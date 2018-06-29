<?php

namespace Query;

use Auth\Auth;
use Con\Project;
use Myaf\Mysql\LActiveQuery;
use Myaf\Utils\Arrays;
use UserProjectModel;


/**
 * ProjectQuery.php 2018年06月06日 下午2:02
 * @author chenqionghe
 */
class ProjectQuery extends LActiveQuery
{

    /**
     * @return ProjectQuery
     */
    public function stateNormal()
    {
        return $this->andWhere(['state' => Project::STAT_NORMAL]);
    }

    /**
     * @return ProjectQuery
     */
    public function stateDelete()
    {
        return $this->andWhere(['state' => Project::STAT_DELETE]);
    }


    /**
     * @param $search
     * @return ProjectQuery
     */
    public function baseSearch($search)
    {
        return $this->projectIds()
            ->stateNormal()
            ->id(Arrays::get($search, 'id'))
            ->envId(Arrays::get($search, 'env_id'))
            ->projectName(Arrays::get($search, 'project_name'))
            ->projectUse(Arrays::get($search, 'project_use'))
            ->projectMainAddress(Arrays::get($search, 'project_main_address'))
            ->domain(Arrays::get($search, 'domain'))
            ->domainSecondPath(Arrays::get($search, 'domain_second_path'));
    }


    /**
     * @return ProjectQuery
     */
    public function waitAudit()
    {
        return $this->where(['status' => Project::S_WAIT]);
    }

    /**
     * @param $value
     * @return $this|ProjectQuery
     */
    public function projectUse($value)
    {
        if (empty($value)) {
            return $this;
        }
        return $this->andWhere(['project_use' => $value]);
    }

    /**
     * @param $value
     * @return $this|ProjectQuery
     */
    public function id($value)
    {
        if (empty($value)) {
            return $this;
        }
        return $this->andWhere(['id' => (int)$value]);
    }

    /**
     * @param $value
     * @return $this|ProjectQuery
     */
    public function projectName($value)
    {
        if (empty($value)) {
            return $this;
        }
        return $this->andWhere("`project_name` like '%{$value}%'");
    }

    /**
     * @param $value
     * @return $this|ProjectQuery
     */
    public function projectMainAddress($value)
    {
        if (empty($value)) {
            return $this;
        }
        return $this->andWhere("`project_main_address` like '%{$value}%'");
    }

    /**
     * @param $value
     * @return $this|ProjectQuery
     */
    public function domain($value)
    {
        if (empty($value)) {
            return $this;
        }
        return $this->andWhere("`domain` like '%{$value}%'");
    }

    /**
     * @param $value
     * @return $this|ProjectQuery
     */
    public function domainSecondPath($value)
    {
        if (empty($value)) {
            return $this;
        }
        return $this->andWhere("`domain_second_path` like '%{$value}%'");
    }

    /**
     * @param $value
     * @return $this|ProjectQuery
     */
    public function envId($value)
    {
        if (empty($value)) {
            return $this;
        }
        return $this->andWhere(['env_id' => $value]);
    }

    /**
     * 筛选用户可以查看的项目
     * @return $this|ProjectQuery
     */
    public function projectIds()
    {
        //超管不限制
        if (Auth::isAdmin()) {
            return $this;
        }
        $uid = Auth::userId();
        $projectIds = (new UserProjectModel())->getProjectIdsByUid($uid);
        //项目不让查看
        if (empty($projectIds)) {
            return $this->andWhere("1=0");
        }
        return $this->andWhere(['id' => $projectIds]);
    }

}