<?php

/**
 * ContentMapper for mapping data to a content template
 */

namespace Bonsai\Mapper;

use \Bonsai\Tools;
use \Bonsai\Modules\Registry;
use \Bonsai\Exception\BonsaiStrictException;

/**
 * ContentMapper allows formatted mapping of content to a named key in a string
 * 
 * Call Syntax:
 * {{<key>[::<converter>[::<param1>[::<param2>[::...]]]]}}
 * 
 * Examples:
 * {{field}} is replaced with $output in:
 * $output = $values['field']
 * 
 * {{field::Converter}} is replaced with $output in:
 * $converter = new Converter();
 * $output = $converter->convert($values['field']);
 * 
 * {{field::Converter::Param1::Param2}} is replaced with $output in:
 * $converter = new Converter('Param1', 'Param2');
 * $output = $converter->convert($values['field']);
 */
class ContentMapper
{
    protected $reservedKeys;
    protected $values = array();
    protected $formats = array();

    public function __construct($values = array(), $formats = array())
    {
        $this->reservedKeys = Registry::get('reservedKeys');
        $this->values = $values;
        $this->formats = $formats;
    }

    public function convert($input)
    {
        $pattern = "/\{\{.+?\}\}/";
        $matches = array();
        preg_match_all($pattern, $input, $matches, PREG_OFFSET_CAPTURE);

        foreach (array_reverse($matches[0]) as $match) {
            $field = $this->getFieldData(trim($match[0], '{}'));
            $input = $this->replace($input, $field, $match[1], strlen($match[0]));
        }

        return $input;
    }

    protected function getFieldData($command)
    {
        $command = explode('::', $command);
        $field = array_shift($command);
        $route = explode('>', $field);
        $field = str_replace(">", "", $field);

        if (count($route) == 2 && in_array($route[0], $this->reservedKeys)){
            $this->values[$route[0]][$route[1]] = $route[1];
        }

        //check if the data exists
        if (!$this->issetDeep($this->values, $route)) {
            if(Registry::get('strict')){
                throw new BonsaiStrictException("Strict Standards: Field \$values['" . implode("']['", $route) . "'] not set.");
            }else{
                $this->values[$field] = "-";
            }
            //$command = array();
        }else{
            $this->values[$field] = isset($this->values[$field]) ? $this->values[$field] : $this->issetDeep($this->values, $route, true);
        }

        //check if a format exists, if not default to string
        if (!isset($this->formats[$field])){
            $this->formats[$field] = "%s";
        }

        $output = $this->values[$field];

        if (count($command)) {
            $function = array_shift($command);

            $function = self::resolveConverter($function);

            if (!empty($function) && Tools::class_implements($function, 'Bonsai\Mapper\Converter\Converter')) {
                $r = new \ReflectionClass($function);
                $converter = $r->newInstanceArgs($command);
                $output = $converter->convert($output);
            }
        }

        return sprintf($this->formats[$field],$output);
    }

    protected function replace($input, $field, $index, $length)
    {
        return substr_replace($input, $field, $index, $length);
    }

    public static function resolveConverter($converter){
        if (empty($converter)){
            return false;
        }
        
        $userNamespace = Registry::get('converter');
        $internalNamespace = "\\Bonsai\\Mapper\\Converter\\";
        
        if (class_exists($userNamespace . $converter)){
            return $userNamespace . $converter;
        }elseif (class_exists($internalNamespace . $converter)){
            return $internalNamespace . $converter;
        }elseif(Registry::get('strict')){
            throw new BonsaiStrictException("Strict Standards: Cannot find $userNamespace$converter or $internalNamespace$converter");
        }
        
        return false;
    }
    
    /**
     * Naviage into an array and check if the specified route exists
     *
     * Optionally returns the entity at the end of route instead of boolean
     *
     * @param       array          $array       Array to search
     * @param       array          $route       Array of keys to navigate
     * @param       boolean        $fetch       Should the value be returned instead of the boolean true
     *
     * @return      mixed
     */
    public static function issetDeep($array, $route, $fetch = false)
    {
        foreach ($route as $crossroad){
            if (!isset($array[$crossroad])){
                return false;
            }

            $array = $array[$crossroad];
        }

        return $fetch ? $array : true;
    }

    public static function cleanseOutput($output, $class = 'destroy')
    {
        $dom = new \DOMDocument();
        $dom->loadHTML(mb_convert_encoding($output, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);
        $results = $xpath->query("//*[contains(@class, '$class')]");

        foreach ($results as $result){
            $result->parentNode->removeChild($result);
        }

        $output = $dom->saveHTML($dom->getElementsByTagName('div')->item(0));
        return $output;
    }

}
