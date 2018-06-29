<?php

namespace Query;

use Auth\Auth;
use Con\Role;
use Myaf\Mysql\LActiveQuery;
use UserProjectModel;


/**
 * Class HistryQuery
 * @package Query
 */
class HistoryQuery extends LActiveQuery
{
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
        return $this->andWhere(['pid' => $projectIds]);
    }


}