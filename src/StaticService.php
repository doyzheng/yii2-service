<?php

namespace doyzheng\yii2service;

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
     * @var string 返回数据格式
     */
    public static $dataFormat;
    
    /**
     * @var \yii\caching\CacheInterface 缓存对象
     */
    public static $cache;
    
    /**
     * 查询后返回数组
     */
    const DATA_FORMAT_ARRAY = 'array';
    
    /**
     * 查询后返回对象
     */
    const DATA_FORMAT_MODEL = 'model';
    
    /**
     * 静态调用
     * @param $name
     * @param $arguments
     * @return bool|mixed
     */
    public static function __callStatic($name, $arguments)
    {
        // 查询是否有缓存
        if ($result = static::getCache($arguments)) {
            return $result;
        }
        // 过滤查询参数
        if (static::isUseWhere($name)) {
            static::mergeCondition($arguments, static::baseCondition());
        }
        // 调用Service方法
        $result = static::call($name, $arguments);
        // 返回值转换为数组
        if (static::$dataFormat == self::DATA_FORMAT_ARRAY) {
            if (is_array($result) || is_object($result)) {
                $result = ArrayHelper::toArray($result);
            }
        }
        // 设置缓存
        static::setCache($arguments, $result);
        return $result;
    }
    
    /**
     * 调用Service方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    private static function call($name, $arguments)
    {
        $service = new Service(['model' => static::$model]);
        if (method_exists($service, $name)) {
            return call_user_func_array([$service, $name], $arguments);
        }
        throw new UnknownMethodException($name);
    }
    
    /**
     * 判断获取调用方法是否使用查询
     * @param $name
     * @return bool
     */
    protected static function isUseWhere($name)
    {
        $methods = 'get,getAll,getPage,update,deleteAll,count,sum,min,max,inc,dec';
        return in_array($name, explode(',', $methods));
    }
    
    /**
     * 合并查询条件
     * @param array $arguments
     * @param mixed ...$conditions
     */
    protected static function mergeCondition(&$arguments, ...$conditions)
    {
        if (isset($arguments[0])) {
            $condition   = ['and'];
            $condition[] = $arguments[0];
            foreach ($conditions as $item) {
                if ($item) {
                    $condition[] = $item;
                }
            }
            $arguments[0] = $condition;
        }
    }
    
    /**
     * 查询结果转换为数组
     */
    public static function asArray()
    {
        static::$dataFormat = self::DATA_FORMAT_ARRAY;
    }
    
    /**
     * 基础查询条件
     * @param array $ext
     * @return array
     */
    protected static function baseCondition()
    {
        return [];
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
