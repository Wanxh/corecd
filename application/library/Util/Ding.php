<?php

namespace Util;

use Myaf\Net\LDing;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/6/2
 * Time: 下午1:28
 *
 * 具备发送钉钉消息的能力
 * Class Ding
 */
class Ding
{
    /**
     * 发送钉钉消息
     * Ding constructor.
     * @param $url string
     * @param $msg string
     * @return mixed
     */
    public static function send($url, $msg)
    {
        if (!$url) {
            return;
        }
        $hook = new LDing($url);
        $hook->send($msg);
    }
}