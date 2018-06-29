<?php

use Auth\Auth;
use Myaf\Core\WebController;

/**
 * Class IndexController.
 * 首页相关路由
 */
class IndexController extends WebController
{
    /**
     * 首页
     */
    public function indexAction()
    {
        if (Auth::isLogin()) {
            $this->redirect('/project/index');
        } else {
            $this->redirect('/login');
        }
    }

    /**
     * 无权限告警
     */
    public function norightsAction()
    {
        $this->display('norights');
    }
}