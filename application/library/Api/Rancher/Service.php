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
 * 应用服务
 * Class Service
 * @package Api\Rancher
 */
class Service
{
    /**
     * 服务名称
     * @var string
     */
    public $name = '';
    /**
     * 服务id
     * @var string
     */
    public $id = '';
    /**
     * 服务状态
     * @var string
     */
    public $state = '';
    /**
     * 服务描述
     */
    public $description = '';
    /**
     * 更新的次数,用于判断是否为已经开始进行service upgrade操作
     * 很重要,因为listen rancher检测的频率不定,用次值可以判断rancher是否已经开始了对service新一轮的upgrade
     * @var int
     */
    public $createIndex = 0;
    /**
     * 正在执行的状态(yes|no|error)
     * @var string
     */
    public $transitioning = '';
    /**
     * 执行的结果描述
     * @var string
     */
    public $transitioningMessage = '';

    /**
     * Service constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->id = Arrays::get($data, 'id', '');
        $this->name = Arrays::get($data, 'name', '');
        $this->state = Arrays::get($data, 'state', '');
        $this->createIndex = Arrays::get($data, 'createIndex', 0);
        $this->description = Arrays::get($data, 'description', '');
        $this->transitioning = Arrays::get($data, 'transitioning', '');
        $this->transitioningMessage = Arrays::get($data, 'transitioningMessage', '');
    }
}