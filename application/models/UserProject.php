<?php

use Api\Jenkins;
use Api\Online;
use Api\Rancher;
use Auth\Auth;
use Con\Cache;
use Con\Log;
use Con\Project;
use Data\Setting;
use Myaf\Core\G;
use Myaf\Pool\Data;
use Myaf\Utils\Arrays;
use Myaf\Validator\Validator;
use Query\ProjectQuery;
use Util\Ding;
use Util\Page;


/**
 * Class ProjectModel
 * @property $id
 * @property $uid
 * @property $pid
 */
class UserProjectModel extends BaseModel
{
    /**
     * 表名
     *
     * @return mixed|string
     */
    public function tableName()
    {
        return "user_project";
    }


    public function import()
    {
        $model = new ProjectModel();

        $list = $model->find()->select();
        $insertData = [];
        foreach ($list as $project) {
            $insertData[] = [
                'uid' => $project['uid'],
                'pid' => $project['id'],
            ];
        }
        $this->batchInsert($insertData);
    }


    /**
     * 根据用户ID获取所有的项目ID
     * @param $uid
     * @return array
     */
    public function getProjectIdsByUid($uid)
    {
        $res = $this->find()->asArray()->where(['uid' => (int)$uid])->select();
        if (empty($res)) {
            return [];
        }
        return array_column($res, 'pid');
    }


    /**
     * 根据项目ID获取获取所有的用户ID
     * @param $pid
     * @return array
     */
    public function getUidsByProjectId($pid)
    {
        $res = $this->find()->asArray()->where(['pid' => (int)$pid])->select();
        return array_column($res, 'uid');
    }


    /**
     * 批量创建项目和用户的关系
     *
     * @param $project
     * @param array $uids
     * @return bool
     */
    public function createProjectUids($project, array $uids)
    {
        $uids = array_unique($uids);
        $insertData = [];
        foreach ($uids as $uid) {
            $insertData[] = [
                'uid' => $uid,
                'pid' => $project['id'],
            ];
        }
        return $this->batchInsert($insertData);
    }

    /**
     * 批量更新项目和用户的关系
     *
     * @param $project
     * @param array $uids
     * @return bool
     */
    public function updateProjectUids($project, array $uids)
    {
        $uids = array_unique($uids);
        //先取出当前所有的uid
        $currentUids = $this->getUidsByProjectId($project['id']);

        //要插入的uid
        $insertUids = array_diff($uids, $currentUids);

        //要删除的uid
        $deleteUids = array_diff($currentUids, $uids);


        if (!empty($deleteUids)) {
            //执行删除
            $this->find()->where(['pid' => (int)$project['id']])->andWhere(['uid' => $deleteUids])->delete();
        }
        if (!empty($insertUids)) {
            //执行插入
            $insertData = [];
            foreach ($insertUids as $uid) {
                $insertData[] = [
                    'uid' => $uid,
                    'pid' => $project['id'],
                ];
            }
            return $this->batchInsert($insertData);
        }
        return true;
    }


    /**
     * 获取用户的项目数
     *
     * @param $uids
     * @return array
     * @throws Exception
     */
    public function getUserProjectNum($uids)
    {
        if (empty($uids)) {
            return [];
        }
        $sql = "SELECT COUNT(*) AS project_num, a.uid, GROUP_CONCAT(pid SEPARATOR ',') 
FROM  c_user_project as a
LEFT JOIN c_projects as b ON a.pid=b.id
WHERE b.state=1 AND a.uid IN (" . implode(',', $uids) . ") GROUP BY uid";
        $res = Data::db()->query($sql);
        if (empty($res)) {
            return [];
        }
        return Arrays::lists($res, 'uid');
    }
}