<?php

namespace Con;

use Myaf\Utils\Arrays;

/**
 * 日志常量枚举
 */
class Log
{
    const PROJECT_UPDATE = 'project-update';
    const PROJECT_CREATE = 'project-create';
    const PROJECT_PASS = 'project-pass';
    const PROJECT_DENY = 'project-deny';
    const PROJECT_UPDATE_BATCH_SIZE = 'project-update-batch-size';
    const PROJECT_ONLINE = 'project-online';
    const PROJECT_DELETE = 'project-delete';
    const PROJECT_CHANGE_OWNER = 'project-change-owner';

    const USER_CREATE = 'user-create';
    const USER_UPDATE = 'user-update';
    const USER_DELETE = 'user-delete';

    const SETTING_UPDATE = 'setting-update';


    const CDN_ADD_FILE = 'cdn-add-file';
    const CDN_DEL_FILE = 'cdn-del-file';


    /** @var array 类型对应中文 */
    const TYPE_CNS = [
        self::PROJECT_UPDATE => '（项目）修改',
        self::PROJECT_CREATE => '（项目）添加',
        self::PROJECT_PASS => '（项目）审核通过',
        self::PROJECT_DENY => '（项目）审核拒绝',
        self::PROJECT_ONLINE => '（项目）上线',
        self::PROJECT_UPDATE_BATCH_SIZE => '（项目）修改节点',
        self::PROJECT_DELETE => '（项目）删除',
        self::PROJECT_CHANGE_OWNER => '（项目）转移管理员',

        self::USER_CREATE => '（用户）添加',
        self::USER_UPDATE => '（用户）修改',
        self::USER_DELETE => '（用户）删除',

        self::SETTING_UPDATE => '（系统配置）更新',

        self::CDN_ADD_FILE => '（CDN）上传文件',
        self::CDN_DEL_FILE => '（CDN）删除文件',


    ];

    /**
     * 类型中文
     *
     * @param $type
     * @return mixed|null
     */
    public static function getTypeCn($type)
    {
        return Arrays::get(self::TYPE_CNS, $type, '未知');
    }


}