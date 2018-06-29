<?php

use Auth\AuthControl;
use Con\Error;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/29
 * Time: 上午10:02
 *
 * 系统设置路由
 * Class SettingController
 */
class SettingController extends AuthControl
{
    protected function checkAdmin()
    {
        return true;
    }

    /**
     * 首页
     */
    public function indexAction()
    {
        if ($result = (new SettingModel())->get()) {
            $this->display('index', ['title' => '系统设置', 'data' => $result, 'controller' => $this->controllerName()]);
        } else {
            $this->display('index', ['title' => '系统设置', 'data' => [], 'controller' => $this->controllerName()]);
        }
    }

    /**
     * 设置数据
     */
    public function setAction()
    {
        if ((new SettingModel())->set($this->post())) {
            $this->json(null, '配置修改成功');
        } else {
            $this->json(null, '配置修改失败', Error::SETTING_SET);
        }
    }
}