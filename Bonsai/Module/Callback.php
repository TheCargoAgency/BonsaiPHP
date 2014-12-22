<?php

namespace Bonsai\Module;

use Bonsai\Module\Registry;

class Callback
{
    public static function Get($callback){
        $method = Registry::get($callback . "Method");
        $class = Registry::get($callback . "Class");

        if (empty($method) || (empty($class) && !is_null($class))){
            return false;
        }
        
        if ($class){
            if (class_exists($class) && method_exists($class, $method)){
                return $class::$method();
            }else{
                return false;
            }
        }else{
            return (function_exists($method)) ? $method() : false;
        }
    }
    
    public static function inEnvEdit(){
        return static::inEnv(Registry::get('editEnv'));
    }
    
    public static function inEnvCache(){
        return static::inEnv(Registry::get('cacheEnv'));
    }
    
    public static function inEnv($values){
        return (in_array(strtolower(\getenv('APPLICATION_ENV')), $values)) ? true : false;
    }
    
}