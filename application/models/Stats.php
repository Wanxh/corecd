<?php

use Api\Gitlab;
use Api\GitStats;
use Data\Setting;
use Myaf\Pool\Data;
use Myaf\Utils\Arrays;


/**
 * Git代码统计类
 *
 * @author chenqionghe
 */
class StatsModel
{

    /**
     * 队列的键
     */
    const QUEUE_LIST = 'projects-list';

    /**
     * 软删除所有统计表
     */
    public static function truncateStatsTable()
    {
        Data::db()->exec("TRUNCATE c_git_stats_day");
        Data::db()->exec("TRUNCATE c_git_stats_week");
        Data::db()->exec("TRUNCATE c_git_stats_month");
    }


    /**
     * 每天执行一遍，初始化项目队列，取出所有的分页project数据放到队列中
     *
     * @throws Exception
     */
    public static function initQueue()
    {
        $sleep = 5;

        Data::redis()->del(self::QUEUE_LIST);
        $pageSize = 100;
        $nextPage = 1;
        $i = 1;
        do {
            $pageList = Gitlab::getProjects(['page' => $nextPage, 'per_page' => $pageSize, 'order_by' => 'id', 'sort' => 'desc']);
            if (empty($pageList)) {
                self::notifyProjectsErr($nextPage, $pageSize, $sleep);
                sleep($sleep);
                continue;//重试
            }
            $projects = Arrays::get($pageList, 'list', []);
            foreach ($projects as $project) {
                $branches = [];
                do {
                    $branchRes = Gitlab::getProjectBranches($project['id']);
                    if ($branchRes === false) {
                        self::notifyBranchErr($project, $sleep);
                        sleep($sleep);
                        continue;//重试
                    }
                    foreach ($branchRes as $v) {
                        $branches[] = $v['name'];
                    }
                    $project['branches'] = $branches;
                } while ($branchRes === false);

                Data::redis()->lPush(self::QUEUE_LIST, json_encode($project, JSON_UNESCAPED_UNICODE));
                echo $i++ . " push {$project['name']}" . PHP_EOL;
            }
            $nextPage = Arrays::get($pageList, 'pageInfo.next');
        } while (!empty($nextPage));

        echo 'done';
    }


    /**
     * 常驻进程
     * @throws Exception
     */
    public static function popQueue()
    {
        $item = Data::redis()->rPop(self::QUEUE_LIST);
        if (empty($item)) {
            return false;
        }
        $project = json_decode($item, true);

        $util = new GitStats($project);
        if (!$util->checkout()) {
            $msg = "无法正常检出代码，重新放入队列等待下一次统计，项目:{$project['name']}代码," . implode(PHP_EOL, $util->getLastOut());
//            Setting::ding($msg);
            echo $msg . PHP_EOL;
            Data::redis()->rPush(self::QUEUE_LIST, json_encode($project, JSON_UNESCAPED_UNICODE));
            return true;
        }
        //统计天
        $util->createDayStats();
        //统计周
        $util->createWeekStats();
        //统计月
        $util->createMonthStats();
        return true;
    }

    /**
     * 返回队列大小
     *
     * @return mixed
     * @throws Exception
     */
    public static function lenQueue()
    {
        return Data::redis()->lLen(self::QUEUE_LIST);
    }


    /**
     * 获取所有的统计数据
     *
     * @param array $search
     * @return array
     */
    public static function getList($search = [])
    {
        return [
            'day' => self::getDayList($search),
            'week' => self::getWeekList($search),
            'month' => self::getMonthList($search),
        ];
    }

    /**
     * 月
     */
    public static function getMonthList($search = [])
    {
        $daySql = "
SELECT * FROM (
SELECT username, GROUP_CONCAT(project_name SEPARATOR '|') projects, COUNT(DISTINCT(project_id))  project_num,SUM(commits) as commits, SUM(added) as added, SUM(removed) as removed FROM c_git_stats_month GROUP BY username
) AS temp
WHERE commits <> 0
ORDER BY commits DESC,added DESC,removed DESC;
";
        $res = Data::db()->query($daySql);
        if (empty($res)) {
            return $res;
        }
        foreach ($res as & $v) {
            $v['projects'] = explode('|', $v['projects']);
        }
        return $res;
    }


    /**
     * 周
     */
    public static function getWeekList($search = [])
    {
        $daySql = "
SELECT * FROM (
SELECT username, GROUP_CONCAT('project_name' SEPARATOR '|') projects, COUNT(DISTINCT(project_id)) project_num,SUM(commits) as commits, SUM(added) as added, SUM(removed) as removed FROM c_git_stats_week GROUP BY username
) AS temp
WHERE commits <> 0
ORDER BY commits DESC,added DESC,removed DESC;
";
        $res = Data::db()->query($daySql);
        if (empty($res)) {
            return $res;
        }
        foreach ($res as & $v) {
            $v['projects'] = explode('|', $v['projects']);
        }
        return $res;
    }


    /**
     * 天
     */
    public static function getDayList($search = [])
    {
        $daySql = "
SELECT * FROM (
SELECT username, GROUP_CONCAT('project_name' SEPARATOR '|') projects, COUNT(DISTINCT(project_id)) project_num,SUM(commits) as commits, SUM(added) as added, SUM(removed) as removed FROM c_git_stats_day GROUP BY username
) AS temp
WHERE commits <> 0
ORDER BY commits DESC,added DESC,removed DESC;
";
        $res = Data::db()->query($daySql);
        if (empty($res)) {
            return $res;
        }
        foreach ($res as & $v) {
            $v['projects'] = explode('|', $v['projects']);
        }
        return $res;
    }


    /**
     * 月提交详情
     *
     * @param $username
     * @return array|mixed
     * @throws Exception
     */
    public static function getMonthDetail($username)
    {
        $tableName = 'c_git_stats_month';
        return self::getDetail($username, $tableName);
    }


    /**
     * 周提交详情
     *
     * @param $username
     * @return array|mixed
     * @throws Exception
     */
    public static function getWeekDetail($username)
    {
        $tableName = 'c_git_stats_week';
        return self::getDetail($username, $tableName);
    }

    /**
     * 日提交详情
     *
     * @param $username
     * @return array|mixed
     * @throws Exception
     */
    public static function getDayDetail($username)
    {
        $tableName = 'c_git_stats_day';
        return self::getDetail($username, $tableName);
    }

    /**
     * 报警获取项目列表失败
     *
     * @param $project
     * @param $sleep
     * @throws Exception
     */
    private static function notifyBranchErr($project, $sleep)
    {
        $msg = "调用gitlab获取项目分支接口失败，项目ID{$project['id']},项目名:{$project['name']},{$sleep}秒后重试" . PHP_EOL;
        echo $msg;
        Setting::ding($msg);
    }

    /**
     * 报警获取分支列表失败
     *
     * @param $nextPage
     * @param $pageSize
     * @param $sleep
     * @throws Exception
     */
    private static function notifyProjectsErr($nextPage, $pageSize, $sleep)
    {
        $msg = "调用gitlab项目列表接口失败,page:{$nextPage},pageSize:{$pageSize},{$sleep}秒后重试" . PHP_EOL;
        echo $msg;
        Setting::ding($msg);
    }


    /**
     * 测试
     *
     * @throws Exception
     */
    public static function test()
    {
        $list = Data::redis()->lRange(self::QUEUE_LIST, 0, -1);
        foreach ($list as $v) {
            $project = json_decode($v, true);

            if ($project['id'] != 8) {
                continue;
            }
            $util = new GitStats($project);
            if (!$util->checkout()) {
                $msg = "无法正常检出代码，重新放入队列等待下一次统计，项目:{$project['name']}代码," . implode(PHP_EOL, $util->getLastOut());
                echo $msg . PHP_EOL;
            }
            $util->createDayStats();
            $util->createWeekStats();
            $util->createMonthStats();
            die;
        }
    }

    /**
     * 获取详情
     *
     * @param $username
     * @param $tableName
     * @return array|mixed
     * @throws Exception
     */
    private static function getDetail($username, $tableName)
    {
        if (empty($username)) {
            return [];
        }
        $sql = "SELECT* FROM {$tableName} WHERE username='{$username}' ORDER BY project_id,commits DESC,added DESC,removed DESC;";
        $res = Data::db()->query($sql);
        if (empty($res)) {
            return [];
        }
        return $res;
    }

}