<?php

use Auth\AuthControl;
use Con\Project;
use Util\Out;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/29
 * Time: 上午10:25
 *
 * 项目审核路由
 * Class AuditController
 */
class AuditController extends AuthControl
{
    /**
     * @var ProjectModel
     */
    private $model;


    protected function checkAdmin()
    {
        return true;
    }
    
    public function init()
    {
        $this->model = new ProjectModel();
        parent::init();
    }

    /**
     * 首页
     */
    public function indexAction()
    {
        $this->display('index', [
            'title' => '项目审核',
            'envList' => EnvModel::getList(),
            'useList' => Project::USES,
            'controller' => $this->controllerName(),
            'list' => $this->model->getAuditList($this->get()),
            'search'=>$this->get()
        ]);
    }

    /**
     * 通过项目
     */
    public function passAction()
    {
        Out::auto($this->model->passProject($this->post('id')));
    }

    /**
     * 通过项目
     */
    public function denyAction()
    {
        Out::auto($this->model->denyProject($this->post('id')));
    }
}