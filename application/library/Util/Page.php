<?php

namespace Util;

use Myaf\Mysql\LDBKernel;
use Myaf\Utils\Arrays;
use Myaf\Utils\PageUtil;


/**
 * 分页工具类
 * @author chenqionghe
 */
class Page
{
    /**
     * 获取分页信息
     *
     * @param LDBKernel $query 查询对象
     * @param array $search 搜索条件
     * @param string $url 跳转地址
     * @return array
     */
    public static function pageList(LDBKernel $query, $search, $url)
    {
        $countQuery = clone $query;
        $info = ['search' => $search, 'prev' => 0, 'next' => 0, 'count' => 0, 'page' => 0, 'pageCount' => 0, 'totalCount' => 0, 'pageSize' => 0, 'list' => []];
        $total = $countQuery->count();
        if (empty($total)) {
            return $info;
        }
        $pageInfo = PageUtil::get(Arrays::get($search, 'page'), $total, Arrays::get($search, 'pageSize', 15));
        $list = $query->limit($pageInfo['offset'], $pageInfo['limit'])->select();
        $pageInfo['list'] = $list;
        $pageInfo['search'] = $search;
        $pageInfo['pageStr'] = PageUtil::create($pageInfo['totalCount'], $pageInfo['pageSize'], $pageInfo['page'], $search)->getPagination($url);
        return $pageInfo;

    }
}