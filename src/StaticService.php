<?php

namespace doyzheng\yii2service;

use yii\base\UnknownMethodException;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\db\Transaction;

/**
 * yii2 数据层服务静态方法调用类
 * Class StaticService
 * @package doyzheng\yii2service
 * @method static ActiveRecord getModel()
 * @method static bool setModel($model)
 * @method static array getErrors()
 * @method static bool setErrors($errors)
 * @method static ActiveQuery find()
 * @method static string getPk()
 * @method static Transaction beginTransaction()
 * @method static bool add($data)
 * @method static array get($where, $fields = '', $order = '')
 * @method static array getAll($where, $fields = '', $order = '')
 * @method static array getPage($where, $page, $limit = '', $fields = '', $order = '')
 * @method static bool update($where, $data)
 * @method static bool updateAll($where, $data, $params = [])
 * @method static bool delete($where)
 * @method static bool deleteAll($where, $params = [])
 * @method static int count($where, $fields = '')
 * @method static int sum($where, $fields)
 * @method static int min($where, $fields)
 * @method static int max($where, $fields)
 * @method static bool inc($where, $field, $step = 1)
 * @method static bool dec($where, $field, $step = 1)
 * @method static bool raw($value)
 * @mixin ActiveRecord
 */
abstract class StaticService
{
    
    /**
     * @var string 模型对象
     */
    public static $model;
    
    /**
     * @param $name
     * @param $arguments
     * @return bool|mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $service = new Service(['model' => static::$model]);
        if (method_exists($service, $name)) {
            return call_user_func_array([$service, $name], $arguments);
        }
        throw new UnknownMethodException($name);
    }
    
    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([new static::$model, $name], $arguments);
    }
    
    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        $model = new static::$model;
        return $model->$name;
    }
    
    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $model        = new static::$model;
        $model->$name = $value;
    }
    
}