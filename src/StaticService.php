<?php

namespace doyzheng\yii2service;

use yii\base\Model;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\db\Transaction;
use yii\helpers\ArrayHelper;
use yii\base\UnknownMethodException;

/**
 * yii2 数据层服务静态方法调用类
 * Class StaticService
 * @package doyzheng\yii2service
 * @method static ActiveRecord getModel()
 * @method static bool setModel($model)
 * @method static array getErrors()
 * @method static bool setErrors($errors)
 * @method static ActiveQuery find()
 * @method static ActiveQuery getQuery($where = [], $fields = '', $order = '', $asArray = '')
 * @method static string getPk()
 * @method static Transaction beginTransaction()
 * @method static bool add($data)
 * @method static array get($where = [], $fields = '', $order = '')
 * @method static array getAll($where = [], $fields = '', $order = '')
 * @method static array getPage($where, $page, $limit = '', $fields = '', $order = '')
 * @method static bool update($where, $data)
 * @method static bool updateAll($where, $data, $params = [])
 * @method static bool delete($where)
 * @method static bool deleteAll($where, $params = [])
 * @method static int count($where = [], $fields = '')
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
    protected static $model;
    
    /**
     * @var string 返回数组
     */
    protected static $asArray;
    
    /**
     * @var array 基础查询条件
     */
    protected static $baseWhere;
    
    /**
     * 静态调用
     * @param string $name
     * @param mixed  $arguments
     * @return array|mixed
     * @throws \yii\base\UnknownClassException
     */
    public static function __callStatic($name, $arguments)
    {
        static::beforeCall($name, $arguments);
        // 查询是否有缓存
        $cacheKey = $arguments;
        if ($result = static::getCache($cacheKey)) {
            return $result;
        }
        // 调用Service方法
        $result = self::callService($name, $arguments);
        // 是否需要转换到数组
        if (static::$asArray) {
            $result = static::toArray($result);
        }
        // 设置缓存
        static::setCache($cacheKey, $result);
        static::afterCall($name, $arguments);
        return $result;
    }
    
    /**
     * 调用Service方法
     * @param string $name
     * @param mixed  $arguments
     * @return mixed
     * @throws \yii\base\UnknownClassException
     */
    private static function callService($name, $arguments)
    {
        $service = new Service([
            'model'     => static::$model,
            'baseWhere' => static::getBaseWhere()
        ]);
        if (method_exists($service, $name)) {
            return call_user_func_array([$service, $name], $arguments);
        }
        // Service方法不存在
        throw new UnknownMethodException($name);
    }
    
    /**
     * 在调用静态方法之后回调
     * @param string $name
     * @param array  $arguments
     */
    protected static function afterCall($name, $arguments)
    {
    }
    
    /**
     * 在调用静态方法之前回调
     * @param string $name
     * @param array  $arguments
     */
    protected static function beforeCall($name, $arguments)
    {
    }
    
    /**
     * model转换到数组
     * @param array $result
     * @return array
     */
    protected static function toArray($result)
    {
        if ($result instanceof Model || is_array($result)) {
            return ArrayHelper::toArray($result);
        }
        return $result;
    }
    
    /**
     * 查询结果转换为数组
     * @param bool $value
     */
    public static function asArray($value = true)
    {
        static::$asArray = $value;
    }
    
    /**
     * 获取基础查询条件
     * @param array $baseWhere
     */
    public static function setBaseWhere($baseWhere = [])
    {
        static::$baseWhere = $baseWhere;
    }
    
    /**
     * 设置基础查询条件
     * @return array
     */
    public static function getBaseWhere()
    {
        return static::$baseWhere;
    }
    
    /**
     * 获取缓存值
     * @param array $condition
     * @return null
     */
    protected static function getCache($condition)
    {
        return null;
    }
    
    /**
     * 设置缓存
     * @param array $condition
     * @param mixed $value
     * @return bool
     */
    protected static function setCache($condition, $value)
    {
        return false;
    }
    
}
