<?php

namespace Auth;

use Con\Role;
use Exception;
use Myaf\Core\G;
use Myaf\Pool\Data;
use Myaf\Utils\Arrays;
use Myaf\Utils\OtpUtil;
use Myaf\Validator\Validator;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/29
 * Time: 上午9:17
 *
 *
 * 用户鉴权类
 * Class Auth
 */
class Auth
{
    /**
     * 用户完整信息
     * @var array|bool
     */
    private static $info = false;

    /**
     * 获取用户身份信息
     * @return array|bool
     * @throws Exception
     */
    public static function userInfo()
    {
        if (!self::$info) {
            self::$info = Data::session()->get();
        }
        return self::$info;
    }

    /**
     * 用户是否登录
     * @return bool
     */
    public static function isLogin()
    {
        return self::userInfo() != false;
    }

    /**
     * 当前用户是否为管理员
     * @return bool
     */
    public static function isAdmin()
    {
        if ($info = self::userInfo()) {
            return (int)Arrays::get($info, 'role', Role::USER_COMMON) == Role::USER_ADMIN;
        }
        return false;
    }

    /**
     * 直接获取用户id
     * @return mixed|null
     */
    public static function userId()
    {
        return Arrays::get(self::userInfo(), 'id', 0);
    }

    /**
     * 直接获取当前用户username
     * @return mixed|null
     */
    public static function userName()
    {
        return Arrays::get(self::userInfo(), 'username', '');
    }

    /**
     * 登入验证
     * @param $data array|mixed
     * @return bool
     */
    public static function login($data)
    {
        $val = new Validator($data);
        $val->rules([
            ['required', 'username'],
            ['required', 'totp']
        ]);
        if (!$val->validate()) {
            G::msg($val->errorString());
            return false;
        }
        if (!$info = Data::db()->table('users')->where(['username' => Arrays::get($data, 'username')])->one()) {
            G::msg("用户 {$data['username']} 不存在");
            return false;
        }
        if (!OtpUtil::verify(Arrays::get($info, 'secret'), (string)Arrays::get($data, 'totp'))) {
            G::msg("验证码错误");
            return false;
        }
        if (!Data::session()->mSet($info)) {
            G::msg("服务错误,登录Session录入失败");
            return false;
        }
        Data::db()->table('users')->where(['username' => Arrays::get($data, 'username')])->update(['update_time' => date('Y-m-d H:i:s')]);
        return true;
    }

    /**
     * 登出
     */
    public static function logout()
    {
        Data::session()->clear();
    }

    /**
     * 获取一个随机钥匙串
     */
    public static function getKey()
    {
        return new Key();
    }
}