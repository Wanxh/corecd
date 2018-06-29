<?php

use Con\Error;
use Myaf\Core\G;
use Myaf\Mysql\LActiveRecord;
use Myaf\Pool\Data;

/**
 * 基类AR模型，指定database, 避免所有的的AR类再写一次database方法
 *
 * Class BaseModel
 */
abstract class BaseModel extends LActiveRecord
{
    /**
     * update的字段变更详情
     * @var array
     */
    protected $attributeUpdateDetail = [];


    /**
     * 默认使用数据库
     *
     * @return bool|\Myaf\Mysql\LDB
     * @throws Exception
     */
    public function database()
    {
        return Data::db('default');
    }


    /***
     * 根据id查询对象
     *
     * @param $id
     * @return bool|static|LActiveRecord
     */
    public function findById($id)
    {
        if (empty($id)) {
            G::msg('id不能为空！');
            return false;
        }
        return $this->find()->where(['id' => (int)$id])->one();
    }


    /**
     * @return bool
     */
    public function saveWithMsg()
    {
        if ($this->save()) {
            G::msg('操作成功');
            return true;
        } else {
            G::msg($this->_db->lastSql());
            return false;
        }
    }

    /**
     * 保存，并设置msg
     * @return bool
     * @throws Exception
     */
    public function deleteWithMsg()
    {
        if ($this->delete()) {
            G::msg('删除成功');
            return true;
        } else {
            G::msg($this->_db->lastError());
            return false;
        }
    }

    /**
     * 判断执行结果, 如果$result为假, 抛出异常
     *
     * @param mixed $result 操作结果
     * @param string $msg 错误信息
     * @throws Exception
     */
    public function doTrans($result, $msg = '')
    {
        if ($result === false) {
            if (G::msg() !== null) {
                $msg = G::msg();
            }
            throw new Exception($msg);
        }
    }


    /**
     * 开启事务
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->_db->beginTransaction();
    }

    /**
     * 提交
     *
     * @return bool
     */
    public function commit()
    {
        return $this->_db->commit();
    }

    /**
     * 回滚
     *
     * @return bool
     */
    public function rollback()
    {
        return $this->_db->rollBack();
    }


    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if (!empty($changedAttributes) && $insert == false) {
            $this->setUpdateAttributesDetail($changedAttributes);
        }
    }

    /**
     * @param array $changedAttributes
     */
    public function setUpdateAttributesDetail($changedAttributes)
    {
        $changeDetail = [];
        foreach ($changedAttributes as $key => $value) {
            if (trim($value) != trim($this->$key)) {
                $changeDetail[$key] = ['before' => $value, 'after' => $this->$key];
            }
        }
        $this->attributeUpdateDetail = $changeDetail;
    }

    /**
     * 获取字段变更详情
     *
     * @return array
     */
    public function getAttributesUpdateDetail()
    {
        return $this->attributeUpdateDetail;
    }

    /**
     * 根据属性查询，不存在创建对象
     *
     * @param $attributes
     * @return BaseModel|bool|mixed
     */
    public function findByAttributesOrNew($attributes)
    {
        $obj = $this->find()->where($attributes)->one();
        if (empty($obj)) {
            $obj = new static();
            $obj->setAttributes($attributes);
            $obj->setAttributes(['create_time' => date("Y-m-d H:i:s")]);
        }
        $obj->setAttributes(['update_time' => date("Y-m-d H:i:s")]);
        return $obj;
    }


    /**
     * 批量插入
     *
     * @param array $insertData 多维数组 如
     * [
     *   [ 'id' => 123, 'name' => 'test1' ],
     *   [ 'id' => 124, 'name' => 'test2' ],
     *   ...
     * ]
     * @return bool
     */
    public function batchInsert(array $insertData)
    {
        return $this->find()->insertMulti(array_keys($insertData[0]), array_values($insertData));
    }

}