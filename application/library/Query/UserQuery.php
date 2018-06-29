<?php

namespace Query;

use Myaf\Mysql\LActiveQuery;
use Myaf\Utils\Arrays;


/**
 * UserQuery.php 2018年06月06日 下午2:02
 * @author chenqionghe
 */
class UserQuery extends LActiveQuery
{

    /**
     * @param $search
     * @return UserQuery
     */
    public function baseSearch($search = [])
    {
        return $this->username(Arrays::get($search, 'username'))
            ->role(Arrays::get($search, 'role'));
    }


    /**
     * @param $value
     * @return $this|UserQuery
     */
    public function role($value)
    {
        if ($value === null || $value === '') {
            return $this;
        }
        return $this->andWhere(['role' => (int)$value]);
    }

    /**
     * @param $value
     * @return $this|UserQuery
     */
    public function username($value)
    {
        if (empty($value)) {
            return $this;
        }
        return $this->andWhere("`username` like '%{$value}%'");
    }


}