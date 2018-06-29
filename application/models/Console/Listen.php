<?php

namespace Console;

use Api\Jenkins;
use Api\Online;
use Api\Rancher;
use Con\Cache;
use Con\History;
use Con\Project;
use Data\Setting;
use Myaf\Core\G;
use Myaf\Pool\Data;
use Myaf\Utils\Arrays;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/31
 * Time: 下午9:05
 *
 * Rancher监听相关模型
 * Class ListenModel
 * @package Console
 */
class ListenModel
{
    /**
     * @var Online
     */
    private $online = null;

    /**
     * 开始监听Redis
     */
    public function __construct()
    {
        if (!$setting = Setting::getInstance()) {
            exit("setting load error\n");
        }
        while (true) {
            try {
                $this->check();
            } catch (\Exception $e) {
            }
            sleep($setting->listenRate);
            Setting::getInstance(true);
        }
    }

    /**
     * 移除某个监控历史
     * @param $item string
     * @param $online Online
     * @param string $because
     * @param bool $deleteRancherServiceFlag 是否也删除rancher service
     * @throws \Exception
     */
    private function delItem($item, $online, $because = '', $deleteRancherServiceFlag = false)
    {
        if (Data::redis()->sRem(Cache::KEY_ONLINE, $item)) {
            var_dump("item:{$item} 因{$because}被移除");
            Jenkins::deleteJob($online);
            if ($deleteRancherServiceFlag && $online) {
                var_dump("Rancher Service: {$online->totalName} 因上线失败被移除");
                Rancher::deleteService($online);
            }
        }
    }

    /**
     * 批量检测
     * @throws \Exception
     */
    private function check()
    {
        if (!$list = Data::redis()->sMembers(Cache::KEY_ONLINE)) {
            return;
        }
        var_dump("检测队列: ", $list);
        foreach ($list as $item) {
            $this->checkService($item);
        }
        $this->online = null;
    }

    /**
     * 单个监控上线历史
     * @param $item string
     * @throws \Exception
     */
    private function checkService($item)
    {
        $this->online = null;
        list($md5, $createTime) = explode('@', $item);

        $db = Data::db();
        $historyTable = $db->table('history');
        if (!$history = $historyTable->where(['md5' => $md5])->one()) {
            return;
        }

        if (!$this->online = Arrays::get($history, 'online')) {
            Setting::ding("项目({$item})上线失败,原因:无法找到上线历史内容");
            $this->delItem($item, $this->online, '无法找到上线历史内容');
            return;
        }

        if (!$this->online = unserialize($this->online)) {
            Setting::ding("项目({$item})上线失败,原因:无法找到上线历史内容");
            $this->delItem($item, $this->online, '无法将online反序列化');
            return;
        }

        //jenkins检测
        //检测该上线md5在jenkins中是否已经结束
        $errorLogJenkins = Jenkins::getJobLatestBuildErrorInfo($this->online);
        if ($errorLogJenkins === 'NULL') {//等待构建
            return;
        }

        if (time() - $createTime > Setting::getInstance()->listenExpire) {//构建超时了
            $this->online->ding("项目({$this->online->totalName})上线失败,原因:jenkins构建超时");
            $this->delItem($item, $this->online, '上线过期超时');
            $historyTable->where(['md5' => $md5])->update(['log_jenkins' => $errorLogJenkins, 'status' => History::S_ONLINE_ERROR]);
            Data::db()->exec("UPDATE c_projects SET `status`=" . Project::S_ONLINE_FAIL . " WHERE `id`={$history['pid']}");
            return;
        }

        if ($errorLogJenkins) {//构建失败
            $this->online->ding("项目({$this->online->totalName})上线失败,原因:jenkins构建失败");
            $this->delItem($item, $this->online, 'jenkins构建失败');
            $historyTable->where(['md5' => $md5])->update(['log_jenkins' => $errorLogJenkins, 'status' => History::S_ONLINE_ERROR]);
            Data::db()->exec("UPDATE c_projects SET `status`=" . Project::S_ONLINE_FAIL . " WHERE `id`={$history['pid']}");
            return;
        }
        if ($history['log_jenkins'] != 'SUCCESS') { //jenkins构建完成
            $historyTable->where(['md5' => $md5])->update(['log_jenkins' => G::msg()]);
        }

        //rancher检测
        $errorLogRancher = Rancher::getServiceErrorLog($this->online);
        if ($errorLogRancher === 'NULL') {//等待上线
            return;
        }
        if ($errorLogRancher) {//上线失败
            $this->online->ding("项目({$this->online->totalName})上线失败,原因:容器编排部署失败,{$errorLogRancher}");
            $this->delItem($item, $this->online, 'rancher上线失败', true);
            $historyTable->where(['md5' => $md5])->update(['log_rancher' => $errorLogRancher, 'status' => History::S_ONLINE_ERROR]);
            Data::db()->exec("UPDATE c_projects SET `status`=" . Project::S_ONLINE_FAIL . " WHERE `id`={$history['pid']}");
            return;
        }
        //上线完毕
        if ($historyTable->where(['md5' => $md5])->update(['log_rancher' => 'SUCCESS', 'status' => History::S_ONLINE_SUCCESS])) {
            $this->delItem($item, $this->online, '上线完成');
            $this->online->ding("项目({$this->online->totalName})上线完成");
        }
        //修改项目上线状态为成功，上线次数加1
        Data::db()->exec("UPDATE c_projects SET online_num=online_num+1,`status`=" . Project::S_ONLINE_OK . " WHERE `id`={$history['pid']}");

    }
}