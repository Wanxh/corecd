<?php

namespace Auth;

use Myaf\Core\WebController;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/29
 * Time: 上午8:58
 *
 * Class AuthController
 * 身份校验基础controller
 * 支持模板入口
 */
class AuthControl extends WebController
{
    /**
     * 是否重写display以支持模板入口
     * @return bool
     */
    protected function overrideDisplay()
    {
        return true;
    }

    /**
     * 获取当前路由名称
     * @return string
     */
    protected function controllerName()
    {
        return $this->getRequest()->getControllerName();
    }

    /**
     * 是否检测用户登录
     * @return bool
     */
    protected function checkLogin()
    {
        return true;
    }

    /**
     * 是否检测管理员身份
     * @return bool
     */
    protected function checkAdmin()
    {
        return false;
    }

    public function init()
    {
        parent::init();
        if ($this->checkLogin()) {
            if (!Auth::isLogin()) {
                $this->redirect('/login/index');
                return;
            }
        }
        if ($this->checkAdmin()) {
            if (!Auth::isAdmin()) {
                $this->redirect('/index/norights');
                return;
            }
        }
    }

    /**
     * override
     * @param string $tpl
     * @param array|null $parameters
     * @return bool
     */
    protected function display($tpl, array $parameters = null)
    {
        //...todo override
        if ($this->overrideDisplay()) {
            echo $this->render("../public/top", $parameters);
            echo $this->render("../public/left", $parameters);
            echo $this->render($tpl, $parameters);
            echo $this->render("../public/footer", $parameters);
        } else {
            parent::display($tpl, $parameters);
        }
    }
}