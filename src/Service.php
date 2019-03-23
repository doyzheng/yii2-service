<?php

namespace doyzheng\yii2service;

use Yii;
use yii\base\UnknownClassException;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * yii2 数据层服务类
 * Class Service
 * @package doyzheng\yii2service
 */
class Service
{
    
    /**
     * @var string 模型对象名称
     */
    public $model;
    
    /**
     * @var bool 查询结果返回数组
     */
    public $asArray;
    
    /**
     * @var array 基础查询条件
     */
    public $baseWhere;
    
    /**
     * @var mixed 错误信息
     */
    private static $errors = [];
    
    /**
     * 构造方法
     * Service constructor.
     * @param array $config
     * @throws UnknownClassException
     */
    public function __construct($config = [])
    {
        foreach ($config as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
        $this->init();
    }
    
    /**
     * 初始化
     * @throws UnknownClassException
     */
    public function init()
    {
        if (!class_exists($this->model)) {
            throw new UnknownClassException($this->model);
        }
    }
    
    /**
     * 设置模型对象
     * @param ActiveRecord $model
     * @return bool
     */
    public function setModel($model)
    {
        $this->model = $model;
        return true;
    }
    
    /**
     * 获取模型对象
     * @return ActiveRecord
     */
    public function getModel()
    {
        return new $this->model;
    }
    
    /**
     * 获取查询对象
     * @return \yii\db\ActiveQuery
     */
    public function find()
    {
        return call_user_func_array($this->model . '::find', []);
    }
    
    /**
     * 查询结果返回数组
     * @param bool $value
     */
    public function asArray($value = false)
    {
        $this->asArray = $value;
    }
    
    /**
     * 获取查询条件
     * @param $where
     */
    public function getWhere($where)
    {
        $condition[] = 'and';
        if ($where) {
            $condition[] = $where;
        }
        if ($baseWhere = $this->getBaseWhere()) {
            $condition[] = $baseWhere;
        }
        return $condition;
    }
    
    /**
     * @return array 基础查询条件
     */
    public function getBaseWhere()
    {
        return $this->baseWhere;
    }
    
    /**
     * 数组基础查询条件
     * @param array $where
     */
    public function setBaseWhere($where)
    {
        $this->baseWhere = $where;
    }
    
    /**
     * 开始事务
     * @return \yii\db\Transaction
     */
    public function beginTransaction()
    {
        return Yii::$app->db->beginTransaction();
    }
    
    /**
     * 获取模型主键
     * @return string
     */
    public function getPk()
    {
        $attr = $this->getModel()->primaryKey();
        return array_shift($attr);
    }
    
    /**
     * 获取错误信息
     * @return mixed
     */
    public function getErrors()
    {
        return self::$errors;
    }
    
    /**
     * 设置错误信息
     * @param mixed $error
     * @return bool
     */
    public function setErrors($error = '')
    {
        self::$errors[] = $error;
        return true;
    }
    
    /**
     * 获取查询对象
     * @param array  $where
     * @param string $fields
     * @param string $order
     * @param bool   $asArray
     * @return \yii\db\ActiveQuery
     */
    public function getQuery($where = [], $fields = '', $order = '', $asArray = false)
    {
        $activeQuery = $this->find()->where($this->getWhere($where));
        if ($fields) {
            $activeQuery->select($fields);
        }
        if ($order) {
            $activeQuery->orderBy($order);
        }
        if ($asArray) {
            $activeQuery->asArray($asArray);
        }
        return $activeQuery;
    }
    
    /**
     * 获取默认排序方式
     * @return string
     */
    public function getDefaultOrder()
    {
        return $this->getPk() . ' DESC';
    }
    
    /**
     * 添加数据
     * @param array $data
     * @return int
     */
    public function add($data)
    {
        $model = $this->getModel();
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
     * @return ActiveRecord
     */
    public function get($where = [], $fields = '', $order = '')
    {
        $asArray = is_string($fields) && stripos($fields, ' as ') !== false;
        return $this->getQuery($where, $fields, $order, $asArray)->one();
    }
    
    /**
     * 获取全部数据
     * @param array  $where
     * @param string $fields
     * @param string $order
     * @return ActiveRecord[]
     */
    public function getAll($where = [], $fields = '', $order = '')
    {
        $asArray = is_string($fields) && stripos($fields, ' as ') !== false;
        $order = $order ? $order : $this->getDefaultOrder();
        return $this->getQuery($where, $fields, $order, $asArray)->all();
    }
    
    /**
     * 分页查询
     * @param array  $where
     * @param string $page
     * @param string $limit
     * @param string $fields
     * @param string $order
     * @return ActiveRecord[]
     */
    public function getPage($where = [], $page = '1', $limit = '', $fields = '', $order = '')
    {
        $page = $page < 1 ? 1 : $page;
        $limit = $limit < 1 ? 1 : $limit;
        $offset = ($page - 1) * $limit;
        $order = $order ? $order : $this->getDefaultOrder();
        return $this->getQuery($where, $fields, $order)->offset($offset)->limit($limit)->all();
    }
    
    /**
     * 单条更新
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function update($where, $data)
    {
        $model = $this->getQuery($where)->one();
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
        $models = $this->getQuery($where)->all();
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
    
    /**单条删除
     * @param array $where
     * @return bool
     */
    public function delete($where)
    {
        $model = $this->getQuery($where, $this->getPk())->one();
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
            return $this->getModel()->deleteAll($where, $params);
        } catch (\Throwable $exception) {
            $this->setErrors($exception);
        }
        return false;
    }
    
    /**
     * 统计符合条件记录数量
     * @param array  $where
     * @param string $fields
     * @return int
     */
    public function count($where = [], $fields = '*')
    {
        return (int)$this->getQuery($where)->count($fields);
    }
    
    /**
     * 返回指定列值的和
     * @param array  $where
     * @param string $fields
     * @return int
     */
    
    /**
     * @param array $where
     * @param mixed $fields
     * @return int
     */
    public function sum($where, $fields)
    {
        return (int)$this->getQuery($where)->sum($fields);
    }
    
    /**
     * 返回指定列值的最小值
     * @param array  $where
     * @param string $field
     * @return int
     */
    public function min($where, $field)
    {
        return (int)$this->getQuery($where)->min($field);
    }
    
    /**
     * 返回指定列值的最大值
     * @param array  $where
     * @param string $field
     * @return mixed
     */
    public function max($where, $field)
    {
        return (int)$this->getQuery($where)->max($field);
    }
    
    /**
     * 字段值增长
     * @param array        $where 条件
     * @param string|array $field 字段名
     * @param int          $step  增长值
     * @return bool
     */
    public function inc($where, $field, $step = 1)
    {
        $fields = is_string($field) ? explode(',', $field) : $field;
        $upData = [];
        foreach ($fields as $field => $val) {
            if (is_numeric($field)) {
                $field = $val;
            } else {
                $step = $val;
            }
            $upData[$field] = $this->raw("$field + $step");
        }
        return boolval(static::getModel()->updateAll($upData, $where));
    }
    
    /**
     * 字段值减少
     * @param array        $where 条件
     * @param string|array $field 字段名
     * @param int          $step  增长值
     * @return bool
     */
    public function dec($where, $field, $step = 1)
    {
        $fields = is_string($field) ? explode(',', $field) : $field;
        $upData = [];
        foreach ($fields as $field => $val) {
            if (is_numeric($field)) {
                $field = $val;
            } else {
                $step = $val;
            }
            $upData[$field] = $this->raw("$field - $step");
        }
        return boolval(static::getModel()->updateAll($upData, $where));
    }
    
    /**
     * 使用表达式设置数据
     * @access public
     * @param  mixed $value 表达式
     * @return Expression
     */
    public function raw($value)
    {
        return new Expression($value);
    }
    
}

