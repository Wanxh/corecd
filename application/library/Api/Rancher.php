<?php

namespace Api;

use Api\Rancher\Env;
use Api\Rancher\Service;
use Api\Rancher\Stack;
use Data\Setting;
use Myaf\Core\G;
use Myaf\Net\LCurl;
use Myaf\Utils\Arrays;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/29
 * Time: 上午11:38
 *
 * Rancher Restful
 */
class Rancher
{
    private static $lastResult;

    /**
     * 获取标准basic auth base64 string
     * @return string
     */
    public static function getBasicAuth()
    {
        $setting = Setting::getInstance();
        return base64_encode(sprintf("%s:%s", $setting->rancherKey, $setting->rancherSecret));
    }

    /**
     * 获取env环境详情
     * @param null $envId
     * @return Env|bool
     */
    public static function getEnv($envId = null)
    {
        if (!$result = self::request("/projects/{$envId}", 'get')) {
            return false;
        }
        return new Env($result);
    }

    /**
     * 获取env原始数据源.
     * ['envId' => [...]]
     * @return array|bool
     */
    public static function getEnvData()
    {
        if (!$result = self::request('/projects?limit=-1&sort=name', 'get')) {
            return false;
        }
        if (!$data = Arrays::get($result, 'data')) {
            return false;
        }
        $items = [];
        foreach ($data as $env) {
            if (Arrays::get($env, 'type') == 'project') {
                $items[Arrays::get($env, 'id')] = $env;
            }
        }
        return $items;
    }

    /**
     * 判断某个环境内是否存在某个应用栈
     * @param Env $env
     * @param $stackName string
     * @return bool|Stack
     */
    public static function getStack(Env $env, $stackName)
    {
        if (!$result = self::request("/projects/{$env->id}/stacks", 'get')) {
            return false;
        }
        if (!$result = Arrays::get($result, 'data')) {
            return false;
        }
        foreach ($result as $stack) {
            if (Arrays::get($stack, 'name') == $stackName) {
                return new Stack($stack);
            }
        }
        return false;
    }

    /**
     * 获取服务最近一次的错误日志
     * @param Online $online
     * @return bool|mixed
     */
    public static function getServiceErrorLog(Online $online)
    {
        if (!$service = self::getService($online)) {//可能上线中等待
            return 'NULL';
        }
        if ($service->transitioning == 'yes') {//上线中等待
            return 'NULL';
        }
        if ($online->service) {//说明这是online upgrade操作类型
            if ($service->createIndex <= $online->service->createIndex) {
                //rancher尚未进入到最新的online upgrade中进行等待
                return 'NULL';
            }
        }
        if ($service->state == 'upgraded') {//上线更新完毕并执行结束更新
            self::request("/projects/{$online->env->id}/services/{$service->id}/?action=finishupgrade");
            return 'NULL';
        }
        if ($service->state == 'active') {//上线完毕
            return false;
        }
        if ($service->state == 'removed') {//被删除
            return '被删除';
        }
        return $service->transitioningMessage;
    }

    /**
     * 判断某个环境内是否包含某个服务,如果存在则返回服务id
     * @param $online Online
     * @return Service|bool
     */
    public static function getService(Online $online)
    {
        //先通过服务名称获取服务列表找出该服务相关简单描述
        if (!$result = self::request("/projects/{$online->env->id}/stacks/{$online->stack->id}/services", 'get')) {
            return false;
        }
        if (!$data = Arrays::get($result, 'data')) {
            return false;
        }
        $id = '';
        foreach ($data as $s) {
            if (Arrays::get($s, 'name') == $online->name) {
                $id = Arrays::get($s, 'id');
                break;
            }
        }
        if (!$id) {
            return false;
        }
        //如果不属于正常状态则再根据服务id获取更详细的服务内容
        if (!$result = self::request("/projects/{$online->env->id}/services/{$id}", 'get')) {
            return false;
        }
        return new Service($result);
    }

    /**
     * 创建服务
     * @param Online $online
     * @return string
     */
    public static function getCreateShell(Online $online)
    {
        if ($online->service) {
            return self::getUpdateServiceString($online);
        } else {
            return self::getCreateServiceString($online);
        }
    }

    /**
     * 删除某个服务
     * @param Online $online
     */
    public static function deleteService(Online $online)
    {
        self::request("/projects/{$online->env->id}/services/{$online->service->id}", 'delete');
    }

    /**
     * 组装创建服务字符串
     * @param Online $online
     * @return string
     */
    private static function getCreateServiceString(Online $online)
    {
        $setting = Setting::getInstance();
        $data = [
            "scale" => $online->batchSize,
            "assignServiceIpAddress" => false,
            "startOnCreate" => true,
            "type" => "service",
            "stackId" => $online->stack->id,
            "launchConfig" => [
                "instanceTriggeredStop" => "stop",
                "kind" => "container",
                "networkMode" => "managed",
                "privileged" => false,
                "publishAllPorts" => false,
                "readOnly" => false,
                "runInit" => false,
                "startOnCreate" => true,
                "stdinOpen" => true,
                "tty" => true,
                "vcpu" => 1,
                "drainTimeoutMs" => 0,
                "type" => "launchConfig",
                "labels" => $online->labels,
                "restartPolicy" => [
                    "name" => "always"
                ],
                "secrets" => [],
                "dataVolumes" => $online->dataVolumes,
                "dataVolumesFrom" => [],
                "dns" => [],
                "dnsSearch" => [],
                "capAdd" => ["SYS_PTRACE"],
                "capDrop" => [],
                "devices" => [],
                "logConfig" => [
                    "driver" => "",
                    "config" => (object)[]
                ],
                "dataVolumesFromLaunchConfigs" => [],
                "imageUuid" => "docker:{$online->imageUuid}",
                "ports" => [],
                "workingDir" => null,
                "environment" => $online->environment,
                "healthCheck" => $online->healthCheck,
                "blkioWeight" => null,
                "cgroupParent" => null,
                "count" => null,
                "cpuCount" => null,
                "cpuPercent" => null,
                "cpuPeriod" => null,
                "cpuQuota" => null,
                "cpuRealtimePeriod" => null,
                "cpuRealtimeRuntime" => null,
                "cpuSet" => null,
                "cpuSetMems" => null,
                "cpuShares" => $setting->nodeCpuShares,
                "createIndex" => null,
                "created" => null,
                "deploymentUnitUuid" => null,
                "description" => null,
                "diskQuota" => null,
                "domainName" => null,
                "externalId" => null,
                "firstRunning" => null,
                "healthInterval" => null,
                "healthRetries" => null,
                "healthState" => null,
                "healthTimeout" => null,
                "hostname" => null,
                "ioMaximumBandwidth" => null,
                "ioMaximumIOps" => null,
                "ip" => null,
                "ip6" => null,
                "ipcMode" => null,
                "isolation" => null,
                "kernelMemory" => null,
                "memory" => $online->nodeMemory ? $online->nodeMemory : $setting->nodeMemory,
                "memoryMb" => null,
                "memoryReservation" => null,
                "memorySwap" => null,
                "memorySwappiness" => null,
                "milliCpuReservation" => null,
                "oomScoreAdj" => null,
                "pidMode" => null,
                "pidsLimit" => null,
                "removed" => null,
                "requestedIpAddress" => null,
                "shmSize" => null,
                "startCount" => null,
                "stopSignal" => null,
                "stopTimeout" => null,
                "user" => null,
                "userdata" => null,
                "usernsMode" => null,
                "uts" => null,
                "uuid" => null,
                "volumeDriver" => null,
                "networkLaunchConfig" => null
            ],
            "secondaryLaunchConfigs" => [],
            "name" => $online->name,
            "description" => $online->description,
            "createIndex" => null,
            "created" => null,
            "externalId" => null,
            "healthState" => null,
            "kind" => null,
            "removed" => null,
            "selectorContainer" => null,
            "selectorLink" => null,
            "uuid" => null,
            "vip" => null,
            "fqdn" => null
        ];
        $data = json_encode($data);
        $setting = Setting::getInstance();
        $basicAuth = self::getBasicAuth();
        $shell =
            <<< EOF
curl --request POST \
  --url '{$setting->rancherAddress}/v2-beta/projects/{$online->env->id}/services' \
  --header 'authorization: Basic {$basicAuth}' \
  --header 'content-type: application/json' \
  --data '{$data}'
EOF;
        return $shell;
    }

    /**
     * 组装更新服务字符串
     * @param Online $online
     * @return string
     */
    private static function getUpdateServiceString(Online $online)
    {
        $setting = Setting::getInstance();
        $data = [
            "inServiceStrategy" => [
                "batchSize" => $online->batchSize,
                "intervalMillis" => 2000,
                "startFirst" => true,
                "launchConfig" => [
                    "instanceTriggeredStop" => "stop",
                    "kind" => "container",
                    "networkMode" => "managed",
                    "privileged" => false,
                    "publishAllPorts" => false,
                    "readOnly" => false,
                    "runInit" => false,
                    "startOnCreate" => true,
                    "stdinOpen" => true,
                    "tty" => true,
                    "vcpu" => 1,
                    "drainTimeoutMs" => 0,
                    "type" => "launchConfig",
                    "labels" => $online->labels,
                    "restartPolicy" => [
                        "name" => "always"
                    ],
                    "secrets" => [],
                    "dataVolumes" => $online->dataVolumes,
                    "dataVolumesFrom" => [],
                    "dns" => [],
                    "dnsSearch" => [],
                    "capAdd" => ["SYS_PTRACE"],
                    "capDrop" => [],
                    "devices" => [],
                    "logConfig" => [
                        "driver" => "",
                        "config" => (object)[]
                    ],
                    "dataVolumesFromLaunchConfigs" => [],
                    "imageUuid" => "docker:{$online->imageUuid}",
                    "ports" => [],
                    "workingDir" => null,
                    "environment" => $online->environment,
                    "healthCheck" => $online->healthCheck,
                    "blkioWeight" => null,
                    "cgroupParent" => null,
                    "count" => null,
                    "cpuCount" => null,
                    "cpuPercent" => null,
                    "cpuPeriod" => null,
                    "cpuQuota" => null,
                    "cpuRealtimePeriod" => null,
                    "cpuRealtimeRuntime" => null,
                    "cpuSet" => null,
                    "cpuSetMems" => null,
                    "cpuShares" => $setting->nodeCpuShares,
                    "createIndex" => null,
                    "created" => null,
                    "deploymentUnitUuid" => null,
                    "description" => null,
                    "diskQuota" => null,
                    "domainName" => null,
                    "externalId" => null,
                    "firstRunning" => null,
                    "healthInterval" => null,
                    "healthRetries" => null,
                    "healthState" => null,
                    "healthTimeout" => null,
                    "hostname" => null,
                    "ioMaximumBandwidth" => null,
                    "ioMaximumIOps" => null,
                    "ip" => null,
                    "ip6" => null,
                    "ipcMode" => null,
                    "isolation" => null,
                    "kernelMemory" => null,
                    "memory" => $online->nodeMemory ? $online->nodeMemory : $setting->nodeMemory,
                    "memoryMb" => null,
                    "memoryReservation" => null,
                    "memorySwap" => null,
                    "memorySwappiness" => null,
                    "milliCpuReservation" => null,
                    "oomScoreAdj" => null,
                    "pidMode" => null,
                    "pidsLimit" => null,
                    "removed" => null,
                    "requestedIpAddress" => null,
                    "shmSize" => null,
                    "startCount" => null,
                    "stopSignal" => null,
                    "stopTimeout" => null,
                    "user" => null,
                    "userdata" => null,
                    "usernsMode" => null,
                    "uts" => null,
                    "uuid" => null,
                    "volumeDriver" => null,
                    "networkLaunchConfig" => null
                ],
                "secondaryLaunchConfigs" => [],
                "name" => $online->name,
                "description" => $online->description,
                "createIndex" => null,
                "created" => null,
                "externalId" => null,
                "healthState" => null,
                "kind" => null,
                "removed" => null,
                "selectorContainer" => null,
                "selectorLink" => null,
                "uuid" => null,
                "vip" => null,
                "fqdn" => null
            ]
            , 'secondaryLaunchConfigs' => []
        ];

        $data = json_encode($data);
        $setting = Setting::getInstance();
        $basicAuth = self::getBasicAuth();
        $shell =
            <<< EOF
curl --request POST \
  --url '{$setting->rancherAddress}/v2-beta/projects/{$online->env->id}/services/{$online->service->id}/?action=upgrade' \
  --header 'authorization: Basic {$basicAuth}' \
  --header 'content-type: application/json' \
  --data '{$data}'
  
EOF;

        return $shell;
    }

    /**
     * 修改服务节点数
     * @param Online $online
     * @param $scale int
     * @return bool
     */
    public static function changeServiceScale(Online $online, $scale)
    {
        if (!$service = self::getService($online)) {
            G::msg('无法找到该上线服务');
            return false;
        }
        return self::request("/projects/{$online->env->id}/services/{$service->id}", 'put', json_encode(['type' => 'service', 'scale' => $scale]));
    }

    /**
     * 统一请求
     * @param $route
     * @param string $method
     * @param null $data
     * @return bool|array
     */
    private static function request($route, $method = 'post', $data = null)
    {
        if (!$setting = Setting::getInstance()) {
            return false;
        }
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => sprintf("Basic %s", self::getBasicAuth())
        ];
        $curl = new LCurl(LCurl::POST_JSON, 5);
        $rt = $curl->setJsonResult(true)->$method("{$setting->rancherAddress}/v2-beta{$route}", $data, $headers);
        //设置请求结果
        self::$lastResult = $curl->getOriginalResult();
        if ($curl->httpCode != 200) {
            return false;
        }
        return $rt;
    }

    /**
     * 获取最近一次请求结果字符串
     *
     * @return mixed
     */
    public static function getLastResult()
    {
        return self::$lastResult;
    }


}