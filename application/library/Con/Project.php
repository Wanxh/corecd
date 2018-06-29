<?php

namespace Con;

use Myaf\Utils\Arrays;

/**
 * 项目状态枚举
 * Class Project
 * @package Con
 */
class Project
{
    /**
     * 待审核
     */
    const S_WAIT = 0;
    /**
     * 审核通过
     */
    const S_ACCESS = 1;
    /**
     * 被拒绝
     */
    const S_DENIED = 2;

    /**
     * 上线中
     */
    const S_ONLINE_ING = 11;
    /**
     * 上线失败
     */
    const S_ONLINE_FAIL = 12;//上线失败

    /**
     * 上线成功
     */
    const S_ONLINE_OK = 13;//上线成功


    /**
     * web外网服务
     */
    const USE_WEB = 'web';
    /**
     * web内网服务
     */
    const USE_INNER = 'inner';
    /**
     * job任务服务
     */
    const USE_JOB = 'job';

    const USES = [
        self::USE_WEB => 'web外网服务',
        self::USE_INNER => 'web内网服务',
        self::USE_JOB => 'job任务服务',
    ];


    /**
     * 正常
     */
    const STAT_NORMAL = 1;
    /**
     * 已删除
     */
    const STAT_DELETE = 0;


    /**
     * 状态中文
     *
     * @param $value
     * @return mixed|null
     */
    public static function getStatusCn($value)
    {
        $map = [
            self::S_WAIT => '待审核',
            self::S_ACCESS => '审核通过',
            self::S_DENIED => '审核拒绝',
            self::S_ONLINE_ING => '上线中',
            self::S_ONLINE_FAIL => '上线失败',
            self::S_ONLINE_OK => '上线成功',
        ];
        return Arrays::get($map, $value, '未知');
    }
}