<?php

namespace Data;

use Myaf\Pool\Data;
use Myaf\Utils\Arrays;
use Util\Ding;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/30
 * Time: 上午11:08
 *
 * 获取系统配置
 */
class Setting
{
    /**
     * jenkins地址
     * @var mixed|null|string
     */
    public $jenkinsAddress = '';
    /**
     * jenkins用户名
     * @var mixed|null|string
     */
    public $jenkinsUsername = '';
    /**
     * jenkins用户token
     * @var mixed|null|string
     */
    public $jenkinsPassword = '';
    /**
     * 系统钉钉
     * @var mixed|null|string
     */
    public $ding = '';
    /**
     * rancher api address
     * @var mixed|null|string
     */
    public $rancherAddress = '';
    /**
     * rancher api key
     * @var mixed|null|string
     */
    public $rancherKey = '';
    /**
     * rancher api secret
     * @var mixed|null|string
     */
    public $rancherSecret = '';
    /**
     * registry address
     * @var string
     */
    public $registryAddress = 'registry.eoffcn.com';
    /**
     * registry username
     * @var string
     */
    public $registryUsername = 'inner';
    /**
     * registry password
     * @var string
     */
    public $registryPassword = '1qaz2wsx3edc';
    /**
     * 上线状态检测频率(单位:秒)
     * @var int
     */
    public $listenRate = 3;
    /**
     * 上线状态检测过期时间(单位:秒)
     * @var int
     */
    public $listenExpire = 120;

    /**
     * 单点cpu资源分数(单核是1024份)
     * @var int
     */
    public $nodeCpuShares = 4000;
    /**
     * 单点内存限制(单位:Byte)
     * @var int
     */
    public $nodeMemory = 3670016000;

    /**
     * Setting constructor.
     */
    public function __construct()
    {
        $this->jenkinsAddress = Arrays::get(self::$info, 'jenkins_address', '');
        $this->jenkinsUsername = Arrays::get(self::$info, 'jenkins_username', '');
        $this->jenkinsPassword = Arrays::get(self::$info, 'jenkins_password', '');

        $this->registryAddress = Arrays::get(self::$info, 'registry_address', '');
        $this->registryUsername = Arrays::get(self::$info, 'registry_username', '');
        $this->registryPassword = Arrays::get(self::$info, 'registry_password', '');

        $this->rancherAddress = Arrays::get(self::$info, 'rancher_address', '');
        $this->rancherKey = Arrays::get(self::$info, 'rancher_key', '');
        $this->rancherSecret = Arrays::get(self::$info, 'rancher_secret', '');

        $this->ding = Arrays::get(self::$info, 'ding', '');
        $this->listenRate = Arrays::get(self::$info, 'listen_rate', '');
        $this->listenExpire = Arrays::get(self::$info, 'listen_expire', '');

        $this->nodeCpuShares = Arrays::get(self::$info, 'node_cpu_shares', 4000);
        $this->nodeMemory = Arrays::get(self::$info, 'node_memory', 3670016000);
    }

    /**
     * 系统配置暂存
     * @var bool|array
     */
    private static $info = false;

    /**
     * 返回setting实例
     *
     * @param bool $flush
     * @return bool|Setting
     * @throws \Exception
     */
    public static function getInstance($flush = false)
    {
        if ($info = self::load($flush)) {
            return new self();
        }
        return false;
    }

    /**
     * 获取所有系统配置
     *
     * @param bool $flush 是否强制刷新配置
     * @return array|bool
     * @throws \Exception
     */
    public static function load($flush = false)
    {
        if (!self::$info || $flush) {
            if ($result = Data::db()->table('config')->select()) {
                $items = [];
                foreach ($result as $item) {
                    $items[Arrays::get($item, 'name')] = Arrays::get($item, 'value');
                }
                self::$info = $items;
            }
        }
        return self::$info;
    }

    /**
     * 发送钉钉消息
     *
     * @param $msg
     * @throws \Exception
     */
    public static function ding($msg)
    {
        Ding::send(@self::getInstance()->ding, $msg);
    }
}