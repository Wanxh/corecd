<?php

use Auth\AuthControl;
use Con\Error;
use Con\Project;
use Myaf\Core\G;
use Util\Out;

/**
 * Class ProjectController
 */
class ProjectController extends AuthControl
{
    /**
     * @var ProjectModel
     */
    private $model;

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
        $pageList = $this->model->getPageList($this->get());
        $this->display('index', [
            'title' => '我的项目',
            'controller' => $this->controllerName(),
            'pageList' => $pageList,
            'envList' => EnvModel::getList(),
            'useList' => Project::USES,
            'userList' => (new UserModel())->getUsers(),
            'search' => $this->get(),
        ]);
    }

    /**
     * 更新
     */
    public function updateAction()
    {
        $project = $this->model->getProject($this->get('id'));
        if (empty($project)) {
            exit(G::msg());
        }
        $this->display('edit', [
            'title' => '项目修改',
            'controller' => $this->controllerName(),
            'envList' => EnvModel::getList(),
            'useList' => Project::USES,
            'project' => $project,
            'projectUids' => (new UserProjectModel())->getUidsByProjectId($project->id),
            'userList' => (new UserModel())->getUsers(),
            'isUpdate' => 1,
        ]);
    }


    /**
     * 克隆项目
     */
    public function cloneAction()
    {
        $project = $this->model->getProject($this->get('id'));
        if (empty($project)) {
            exit(G::msg());
        }
        $this->display('edit', [
            'title' => '项目克隆',
            'controller' => $this->controllerName(),
            'envList' => EnvModel::getList(),
            'useList' => Project::USES,
            'project' => $project,
        ]);
    }


    /**
     * 执行更新
     */
    public function doUpdateAction()
    {
        if (!$this->isPost()) {
            Out::error(Error::NOT_POST, '非法请求');
            return;
        }
        Out::auto($this->model->doUpdate($this->post()));
    }


    /**
     * 创建项目
     */
    public function createAction()
    {
        $this->display('edit', [
            'title' => '项目申请',
            'controller' => $this->controllerName(),
            'envList' => EnvModel::getList(),
            'userList' => (new UserModel())->getUsers(),
            'useList' => Project::USES
        ]);
    }

    /**
     * 执行创建
     */
    public function doCreateAction()
    {
        if (!$this->isPost()) {
            Out::error(Error::NOT_POST, '非法请求');
            return;
        }
        Out::auto($this->model->doCreate($this->post()));
    }

    /**
     * 执行上线
     */
    public function onlineAction()
    {
        if (!$this->isPost()) {
            Out::error(Error::NOT_POST, '非法请求');
            return;
        }
        Out::auto($this->model->online($this->get('id')));
    }


    /**
     * 详情
     */
    public function detailAction()
    {
        $project = $this->model->getProject($this->get('id'));
        if (empty($project)) {
            exit(G::msg());
        }
        $this->display('detail', [
            'title' => '项目修改',
            'controller' => $this->controllerName(),
            'envList' => EnvModel::getList(),
            'userList' => (new UserModel())->getUsers(),
            'projectUids' => (new UserProjectModel())->getUidsByProjectId($project->id),
            'useList' => Project::USES,
            'project' => $project,
        ]);
    }


    /**
     * 修改节点数量
     */
    public function updateBatchSizeAction()
    {
        Out::auto($this->model->updateBatchSize($this->post()));
    }


    /**
     * 删除项目
     */
    public function delAction()
    {
        Out::auto($this->model->del($this->post('id')));
    }


    /**
     * 转移项目管理员
     */
    public function changeOwnerAction()
    {
        Out::auto($this->model->changeOwner($this->post()));
    }


    /**
     * 用户项目
     */
    public function userProjectsAction()
    {
        $uid = $this->get('uid');
        if (empty($uid)) {
            $this->shutdown("uid必传");
        }
        $user = (new UserModel())->findById($uid);
        if (empty($user)) {
            $this->shutdown("用户不存在");
        }
        $projects = $this->model->myProjects($user->id);
        $this->display('userProjects', [
            'title' => '我的项目',
            'controller' => $this->controllerName(),
            'projects' => $projects,
            'user' => $user,
            'useList' => Project::USES,
            'search' => $this->get(),
        ]);
    }

}