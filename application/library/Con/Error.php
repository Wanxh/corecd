<?php
/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/29
 * Time: 下午12:23
 */

namespace Con;

/**
 * 错误码枚举
 * Class Error
 * @package Con
 */
class Error
{
    /***
     * 登录失败
     */
    const LOGIN = 1000;
    /**
     * 系统配置设置错误
     */
    const SETTING_SET = 5001;

    /**
     * 通用错误码
     */
    const COMMON = 3000;

    const PARAMS=3001;
    /**
     * 不是post请求
     */
    const NOT_POST = 3002;

    /**
     * 数据操作失败
     */
    const DB=3333;

    /** 记录不存在 */
    const NOT_EXIST = 3003;

    /** 参数缺失 */
    const PARAMS_MISS = 3003;

    /** 项目状态已经变更  */
    const PROJECT_STATUS_CHANGE= 3004;



}