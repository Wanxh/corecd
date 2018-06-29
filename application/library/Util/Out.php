<?php

namespace Util;

use Con\Error;
use Myaf\Core\G;

/**
 * 标准输出
 * @author chenqionghe
 */
class Out
{

    /**
     * 成功json
     *
     * @param $data
     * @param $msg
     * @return string
     */
    public static function success($data = null, $msg)
    {
        echo G::json($data, $msg, 0);
    }

    /**
     * 错误json
     *
     * @param $code
     * @param string $msg
     * @param null $data
     * @return string
     */
    public static function error($code, $msg = '', $data = null)
    {
        echo G::json($data, $msg, $code);
    }

    /**
     * 自动输出,$data为真输出success，否则输出失败
     *
     * @param mixed $data
     */
    public static function auto($data)
    {
        if (!$data) {
            self::error(Error::COMMON, "操作失败", $data);
        } else {
            self::success($data, "操作成功");
        }
    }

    /**
     * 输出json
     *
     * @param $data
     */
    public static function json($data)
    {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }


}