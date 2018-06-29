<?php

use Auth\Auth;
use Con\Error;
use Myaf\Core\G;
use Myaf\Core\WebController;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/29
 * Time: 下午1:49
 *
 * 登录相关路由
 * Class LoginController
 */
class LoginController extends WebController
{
    /**
     * 首页
     */
    public function indexAction()
    {
        if (Auth::isLogin()) {
            $this->redirect('/project/index');
        } else {
            $this->display('index');
        }
    }

    /**
     * 登录请求
     */
    public function inAction()
    {
        if (Auth::login($this->post())) {
            $this->json(null, '登录成功');
        } else {
            $this->json(null, G::msg(), Error::LOGIN);
        }
    }

    /**
     * 退出
     */
    public function outAction()
    {
        Auth::logout();
        $this->redirect('/index');
    }
}