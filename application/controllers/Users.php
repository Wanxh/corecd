<?php

use Auth\AuthControl;
use Myaf\Utils\ImageUtil;
use Myaf\Utils\OtpUtil;
use Util\Out;
use Con\Error;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/29
 * Time: 上午10:15
 *
 * 用户管理路由
 * Class UsersController
 */
class UsersController extends AuthControl
{
    /** @var UserModel */
    private $model;


    protected function checkAdmin()
    {
        return true;
    }

    public function init()
    {
        $this->model = new UserModel();
        parent::init();
    }

    /**
     * 首页
     */
    public function indexAction()
    {
        $pageList = $this->model->getPageList($this->get());
        $userIds = array_column($pageList['list'],'id');
        $userProjectNum = (new UserProjectModel())->getUserProjectNum($userIds);
        $this->display('index', [
            'title' => '用户管理',
            'controller' => $this->controllerName(),
            'pageList' => $pageList,
            'userProjectNum' => $userProjectNum,
            'search' => $this->get(),
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
     * 删除
     */
    public function delAction()
    {
        if (!$this->isPost()) {
            Out::error(Error::NOT_POST, '非法请求');
            return;
        }
        Out::auto($this->model->del($this->post('id')));
    }


    /**
     * 二维码图片
     */
    public function qrcodeAction()
    {
        $user = $this->model->findById($this->get('id'));
        if (empty($user)) {
            Out::error(Error::NOT_EXIST, '用户不存在');
            return;
        }

        ImageUtil::qrcodePng($user->turl);
    }


    /**
     * 获取随机key
     */
    public function getKeyAction()
    {
        Out::success(['key' => OtpUtil::getRandomSecret()], 'ok');
    }

}