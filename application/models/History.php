<?php

use Query\HistoryQuery;
use Util\Page;

/**
 * Class HistoryModel
 * @property $uid
 * @property $pid
 * @property $status
 * @property $md5
 * @property $online
 * @property $log_jenkins
 * @property $log_rancher
 * @property $create_time
 */
class HistoryModel extends BaseModel
{

    /**
     * 自定义查询条件
     *
     * @return \Myaf\Mysql\LActiveQuery|HistoryQuery
     */
    public function find()
    {
        $activeQuery = new HistoryQuery($this->_db, $this->trueTableName());
        $activeQuery->setModelClass(get_called_class());
        return $activeQuery;
    }


    /**
     * 表名
     *
     * @return mixed|string
     */
    public function tableName()
    {
        return "history";
    }

    /**
     * 获取项目列表
     *
     * @param array $search
     * @return array|\Myaf\Mysql\LActiveRecord[]|null
     */
    public function getPageList($search = [])
    {
        $query = $this->find()->order('id DESC')->projectIds();
        return Page::pageList($query, $search, '/history/index');

    }


    /**
     * @param $id
     * @return mixed|string
     */
    public function getJenkinsLog($id)
    {
        $obj = $this->findById($id);
        if (empty($obj)) {
            return '';
        }
        return "<pre>$obj->log_jenkins</pre>";
    }

    /**
     * @param $id
     * @return mixed|string
     */
    public function getRancherLog($id)
    {
        $obj = $this->findById($id);
        if (empty($obj)) {
            return '';
        }
        return "<pre>$obj->log_rancher</pre>";
    }
}