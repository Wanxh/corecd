<?php

use Auth\AuthControl;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/29
 * Time: 上午10:25
 *
 * 上线历史路由
 * Class HistoryController
 */
class HistoryController extends AuthControl
{
    /**
     * 首页
     */
    public function indexAction()
    {
        $model = new HistoryModel();
        $pageList = $model->getPageList($this->get());
        $this->display('index', [
            'title' => '部署历史',
            'controller' => $this->controllerName(),
            'pageList' =>$pageList,
            'projects' => (new ProjectModel())->getProjectsByIds(array_column($pageList['list'], 'pid'))
        ]);
    }


    /**
     * 获取jenkins日志
     */
    public function jenkinsLogAction()
    {
        $model = new HistoryModel();
        echo $model->getJenkinsLog($this->get('id'));
    }

    /**
     * 获取rancher日志
     */
    public function rancherLogAction()
    {
        $model = new HistoryModel();
        echo $model->getRancherLog($this->get('id'));
    }
}