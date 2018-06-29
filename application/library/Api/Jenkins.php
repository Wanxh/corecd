<?php

namespace Api;

use Data\Setting;
use Myaf\Core\G;
use Myaf\Net\LCurl;
use Myaf\Utils\Arrays;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/29
 * Time: 上午11:39
 */
class Jenkins
{
    /** @var string 最近一次构建xml */
    private static $lastXml = '';


    /**
     * 组装钉钉报警机器人
     * @param Online $online
     * @param $msg string 报警内容
     * @return string
     */
    private static function getShellDing(Online $online, $msg)
    {
        $shell =
            <<< EOF
curl --request POST \
  --url '{$online->get('ding')}' \
  --header 'cache-control: no-cache' \
  --header 'content-type: application/json' \
  --header 'postman-token: 8d18eaf7-0457-7670-8fbd-ad5d32505571' \
  --data '{"msgtype": "text","text": {"content": "{$msg}"},"at": {"isAtAll": true}}'
EOF;
        return $shell;
    }

    /**
     * 获取
     * @param Online $online
     * @return string
     */
    private static function getShellDocker(Online $online)
    {
        $shell =
            <<< EOF
{$online->get('ci_dockerfile')}
ENV APP_NAME {$online->name}
ENV APP_NETWORK_USE {$online->networkUse}
ENV APP_MONITOR_HOOK {$online->get('ding')}
EOF;
        return $shell;
    }

    /**
     * 生成标准shell
     * @param Online $online
     * @return string
     */
    private static function createShell(Online $online)
    {
        $setting = Setting::getInstance();
        $shellDockerfile = self::getShellDocker($online);
        $shellDingBuildStart = self::getShellDing($online, "开始集成[{$online->totalName}]");
        $shellDingDeployStart = self::getShellDing($online, "开始部署[{$online->totalName}]");
        if (!$shellRancherDeploy = Rancher::getCreateShell($online)) {
            return false;
        }
        $shell =
            <<< EOF
#ding build start
{$shellDingBuildStart}

#build start
#execute shell
{$online->get('ci_run')}

cd \$WORKSPACE
#dockerfile
echo "{$shellDockerfile}" > Dockerfile

cat Dockerfile

#docker build 
docker login {$setting->registryAddress} --username={$setting->registryUsername} --password={$setting->registryPassword}
docker pull {$online->imageBaseUuid}
docker build -t {$online->imageUuid} .
docker push {$online->imageUuid}
docker rmi -f {$online->imageUuid}

#deploy start
{$shellDingDeployStart}

#deploy
{$shellRancherDeploy}

EOF;
        return $shell;
    }


    /**
     * 组装子项目(包括集成项目)xml
     * @param Online $online
     * @return string
     */
    private static function getSubProject(Online $online)
    {
        $scms = [];
        if ($subProjectAddress = $online->get('project_sub_address')) {
            $scms[] = [
                'address' => $subProjectAddress,
                'branch' => $online->get('project_sub_branch'),
                'path' => $online->get('project_sub_path')
            ];
        }
        if ($ciProjectAddress = $online->get('project_ci_address')) {
            $scms[] = [
                'address' => $ciProjectAddress,
                'branch' => $online->get('project_ci_branch'),
                'path' => $online->get('project_ci_path')
            ];
        }
        $subSCM = '';
        foreach ($scms as $item) {
            $subSCM .= <<< EOF
             <hudson.plugins.git.GitSCM plugin="git@3.9.0">
                <configVersion>2</configVersion>
                <userRemoteConfigs>
                    <hudson.plugins.git.UserRemoteConfig>
                        <url>{$item['address']}</url>
                        <credentialsId>c6d0f496-0e3b-4436-940a-2b065d6dfdef</credentialsId>
                    </hudson.plugins.git.UserRemoteConfig>
                </userRemoteConfigs>
                <branches>
                    <hudson.plugins.git.BranchSpec>
                        <name>*/{$item['branch']}</name>
                    </hudson.plugins.git.BranchSpec>
                </branches>
                <doGenerateSubmoduleConfigurations>false</doGenerateSubmoduleConfigurations>
                <submoduleCfg class="list"/>
                <extensions>
                    <hudson.plugins.git.extensions.impl.RelativeTargetDirectory>
                        <relativeTargetDir>{$item['path']}</relativeTargetDir>
                    </hudson.plugins.git.extensions.impl.RelativeTargetDirectory>
                </extensions>
            </hudson.plugins.git.GitSCM>
EOF;
        }
        return $subSCM;
    }

    /**
     * 创建jenkins build job
     * @param Online $online
     * @return bool
     */
    public static function createJob(Online $online)
    {
        $subSCM = self::getSubProject($online);
        $shell = self::createShell($online);
        $shell = htmlspecialchars($shell);
        $xml =
            <<< EOF
<?xml version='1.1' encoding='UTF-8'?>
<project>
    <description>{$online->get('project_desc')}</description>
    <keepDependencies>false</keepDependencies>
    <properties>
        <com.dabsquared.gitlabjenkins.connection.GitLabConnectionProperty plugin="gitlab-plugin@1.5.5">
            <gitLabConnection></gitLabConnection>
        </com.dabsquared.gitlabjenkins.connection.GitLabConnectionProperty>
    </properties>
    <scm class="org.jenkinsci.plugins.multiplescms.MultiSCM" plugin="multiple-scms@0.6">
        <scms>
            <hudson.plugins.git.GitSCM plugin="git@3.9.0">
                <configVersion>2</configVersion>
                <userRemoteConfigs>
                    <hudson.plugins.git.UserRemoteConfig>
                        <url>{$online->get('project_main_address')}</url>
                        <credentialsId>c6d0f496-0e3b-4436-940a-2b065d6dfdef</credentialsId>
                    </hudson.plugins.git.UserRemoteConfig>
                </userRemoteConfigs>
                <branches>
                    <hudson.plugins.git.BranchSpec>
                        <name>*/{$online->get('project_main_branch')}</name>
                    </hudson.plugins.git.BranchSpec>
                </branches>
                <doGenerateSubmoduleConfigurations>false</doGenerateSubmoduleConfigurations>
                <submoduleCfg class="list"/>
                <extensions/>
            </hudson.plugins.git.GitSCM>
            {$subSCM}
        </scms>
    </scm>
    <canRoam>true</canRoam>
    <disabled>false</disabled>
    <blockBuildWhenDownstreamBuilding>false</blockBuildWhenDownstreamBuilding>
    <blockBuildWhenUpstreamBuilding>false</blockBuildWhenUpstreamBuilding>
    <triggers/>
    <concurrentBuild>false</concurrentBuild>
    <builders>
        <hudson.tasks.Shell>
            <command>{$shell}</command>
        </hudson.tasks.Shell>
    </builders>
    <publishers/>
    <buildWrappers>
        <hudson.plugins.ws__cleanup.PreBuildCleanup plugin="ws-cleanup@0.34">
            <deleteDirs>false</deleteDirs>
            <cleanupParameter></cleanupParameter>
            <externalDelete></externalDelete>
        </hudson.plugins.ws__cleanup.PreBuildCleanup>
    </buildWrappers>
</project>
EOF;

        self::$lastXml = $xml;
        if (!self::request("/createItem?name={$online->buildJobName}", 'post', $xml)) {
            G::msg('创建jenkins构建任务失败');
            return false;
        }
        return self::request("/job/{$online->buildJobName}/build");
    }

    /**
     * 获取最近一次的构建的错误信息
     * @param Online $online
     * @return bool|string
     */
    public static function getJobLatestBuildErrorInfo(Online $online)
    {
        if (!$result = self::requestAsJson("/job/{$online->buildJobName}/lastBuild/api/json", 'post')) {
            G::msg('无法连接至jenkins获取最近构建信息');
            return 'NULL';
        }
        G::msg('SUCCESS');
        switch ((string)Arrays::get($result, 'result')) {
            case 'SUCCESS':
                G::msg(self::getJobBuildConsoleInfo($online));
                return false;
            case 'FAILURE':
                return self::getJobBuildConsoleInfo($online);
            default:
                return 'NULL';
        }
    }

    /**
     * 获取某一次的jenkins构建控制台(标准输出)信息
     * @param Online $online
     * @return string
     */
    private static function getJobBuildConsoleInfo(Online $online)
    {
        if (!$result = self::requestAsJson("/job/{$online->buildJobName}/1/consoleText", 'get', null, false)) {
            return '无法获取构建错误信息';
        }
        return $result;
    }

    /**
     * 删除一个任务
     * @param Online $online
     * @return bool
     */
    public static function deleteJob(Online $online)
    {
        if (!self::request("/job/{$online->buildJobName}/doDelete", 'post')) {
            G::msg('删除失败');
            return false;
        }
        return true;
    }

    /**
     * 统一请求(无需请求结果)
     * @param $route
     * @param string $method
     * @param null $data
     * @return bool|array
     */
    private static function request($route, $method = 'post', $data = null)
    {
        $setting = Setting::getInstance();
        $headers = ['Content-Type' => 'text/xml'];
        $jenkinsUrl = explode('://', $setting->jenkinsAddress);
        $url = "{$jenkinsUrl[0]}://{$setting->jenkinsUsername}:{$setting->jenkinsPassword}@{$jenkinsUrl[1]}$route";
        $curl = new LCurl(LCurl::POST_RAW, 5);
        $curl->$method($url, $data, $headers);
        return in_array($curl->httpCode, [200, 201]);
    }

    /**
     * 需要请求结果
     * @param $route
     * @param string $method
     * @param null $data
     * @param bool $asJson
     * @return mixed
     */
    private static function requestAsJson($route, $method = 'post', $data = null, $asJson = true)
    {
        $setting = Setting::getInstance();
        $jenkinsUrl = explode('://', $setting->jenkinsAddress);
        $url = "{$jenkinsUrl[0]}://{$setting->jenkinsUsername}:{$setting->jenkinsPassword}@{$jenkinsUrl[1]}$route";
        $curl = new LCurl(LCurl::POST_RAW, 5);
        $rt = $curl->setJsonResult($asJson)->$method($url, $data);
        return $rt;
    }

    /**
     * @return string
     */
    public static function getLastXml()
    {
        return self::$lastXml;
    }



}