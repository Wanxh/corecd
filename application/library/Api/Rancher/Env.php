<?php

namespace Api\Rancher;

use Myaf\Utils\Arrays;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/29
 * Time: 上午11:40
 *
 * 环境实例
 * Class Env
 * @package Api\Rancher
 */
class Env
{
    /**
     * 环境名称
     * @var string
     */
    public $name = '';
    /**
     * 环境id
     * @var string
     */
    public $id = '';
    /**
     * 状态
     * @var string
     */
    public $state = '';
    /**
     * 环境描述
     */
    public $description = '';

    /**
     * Env constructor.
     * @param $data array
     */
    public function __construct($data)
    {
        $this->id = Arrays::get($data, 'id', '');
        $this->name = Arrays::get($data, 'name', '');
        $this->state = Arrays::get($data, 'state', '');
        $this->description = Arrays::get($data, 'description', '');
    }
}