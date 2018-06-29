<?php
/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/30
 * Time: 上午9:31
 */

namespace Auth;

use Myaf\Utils\OtpUtil;

/**
 * 用户登录钥匙串
 * Class Key
 * @package Auth
 */
class Key
{
    /**
     * 登录秘钥
     * @var string
     */
    public $secret;
    /**
     * 登录二维码地址
     * @var string
     */
    public $url;

    /**
     * Key constructor.
     * @param string $label
     */
    public function __construct($label = 'corecd')
    {
        $this->secret = OtpUtil::getRandomSecret();
        $this->url = OtpUtil::getOtpAuthUrl($this->secret, $label);
    }
}