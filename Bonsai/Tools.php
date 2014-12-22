<?php

namespace Bonsai;

use \Bonsai\Exception\BonsaiStrictException;

class Tools
{

    public static function smartDump($variable, $die = false)
    {
        print '<pre>';
        if (is_array($variable)){
            print_r($variable);
        }else{
            var_dump($variable);
        }
        print '</pre>';
        if ($die) {
            die();
        }
    }

    public static function class_implements($class, $interface)
    {
        if (!class_exists($class)) {
            if (Registry::get('strict')){
                throw new BonsaiStrictException("Strict Standards: $class not found");
            }else{
                return false;
            }
        }

        $interfaces = class_implements($class);

        if ($interfaces && !empty($interfaces[$interface])) {
            return true;
        }

        if (Registry::get('strict')){
            throw new BonsaiStrictException("Strict Standards: $class must implement the $interface interface");
        }else{
            return false;
        }
    }

    public static function localizeURL($url){
        if (substr($url, 0, 1) == "/" && substr($url, 0, 2) != "//"){
            return \Bonsai\SERVER_ROOT . $url;
        }else{
            return $url;
        }
    }
    
    public static function localLog($type, $message){
        if (getenv("APPLICATION_ENV") == 'local' && class_exists('FB')){
            // @todo - getting errors where class FB isn't found, so disabling for now
            // FB::$type($message);
        }
    }

    static public function slugify($text)
    {
        $text = str_replace('&', 'and', $text);

        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d\-\_]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-_\w]+~', '', $text);

        if (empty($text))
        {
            return 'n-a';
        }

        return $text;
    }
}
