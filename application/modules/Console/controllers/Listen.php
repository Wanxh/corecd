<?php

use Console\ConfigModel;
use Console\ListenModel;
use Myaf\Core\ConsoleController;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/31
 * Time: 下午9:02
 *
 * Rancher监听
 * Class ListenController
 */
class ListenController extends ConsoleController
{
    /**
     * 启动监听
     */
    public function indexAction()
    {
        new ConfigModel();
        new ListenModel();
    }
}