<?php

use Auth\AuthControl;
use Con\Log;

/**
 * Class LogController
 */
class LogController extends AuthControl
{
    protected function checkAdmin()
    {
        return true;
    }

    /**
     * 列表
     */
    public function indexAction()
    {
        $pageList = (new LogModel())->getPageList($this->get());
        $this->display('index', [
            'title' => '日志',
            'controller' => $this->controllerName(),
            'pageList' => $pageList,
            'projects' => (new ProjectModel())->getProjectsByIds(array_column($pageList['list'], 'pid')),
            'logTypes' => Log::TYPE_CNS,
            'userList' => (new UserModel())->getUsers(),
            'search'=>$this->get()
        ]);

    }


    /**
     * 获取日志内容展示
     */
    public function contentAction()
    {
        $model = new LogModel();
        $content = $model->getLogContent($this->get('id'));
        echo $content;
    }

}