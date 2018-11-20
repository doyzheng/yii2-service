<?php

namespace doyzheng\yii2service;

use Yii;
use yii\base\Component;
use yii\base\UnknownClassException;

/**
 * yii2 数据层服务类
 * Class Service
 * @package doyzheng\yii2service
 */
class Service extends Component
{
    
    /**
     * @var string 模型对象名称
     */
    public $model;
    
    /**
     * @throws UnknownClassException
     */
    public function init()
    {
        if (!class_exists($this->model)) {
            throw new UnknownClassException($this->model);
        }
    }
    
    /**
     * @return \yii\db\ActiveRecord
     */
    public function model()
    {
        return new $this->model;
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function find()
    {
        return call_user_func_array($this->model . '::find', []);
    }
    
    /**
     * 获取模型主键
     * @return string
     */
    public function getPk()
    {
        $attr = $this->model()->primaryKey();
        return array_shift($attr);
    }
    
    /**
     * 开始事务操作
     * @param null $isolationLevel
     * @return \yii\db\Transaction
     */
    public function beginTransaction($isolationLevel = null)
    {
        return Yii::$app->db->beginTransaction($isolationLevel);
    }
    
    /**
     * 添加数据
     * @param array $data
     * @return int
     */
    public function add($data)
    {
        $model = $this->model();
        $model->setAttributes($data);
        if ($model->save()) {
            $pk = $this->getPk();
            return $model->{$pk};
        }
        $this->setErrors($model->getErrors());
        return 0;
    }
    
    /**
     * 获取单条数据
     * @param array  $where
     * @param string $fields
     * @param mixed  $order
     * @return array
     */
    public function get($where, $fields = '', $order = '')
    {
        return $this->find()->where($where)->select($fields)->orderBy($order)->asArray()->one();
    }
    
    /**
     * 获取全部数据
     * @param array  $where
     * @param string $fields
     * @param string $order
     * @return array
     */
    public function getAll($where, $fields = '', $order = '')
    {
        $order = $order ? $order : $this->getDefaultOrder();
        return $this->find()->where($where)->select($fields)->orderBy($order)->asArray()->all();
    }
    
    /**
     * 分页查询
     * @param array  $where
     * @param string $page
     * @param string $limit
     * @param string $fields
     * @param string $order
     * @return array
     */
    public function getPage($where, $page, $limit = '', $fields = '', $order = '')
    {
        if ($page < 1) {
            $page = 1;
        }
        $order = $order ? $order : $this->getDefaultOrder();
        return $this->find()->where($where)->offset(($page - 1) * $limit)->limit($limit)->select($fields)->orderBy($order)->asArray()->all();
    }
    
    /**
     * 单条更新
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function update($where, $data)
    {
        $model = $this->find()->where($where)->one();
        if ($model) {
            $model->setAttributes($data);
            if ($model->save()) {
                return true;
            }
            $this->setErrors($model->getErrors());
        }
        return false;
    }
    
    /**
     * 批量更新
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function updateAll($where, $data)
    {
        $models = $this->find()->where($where)->all();
        if ($models) {
            try {
                $tr = $this->beginTransaction();
                foreach ($models as $model) {
                    $model->setAttributes($data);
                    if (!$model->save()) {
                        $tr->rollBack();
                        $this->setErrors($model->getErrors());
                        return false;
                    }
                }
                $tr->commit();
                return true;
            } catch (\Exception $exception) {
                $this->setErrors($exception);
            }
        }
        return false;
    }
    
    /**
     * 单条删除
     * @param array $where
     * @return bool
     */
    public function delete($where)
    {
        $model = $this->find()->where($where)->select($this->getPk())->one();
        if ($model) {
            try {
                if (!$model->delete()) {
                    $this->setErrors($model->getErrors());
                    return false;
                }
                return true;
            } catch (\Throwable $exception) {
                $this->setErrors($exception);
            }
        }
        return false;
    }
    
    /**
     * 批量删除
     * @param array $where
     * @param array $params
     * @return bool
     */
    public function deleteAll($where, $params = [])
    {
        try {
            return $this->model()->deleteAll($where, $params);
        } catch (\Throwable $exception) {
            $this->setErrors($exception);
        }
        return false;
    }
    
    /**
     * 统计符合统计记录数量
     * @param array  $where
     * @param string $fields
     * @return int
     */
    public function count($where, $fields = '*')
    {
        return $this->find()->where($where)->count($fields);
    }
    
    /**
     * 统计字段合计
     * @param array  $where
     * @param string $fields
     * @return int
     */
    public function sum($where, $fields)
    {
        return $this->find()->where($where)->sum($fields);
    }
    
    /**
     * @var mixed 错误信息
     */
    private static $errors = [];
    
    /**
     * 设置错误信息
     * @return mixed
     */
    public function getErrors()
    {
        return self::$errors;
    }
    
    /**
     * @param mixed $error
     * @return bool
     */
    public function setErrors($error = '')
    {
        self::$errors[] = $error;
        return true;
    }
    
    /**
     * 获取默认排序
     * @return string
     */
    public function getDefaultOrder()
    {
        return $this->getPk() . ' DESC';
    }
    
}

