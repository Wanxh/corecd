<?php

use Api\Jenkins;
use Api\Online;
use Api\Rancher;
use Auth\Auth;
use Con\Cache;
use Con\Log;
use Con\Project;
use Data\Setting;
use Myaf\Core\G;
use Myaf\Pool\Data;
use Myaf\Utils\Arrays;
use Myaf\Validator\Validator;
use Query\ProjectQuery;
use Util\Ding;
use Util\Page;


/**
 * Class ProjectModel
 * @property $id
 * @property $uid
 * @property $audit_uid
 * @property $project_name
 * @property $project_desc
 * @property $project_use
 * @property $project_main_address
 * @property $project_main_branch
 * @property $project_sub_address
 * @property $project_sub_branch
 * @property $project_sub_path
 * @property $batch_size
 * @property $online_num
 * @property $ding
 * @property $domain
 * @property $domain_second_path
 * @property $project_ci_address
 * @property $project_ci_branch
 * @property $project_ci_path
 * @property $ci_dockerfile
 * @property $ci_run_file
 * @property $ci_run
 * @property $env_id
 * @property $env_name
 * @property $env_desc
 * @property $use_time
 * @property $update_time
 * @property $create_time
 * @property $status
 * @property $state
 */
class ProjectModel extends BaseModel
{
    /**
     * 自定义查询条件
     *
     * @return \Myaf\Mysql\LActiveQuery|ProjectQuery
     */
    public function find()
    {
        $activeQuery = new ProjectQuery($this->_db, $this->trueTableName());
        $activeQuery->setModelClass(get_called_class());
        return $activeQuery;
    }


    /**
     * 表名
     *
     * @return mixed|string
     */
    public function tableName()
    {
        return "projects";
    }

    /**
     * 获取项目分页列表
     *
     * @param array $search
     * @return array
     */
    public function getPageList($search = [])
    {
        $query = $this->find()->order("status ASC, project_name DESC")->baseSearch($search);
        return Page::pageList($query, $search, '/project/index');
    }

    /**
     * 根据项目id获取
     *
     * @param array $ids
     * @return array|\Myaf\Mysql\LActiveRecord[]|null
     */
    public function getProjectsByIds($ids = [])
    {
        $ids = array_unique($ids);
        if (empty($ids)) {
            return [];
        }
        $list = $this->find()->asArray()->where(['id' => $ids])->select();
        if (empty($list)) {
            return [];
        }
        return Arrays::lists($list, 'id');
    }

    /**
     * 获取待审核列表
     *
     * @param array $search
     * @return array|\Myaf\Mysql\LActiveRecord[]|null
     */
    public function getAuditList($search = [])
    {
        $query = $this->find()->order("id DESC")->waitAudit()->baseSearch($search);
        $list = $query->select();
        return empty($list) ? [] : $list;
    }


    /**
     * 获取项目
     *
     * @param $id
     * @return bool|ProjectModel
     */
    public function getProject($id)
    {
        if (empty($id)) {
            G::msg("项目id不能为空！");
            return false;
        }
        $project = $this->findById($id);
        if (empty($project)) {
            G::msg("项目不存在！");
            return false;
        }
        return $project;
    }


    /**
     * @param $post
     * @return bool
     */
    public function doCreate($post)
    {
        try {
            $val = new Validator($post, $this->attributeLabels());
            $val->rules([
                ['required', ['env_id', 'project_use', 'project_name', 'project_desc', 'project_main_address', 'project_main_branch', 'batch_size', 'ding']],
            ]);
            if ($post['project_use'] == Project::USE_WEB) {
                $val->rule(['required', 'domain']);
            }
            if (!$val->validate()) {
                throw new Exception($val->errorString());
            }

            //拼接内网地址
            if ($post['project_use'] == Project::USE_INNER) {
                $post['domain'] = $post['project_name'] . '.' . Project::USE_INNER;
            }
            if (!$post['node_memory']) {
                $post['node_memory'] = 0;
            }
            $obj = new self();
            $obj->setAttributes($post);
            $this->checkProjectExistOrException($obj);
        } catch (Exception $e) {
            G::msg($e->getMessage());
            return false;
        }

        $obj->uid = Auth::userId();
        $obj->env_name = EnvModel::getEnvName($obj->env_id);
        $obj->status = Project::S_WAIT;

        //管理员可以选择多个所属用户
        if (Auth::isAdmin()) {
            $uids = Arrays::get($post, 'uids');
        } else {
            $uids = [$obj->uid];
        }

        $this->beginTransaction();
        try {
            $this->doTrans($obj->saveWithMsg(), '项目申请失败');
            $this->doTrans(LogModel::add(Log::PROJECT_CREATE, $obj->id));
            if (!empty($uids)) {
                $this->doTrans((new UserProjectModel())->createProjectUids($obj, $uids), '创建用户与项目关联关系失败');
            }
            $this->commit();
            Setting::ding("项目({$obj->project_name})提交申请，操作人:" . Auth::userName());
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            G::msg($e->getMessage());
            return false;
        }
    }


    /**
     * @param $post
     * @return bool
     */
    public function doUpdate($post)
    {
        try {
            $val = new Validator($post, $this->attributeLabels());
            $val->rules([
                ['required', ['id', 'env_id', 'project_use', 'project_name', 'project_desc', 'project_main_address', 'project_main_branch', 'ding']],
            ]);
            if ($post['project_use'] == Project::USE_WEB) {
                $val->rule(['required', 'domain']);
            }
            if (!$val->validate()) {
                throw new Exception($val->errorString());
            }
            $obj = $this->findById($post['id']);
            if (empty($obj)) {
                throw new Exception("项目不存在");
            }
            //拼接内网地址
            if ($post['project_use'] == Project::USE_INNER) {
                $post['domain'] = $post['project_name'] . '.' . Project::USE_INNER;
            }
            $obj->setAttributes($post);
            $this->checkProjectExistOrException($obj);
        } catch (Exception $e) {
            G::msg($e->getMessage());
            return false;
        }

        $obj->env_name = EnvModel::getEnvName($obj->env_id);
        $obj->status = Project::S_WAIT;

        //管理员可以选择多个所属用户
        if (Auth::isAdmin()) {
            $uids = Arrays::get($post, 'uids');
        }

        $this->beginTransaction();
        try {
            $this->doTrans($obj->saveWithMsg(), '更新项目失败');
            $this->doTrans(LogModel::add(Log::PROJECT_UPDATE, $obj->id, $obj->getAttributesUpdateDetail()));
            if (!empty($uids)) {
                $this->doTrans((new UserProjectModel())->updateProjectUids($obj, $uids), '更新用户与项目关联关系失败');
            }
            $this->commit();
            Setting::ding("项目({$obj->project_name})被修改，操作人:" . Auth::userName());
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            G::msg($e->getMessage());
            return false;
        }
    }


    /**
     * 判断域名和路径是否唯一
     *
     * @param ProjectModel $project
     * @return bool|mixed
     */
    private function isDomainPathExist(ProjectModel $project)
    {
        $query = $this->find()->stateNormal()->andWhere([
            'domain' => $project->domain,
            'domain_second_path' => $project->domain_second_path,
            'env_id' => $project->env_id,
        ])->andWhere(['project_use' => Project::USE_WEB]);
        if (!empty($project->id)) {
            $query->andWhere("id <> " . (int)$project->id);
        }
        return $query->one('id');
    }

    /**
     * 判断项目是否唯一
     *
     * @param ProjectModel $project
     * @return bool|mixed
     */
    private function isProjectExist(ProjectModel $project)
    {
        $query = $this->find()->stateNormal()->andWhere([
            'project_name' => $project->project_name,
            'env_id' => $project->env_id,
        ])->andWhere(['project_use' => Project::USE_WEB]);
        if (!empty($project->id)) {
            $query->andWhere("id <> " . (int)$project->id);
        }
        return $query->one('id');
    }


    /**
     * 字段标签
     *
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'env_id' => '部署环境',
            'project_name' => '项目名称',
            'project_desc' => '项目描述',
            'domain' => '域名',
            'batch_size' => '节点数量',
            'project_main_address' => '主项目地址',
            'project_main_branch' => '主干分支',
            'ding' => '钉钉机器人',
        ];
    }


    /**
     * 审核通过
     *
     * @param $id
     * @return bool
     */
    public function passProject($id)
    {
        try {
            if (empty($id)) {
                throw new Exception('id不能为空');
            }
            $obj = $this->findById($id);
            if (empty($obj)) {
                throw new Exception("项目不存在");
            }
            if ($obj->isPass()) {
                throw new Exception("项目已通过");
            }
            if (!$obj->isWaitCheck()) {
                throw new Exception("项目非审核状态");
            }
        } catch (Exception $e) {
            G::msg($e->getMessage());
            return false;
        }

        $obj->status = Project::S_ACCESS;
        $obj->audit_uid = Auth::userId();

        $this->beginTransaction();
        try {
            $this->doTrans($obj->saveWithMsg(), '更新失败');
            $this->doTrans(LogModel::add(Log::PROJECT_PASS, $obj->id, $obj->getAttributesUpdateDetail()));
            Ding::send($obj->ding, "项目({$obj->project_name})审核通过，操作人:" . Auth::userName());
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            G::msg($e->getMessage());
            return false;
        }
    }

    /**
     * 审核通过
     *
     * @param $id
     * @return bool
     */
    public function denyProject($id)
    {
        try {
            if (empty($id)) {
                throw new Exception('id不能为空');
            }
            $obj = $this->findById($id);
            if (empty($obj)) {
                throw new Exception("项目不存在");
            }
            if ($obj->isDeny()) {
                throw new Exception("项目已拒绝");
            }
            if (!$obj->isWaitCheck()) {
                throw new Exception("项目非审核状态");
            }
        } catch (Exception $e) {
            G::msg($e->getMessage());
            return false;
        }

        $obj->status = Project::S_DENIED;
        $obj->audit_uid = Auth::userId();
        $this->beginTransaction();
        try {
            $this->doTrans($obj->saveWithMsg(), '更新失败');
            $this->doTrans(LogModel::add(Log::PROJECT_DENY, $obj->id, $obj->getAttributesUpdateDetail()));
            Ding::send($obj->ding, "项目({$obj->project_name})审核拒绝，操作人:" . Auth::userName());
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            G::msg($e->getMessage());
            return false;
        }
    }


    /**
     * 待审核
     * @return bool
     */
    public function isWaitCheck()
    {
        return $this->status == Project::S_WAIT;
    }

    /**
     * 审核通过
     * @return bool
     */
    public function isPass()
    {
        return $this->status == Project::S_ACCESS;
    }


    /**
     * 审核通过
     * @return bool
     */
    public function isDeny()
    {
        return $this->status == Project::S_DENIED;
    }

    /**
     * 执行项目上线
     *
     * @param $id
     * @return bool
     * @throws Exception
     */
    public function online($id)
    {
        try {
            if (!$id) {
                throw new Exception('上线失败,缺少参数id');
            }
            if (!Setting::getInstance()) {
                throw new Exception('上线失败,获取系统配置失败');
            }
            //检测是否存在该项目
            if (!$project = (new ProjectModel())->findById($id)) {
                throw new Exception('上线失败,未找到该项目');
            }
            if ($project->status == Project::S_ONLINE_ING) {
                throw new Exception('项目正在线，请等待上线完成后操作');
            }
            $createTime = time();
            //生成上线实例
            if (!$online = Online::create($project->toArray())) {
                return false;
            }
            if (!$this->isListenShellStart()) {
                Setting::ding("未运行上线脚本: nohup php /var/www/html/bin/cli console/listen/index &");
                throw new Exception("上线脚本未能正常运行，请联系管理员");
            }

        } catch (Exception $e) {
            G::msg($e->getMessage());
            return false;
        }
        Setting::ding("项目({$project->project_name})上线，操作人:" . Auth::userName());
        try {
            //生成上线历史
            $history = [
                'uid' => Auth::userId(),
                'pid' => $id,
                'md5' => $online->md5,
                'create_time' => date('Y-m-d H:i:s', $createTime),
                'online' => serialize($online)
            ];
            $table = Data::db()->table('history');
            if (!$insertId = $table->insert($history)) {
                throw new Exception('上线失败,无法生成上线历史');
            }

            //更新上线时间
            if (!Data::db()->table("projects")->where(['id' => $id])->update(['use_time' => date('Y-m-d H:i:s'), 'status' => Project::S_ONLINE_ING])) {
                throw new Exception('上线失败,无法更新上线时间与状态');
            }
            if (!Jenkins::createJob($online)) {
                throw new Exception(G::msg());
            }
            //记录上线日志
            LogModel::add(Log::PROJECT_ONLINE, $id, ['xml' => Jenkins::getLastXml()]);
            //更新上线key至redis,以便listen进程进行上线检测
            if (!Data::redis()->sAdd(Cache::KEY_ONLINE, "{$online->md5}@{$createTime}")) {
                G::msg("上线已完成操作,但无法进行监控实时进度,请立即联系管理员,此次上线DM5:{$online->md5}");
                return false;
            }
            //录入缓存
            return true;
        } catch (Exception $e) {
            //上线失败，更新项目状态
            Data::db()->table('projects')->update(['status' => Project::S_ONLINE_FAIL]);
            G::msg($e->getMessage());
            return false;
        }

    }

    /**
     * 更新节点数量
     *
     * @param $post
     * @return bool
     */
    public function updateBatchSize($post)
    {
        try {
            $val = new Validator($post, $this->attributeLabels());
            $val->rules([
                ['required', ['id', 'batch_size']],
                ['compare', 'batch_size', '<=', '5'],
                ['compare', 'batch_size', '>=', '1'],
            ]);
            if (!$val->validate()) {
                throw new Exception($val->errorString());
            }
            $project = $this->findById($post['id']);
            if (empty($project)) {
                throw new Exception("项目不存在！");
            }
            if ($project->online_num == 0) {
                throw new Exception("项目未上过线，无法修改节点，请使用上线功能");
            }
            if (!$project->status == Project::S_ONLINE_OK) {
                throw new Exception("项目未处于上线成功状态,请在上线成功后操作");
            }
            $batchSize = (int)$post['batch_size'];
            if ($project->batch_size == $batchSize) {
                throw new Exception("当前节点数已经是{$batchSize}");
            }
            $online = Online::create($project->toArray());
            //检测服务是否存在
            $service = Rancher::getService($online);
            if (empty($service)) {
                throw new Exception('服务不存在！');
            }
            //执行修改节点
            $online->batchSize = $batchSize;
            if (!Rancher::changeServiceScale($online, $batchSize)) {
                throw new Exception("修改节点失败！");
            }
        } catch (Exception $e) {
            G::msg($e->getMessage());
            return false;
        }

        $this->beginTransaction();
        try {
            Setting::ding("项目({$project->project_name})节点数从{$project->batch_size}修改为{$batchSize}，操作人:" . Auth::userName());
            $project->batch_size = $batchSize;
            $this->doTrans($project->saveWithMsg(), '更新失败');
            $this->doTrans(LogModel::add(Log::PROJECT_UPDATE_BATCH_SIZE, $project->id, $project->getAttributesUpdateDetail()));
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            G::msg($e->getMessage());
            return false;
        }
    }


    /**
     * 删除项目
     *
     * @param $id
     * @return bool
     */
    public function del($id)
    {
        try {
            $project = $this->findById($id);
            if (empty($project)) {
                throw new Exception("项目不存在！");
            }
            $online = Online::create($project->toArray());
            $service = Rancher::getService($online);
            if (empty($service)) {
                throw new Exception("线上服务不存在！");
            }
        } catch (Exception $e) {
            G::msg($e->getMessage());
            return false;
        }

        $project->state = Project::STAT_DELETE;
        $this->beginTransaction();
        try {
            $this->doTrans(LogModel::add(Log::PROJECT_DELETE, $project->id));
            $this->doTrans($project->saveWithMsg(), '删除失败');
            Rancher::deleteService($online);
            $this->commit();
            Setting::ding("项目({$project->project_name})被删除,操作人:" . Auth::userName());
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            G::msg($e->getMessage());
            return false;
        }
    }


    /**
     * 检测项目是否存在，存在抛异常
     *
     * @param ProjectModel $obj
     * @throws Exception
     */
    private function checkProjectExistOrException(ProjectModel $obj)
    {
        if ($this->isProjectExist($obj)) {
            throw new Exception("项目{$obj->project_name}已存在！环境" . EnvModel::getEnvName($obj->env_id));
        }
        if ($this->isDomainPathExist($obj)) {
            throw new Exception("域名{$obj->domain}和二级目录{$obj->domain_second_path}必须唯一！环境" . EnvModel::getEnvName($obj->env_id));
        }
    }


    /**
     * 更新节点数量
     *
     * @param $post
     * @return bool
     */
    public function changeOwner($post)
    {
        try {
            $val = new Validator($post, $this->attributeLabels());
            $val->rules([
                ['required', ['id', 'uid']],
            ]);
            if (!$val->validate()) {
                throw new Exception($val->errorString());
            }
            $project = $this->findById($post['id']);
            if (empty($project)) {
                throw new Exception("项目不存在！");
            }
        } catch (Exception $e) {
            G::msg($e->getMessage());
            return false;
        }


        $project->uid = (int)$post['uid'];
        $this->beginTransaction();
        try {
            $this->doTrans($project->saveWithMsg(), '转移失败');
            $this->doTrans(LogModel::add(Log::PROJECT_CHANGE_OWNER, $project->id, $project->getAttributesUpdateDetail()));
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            G::msg($e->getMessage());
            return false;
        }
    }

    /**
     * 判断listen的shell是否在执行
     *
     * @return bool
     */
    private function isListenShellStart()
    {
        $checkShell = 'ps aux|grep "bin/cli\s*console/listen/index"|grep -v grep';
        exec($checkShell, $out, $execCode);
        if (empty($out)) {
            return false;
        }
        return true;
    }


    /**
     * 运行上线脚本 TODO nohup必须回车，要不会卡死不能返回，先不用
     *
     * @return bool
     */
    private function listenShellStart()
    {
        $shell = 'php /var/www/html/bin/cli console/listen/index &';
        exec($shell, $out, $execCode);
        if ($this->isListenShellStart()) {
            return true;
        }
        return false;
    }


    /**
     * @return array|bool|\Myaf\Mysql\LActiveRecord[]|null
     */
    public function myProjects($uid)
    {
        if (empty($uid)) {
            return [];
        }
        $projecIds = (new UserProjectModel())->getProjectIdsByUid($uid);
        if (empty($projecIds)) {
            return [];
        }
        $list = $this->find()->where(['id' => $projecIds])->stateNormal()->order("env_id ASC")->select();
        if (empty($list)) {
            return [];
        }
        return $list;
    }
}