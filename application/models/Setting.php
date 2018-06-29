<?php

use Con\Log;
use Data\Setting;
use Myaf\Core\G;
use Myaf\Pool\Data;
use Myaf\Utils\Arrays;
use Myaf\Validator\Validator;

/**
 * Created by PhpStorm.
 * User: linyang
 * Date: 2018/5/29
 * Time: 下午12:08
 */
class SettingModel
{
    /**
     * 获取所有系统配置项
     * @return bool|array
     */
    public function get()
    {
        return Setting::load();
    }

    /**
     * 更新系统配置参数
     * @param $data array|mixed
     * @return bool
     */
    public function set($data)
    {
        $val = new Validator($data);
        $val->rules([
            ['required', 'jenkins_address'],
            ['required', 'jenkins_username'],
            ['required', 'jenkins_password'],
            ['required', 'rancher_address'],
            ['required', 'rancher_key'],
            ['required', 'rancher_secret'],
            ['required', 'registry_address'],
            ['required', 'registry_username'],
            ['required', 'registry_password'],
            ['required', 'ding'],
            ['required', 'listen_rate'],
            ['required', 'listen_expire'],
            ['required', 'node_cpu_shares'],
            ['required', 'node_memory']
        ]);
        if (!$val->validate()) {
            G::msg($val->errorString());
            return false;
        }

        $table = Data::db()->table('config');
        foreach ($data as $key => $value) {
            if ($table->where(['name' => $key])->update(['value' => $value]) === false) {
                G::msg("{$key} 更新失败");
                return false;
            }
        }
        LogModel::add(Log::SETTING_UPDATE);
        return true;
    }
}