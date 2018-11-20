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
 * @method static ActiveRecord model()
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
 * @method static integer count($where, $fields = '')
 * @method static integer sum($where, $fields = '')
 * @method static array getErrors()
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
     * @return string
     */
    public static function getModel()
    {
        return self::$model;
    }
    
    /**
     * @param string $model
     */
    public static function setModel($model)
    {
        self::$model = $model;
    }
    
}