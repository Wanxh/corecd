<?php
/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/30
 * Time: 下午7:36
 */

namespace Api\Rancher;

use Myaf\Utils\Arrays;

/**
 * 应用群
 * Class Stack
 * @package Api\Rancher
 */
class Stack
{
    /**
     * 应用群名称
     * @var string
     */
    public $name = '';
    /**
     * 应用群id
     * @var string
     */
    public $id = '';
    /**
     * 应用群状态
     * @var string
     */
    public $state = '';
    /**
     * 应用群描述
     */
    public $description = '';

    /**
     * Stack constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->id = Arrays::get($data, 'id', '');
        $this->name = Arrays::get($data, 'name', '');
        $this->state = Arrays::get($data, 'state', '');
        $this->description = Arrays::get($data, 'description', '');
    }
}