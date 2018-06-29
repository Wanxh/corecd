<?php

use Api\Rancher;
use Myaf\Core\G;
use Myaf\Utils\Arrays;


/**
 * Class EnvModel
 */
class EnvModel
{
    /**
     * 获取所有的env的map
     * [
     *    'env_id' => 'env名称'
     * ]
     * @return array
     */
    public static function getList()
    {
        if (!$data = Rancher::getEnvData()) {
            return [];
        }
        $newData = [];
        foreach ($data as $key => $value) {
            $newData[$key] = Arrays::get($value, 'name') . '(' . Arrays::get($value, 'description') . ')';
        }
        return $newData;
    }


    /**
     * 获取env的描述
     *
     * @param $envId
     * @return mixed|null
     */
    public static function getEnvName($envId)
    {
        $list = self::getList();
        return Arrays::get($list, $envId);
    }
}