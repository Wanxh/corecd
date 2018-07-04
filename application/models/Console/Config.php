<?php
/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/6/29
 * Time: 下午2:59
 */

namespace Console;

use Con\Role;
use Myaf\Core\G;
use Myaf\Pool\Data;
use Myaf\Utils\FileUtil;
use Myaf\Utils\OtpUtil;

/**
 * Class ConfigModel
 * 执行初始化sql 和 配置文件
 * @package Console
 */
class ConfigModel
{
    /**
     * 初始化mysql
     */
    public function __construct()
    {
        $this->initTables();
        $this->initAdmin();
    }

    /**
     * 初始化表结构
     */
    private function initTables()
    {
        $sql = APP_PATH . '/devops/create.sql';
        if (!$sql = FileUtil::read($sql)) {
            G::shutdown("未找到初始化的create.sql文件\n");
        }
        if (!Data::db()->exec($sql)) {
            G::shutdown("初始化表结构失败\n");
        }
    }

    /**
     * 初始化管理员账户
     */
    private function initAdmin()
    {
        if (Data::db()->table('users')->where(['username' => 'corecd-admin'])->one()) {
            return;
        }
        $secret = OtpUtil::getRandomSecret();
        $url = OtpUtil::getOtpAuthUrl($secret, 'corecd-admin');
        if (!Data::db()->table('users')->insert([
            'username' => 'corecd-admin',
            'secret' => $secret,
            'turl' => $url,
            'role' => Role::USER_ADMIN
        ])
        ) {
            G::shutdown("初始化管理员账户失败");
        }
        G::json(['url' => $url], "管理员Google Auth URL创建成功");
    }
}