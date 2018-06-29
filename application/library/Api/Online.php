<?php
/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/30
 * Time: 下午4:56
 */

namespace Api;

use Api\Rancher;
use Api\Rancher\Env;
use Api\Rancher\Stack;
use Con\Project;
use Data\Setting;
use Myaf\Core\G;
use Myaf\Utils\Arrays;
use Util\Ding;

/**
 * 创建或更新的服务实例
 * Class Online
 * @package Api\Rancher
 */
class Online
{
    /**
     * 此次上线id
     * @var string
     */
    public $id = '';
    /**
     * 项目元数据
     * @var null
     */
    public $data = null;
    /**
     * 此次上线的md5值
     * @var string
     */
    public $md5 = '';
    /**
     * 环境
     * @var Env
     */
    public $env = null;
    /**
     * 应用堆
     * @var Stack
     */
    public $stack = null;
    /**
     * 服务实例
     * @var Rancher\Service
     */
    public $service = null;
    /**
     * 节点数量
     * @var int
     */
    public $batchSize = 0;
    /**
     * 标签
     * @var array
     */
    public $labels = [];
    /**
     * 挂载
     * @var array
     */
    public $dataVolumes = [];
    /**
     * 镜像选择
     * @var string
     */
    public $imageUuid = "nginx:alpine";
    /**
     * 基础镜像选择
     * @var string
     */
    public $imageBaseUuid = '';
    /**
     * 环境变量
     * @var object
     */
    public $environment = [];
    /**
     * 健康检查
     * @var int
     */
    public $healthCheck = null;
    /**
     * 服务名称(全网唯一)
     * @var string
     */
    public $name = '';
    /**
     * 服务描述
     * @var string
     */
    public $description = '';
    /**
     * 服务完整名称
     */
    public $totalName = '';
    /**
     * Jenkins此次构建的任务名称(全网唯一)
     * @var string
     */
    public $buildJobName = '';
    /**
     * 网络用途
     * @var string
     */
    public $networkUse = '';
    /**
     * 节点内存限制
     * @var int
     */
    public $nodeMemory = 0;


    /**
     * Online constructor.
     *
     * 注意: 服务集群有两个特殊标签eoffcn.nodes和eoffcn.jobs
     * eoffcn.nodes适用于web程序
     * eoffcn.jobs适用于job任务
     *
     * @param $data array
     * @param $env Env
     * @param $stack Stack
     */
    public function __construct($data, $env, $stack)
    {
        $setting = Setting::getInstance();

        $this->data = $data;
        $this->env = $env;
        $this->stack = $stack;

        $this->md5 = Arrays::get($data, 'md5');
        $this->name = Arrays::get($data, 'project_name', '');
        $this->batchSize = Arrays::get($data, 'batch_size', 1);
        $this->description = Arrays::get($data, 'project_desc', '');
        $this->nodeMemory = Arrays::get($data, 'node_memory', 0);
        $this->totalName = "{$this->name}-{$this->description}";
        $this->environment = (object)[];

        //image name
        $this->imageUuid = "{$setting->registryAddress}/{$this->env->name}/{$this->name}:latest";

        //jenkins相关
        $this->buildJobName = "{$this->name}-{$this->md5}";

        //registry相关
        if ($from = explode("\n", Arrays::get($data, 'ci_dockerfile'))) {
            $this->imageBaseUuid = explode('FROM ', $from[0])[1];
        }

        //rancher相关
        //挂载日志设置
        $this->dataVolumes = ["/data/log/{$this->name}:/data/log", "/etc/localtime:/etc/localtime:ro"];
        //标签设置
        $this->labels = [
            "io.rancher.container.pull_image" => "always"
        ];
        $this->networkUse = Arrays::get($data, 'project_use', Project::USE_WEB);
        if ($this->networkUse != Project::USE_JOB) {
            //web外网或内网服务
            $this->labels['traefik.enable'] = 'true';
            $this->labels['traefik.port'] = '80';
            if ($this->networkUse == Project::USE_WEB) {//如果用于外网
                list($alias, $domain) = self::getAliasDomain(Arrays::get($data, 'domain'));
                $this->labels['traefik.alias'] = $alias;
                $this->labels['traefik.domain'] = $domain;
                if ($secondPath = Arrays::get($data, 'domain_second_path')) {
                    $this->labels['traefik.path.prefix.strip'] = $secondPath;
                }
            }
            //健康检查
            $this->healthCheck = [
                "strategy" => "recreate",
                "type" => "instanceHealthCheck",
                "interval" => 2000,
                "responseTimeout" => 2000,
                "initializingTimeout" => 60000,
                "reinitializingTimeout" => 60000,
                "healthyThreshold" => 2,
                "unhealthyThreshold" => 3,
                "requestLine" => null,
                "port" => 80,
                "name" => null
            ];
            //(重要)该服务会被调度部署至有标签eoffcn.nodes=true的节点集群上
            $this->labels["io.rancher.scheduler.affinity:host_label"] = "corecd.nodes=true";
        } else {
            //job
            $this->healthCheck = null;
            //(重要)该服务会被调度部署至有标签eoffcn.jobs=true的节点集群上
            $this->labels["io.rancher.scheduler.affinity:host_label"] = "corecd.jobs=true";
        }

        //如果有针对此次上线单独的服务器目标标签配置则覆盖
        if ($labels = Arrays::get($data, 'node_label')) {
            $this->labels["io.rancher.scheduler.affinity:host_label"] = "{$data['node_label']}=true";
        }
    }

    /**
     * 从元数据中获取一个key的内容
     * @param $key string
     * @return mixed|null
     */
    public function get($key = null)
    {
        if ($key) {
            return Arrays::get($this->data, $key);
        } else {
            return $this->data;
        }
    }

    /**
     * 根据此次上线进行发送钉钉消息
     * @param $msg string
     */
    public function ding($msg)
    {
        Ding::send(Arrays::get($this->data, 'ding'), $msg);
    }

    /**
     * 获取没有alias的domain部分
     * @param $domain string
     * @return array|mixed
     */
    private static function getAliasDomain($domain)
    {
        $list = explode('.', $domain);
        $com = array_pop($list);
        $main = array_pop($list);
        return [implode('.', $list), "{$main}.{$com}"];
    }

    /**
     * 生产上线实例
     * @param $project array 项目数据
     * @return bool|self
     */
    public static function create($project)
    {
        //生成上线唯一Id
        $md5 = md5(microtime() . uniqid());
        $project['md5'] = $md5;
        if (!$envId = Arrays::get($project, 'env_id')) {
            G::msg('当前上线环境id不存在!');
            return false;
        }
        if (!$env = Rancher::getEnv($envId)) {
            G::msg("{$envId} 该环境不可用!");
            return false;
        }
        if (!$stackName = Arrays::get($project, 'project_use')) {
            G::msg("{$stackName} 该应用群不可用!");
            return false;
        }
        if (!$stack = Rancher::getStack($env, $stackName)) {
            G::msg("当前上线使用的该应用群{$stackName}无法连接!");
            return false;
        }
        $online = new self($project, $env, $stack);
        if ($service = Rancher::getService($online)) {
            //这一部很重要,如果当前online类型为upgrade则需要产生service实例
            $online->service = $service;
        }
        return $online;
    }
}