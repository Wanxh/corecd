<?php

use Con\Log;
use Con\Otp;
use Myaf\Core\G;
use Myaf\Mysql\LActiveRecord;
use Myaf\Utils\OtpUtil;
use Myaf\Validator\Validator;
use Query\UserQuery;
use Util\Page;

/**
 * Class UserModel.
 * @property $id
 * @property $username
 * @property $mobile
 * @property $secret
 * @property $turl
 * @property $create_time
 * @property $update_time
 * @property $role
 */
class UserModel extends BaseModel
{
    /**
     * @return mixed|string
     */
    public function tableName()
    {
        return "users";
    }

    /**
     * 自定义查询条件
     *
     * @return \Myaf\Mysql\LActiveQuery|UserQuery
     */
    public function find()
    {
        $activeQuery = new UserQuery($this->_db, $this->trueTableName());
        $activeQuery->setModelClass(get_called_class());
        return $activeQuery;
    }


    /**
     * 获取用户信息
     * @return array
     */
    public function getUserInfo()
    {
        return ['id' => 1, 'username' => 'test', 'password' => 'xx', 'p1' => 'p1', 'p2' => 'p2'];
    }


    /**
     * 获取项目分页列表
     *
     * @param array $search
     * @return array
     */
    public function getPageList($search = [])
    {
        $query = $this->find()->order("role DESC,id DESC")->baseSearch($search);
        return Page::pageList($query, $search, '/users/index');
    }


    /**
     * 用户列表
     *
     * @param $search
     * @return array|LActiveRecord[]|null
     */
    public function getList($search = [])
    {
        $res = $this->find()->basesearch($search)->select();
        if (empty($res)) {
            return [];
        }
        return $res;

    }

    /**
     * 用户名称列表map，key为id,值为名称
     * @return array
     */
    public function getUsers()
    {
        $list = $this->getList();
        return array_column($list, 'username', 'id');
        $res = [];
        foreach ($list as $user) {
            $res[$user['id']] = "{$user['username']}({$user['comment']})";
        }
        return $res;
    }


    /**
     * @param $post
     * @return bool
     */
    public function doCreate($post)
    {
        $val = new Validator($post, $this->attributeLabels());
        $val->rules([
            ['required', ['username', 'secret', 'role']],
            ['mobile', 'mobile'],
        ]);
        if (!$val->validate()) {
            G::msg($val->errorString());
            return false;
        }
        $obj = new self();
        $obj->setAttributes($post);
        $obj->turl = OtpUtil::getOtpAuthUrl($obj->secret, $obj->username . '（' . Otp::MAIL . '）');
        if ($this->isUsernameExist($obj)) {
            G::msg('用户名已存在');
            return false;
        }
        $this->beginTransaction();
        try {
            $this->doTrans($obj->saveWithMsg(), '创建用户失败');
            $this->doTrans(LogModel::add(Log::USER_CREATE, 0));
            $this->commit();
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
        $val = new Validator($post, $this->attributeLabels());
        $val->rules([
            ['required', ['id', 'username', 'secret', 'role']],
            ['mobile', 'mobile'],
        ]);
        if (!$val->validate()) {
            G::msg($val->errorString());
            return false;
        }
        $obj = $this->findById($post['id']);
        if (empty($obj)) {
            G::msg("用户不存在");
            return false;
        }
        $obj->setAttributes($post);
        $obj->turl = OtpUtil::getOtpAuthUrl($obj->secret, $obj->username . '（' . Otp::MAIL . '）');
        if ($this->isUsernameExist($obj)) {
            G::msg('用户名已存在');
            return false;
        }
        $this->beginTransaction();
        try {
            $this->doTrans($obj->saveWithMsg(), '修改用户失败');
            $this->doTrans(LogModel::add(Log::USER_UPDATE, 0, $obj->getAttributesUpdateDetail()));
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            G::msg($e->getMessage());
            return false;
        }
    }


    /**
     * 判断项目是否唯一
     *
     * @param UserModel $user
     * @return mixed
     */
    private function isUsernameExist(UserModel $user)
    {
        $query = $this->find()->andWhere(['username' => $user->username]);
        if (!empty($user->id)) {
            $query->andWhere("id <> " . (int)$user->id);
        }
        return $query->one('id');
    }

    /**
     * 删除
     *
     * @param $id
     * @return bool
     */
    public function del($id)
    {
        $obj = $this->findById($id);
        if (empty($obj)) {
            G::msg("用户不存在");
            return false;
        }

        $this->beginTransaction();
        try {
            $this->doTrans($obj->delete(), '删除用户失败');
            $this->doTrans(LogModel::add(Log::USER_DELETE, 0));
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            G::msg($e->getMessage());
            return false;
        }
    }


    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => '用户名称',
            'mobile' => '手机号',
            'secret' => 'OTM密钥',
            'turl' => 'TOTP URL',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
            'role' => '用户类别',
        ];
    }

    /**
     * 是否是管理员
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role == \Con\Role::USER_ADMIN;
    }

}