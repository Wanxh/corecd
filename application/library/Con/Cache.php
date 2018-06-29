<?php
/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/6/1
 * Time: 上午10:10
 */

namespace Con;

/**
 * Redis Key相关
 * Class Cache
 * @package Con
 */
class Cache
{
    /**
     * 用于上线监控的redis key
     * (数据结构为hash)
     */
    const KEY_ONLINE = 'key-corecd-online';
    
    /**
     * 用于上线接口监控
     */
}