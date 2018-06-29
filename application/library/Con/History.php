<?php
/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/30
 * Time: 上午10:14
 */

namespace Con;

/**
 * 上线历史状态枚举
 * Class History
 * @package Con
 */
class History
{
    /**
     * 上线中
     */
    const S_ONLINE_ING = 0;
    /**
     * 上线成功
     */
    const S_ONLINE_SUCCESS = 1;
    /**
     * 上线失败
     */
    const S_ONLINE_ERROR = 2;
}
