<?php

namespace Bonsai\Modules;

/**
 * Registry for settings related to the Bonsai
 *
 * @author abenedict
 */
class Registry
{

    const DEFAULT_INI = "inc/config.ini";
    const PROJECT_NAME = "Bonsai";

    /** @var boolean */
    private $init = false;

    /** @var int */
    public $locale = null;

    /** @var array */
    private $config;

    /** @var PDO */
    private $pdo;
    
    /** @var Bonsai\Modules\Registry */
    static $instance = null;

    /**
     * Returns the *Registry* instance of this class.
     *
     * @return Registry The *Registry* instance.
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Registry();
        }

        return self::$instance;
    }

    /**
     * Returns the *Registry* instance of this class.
     *
     * @staticvar Registry $instance The *Registry* instances of this class.
     *
     * @return Registry The *Registry* instance.
     */
    public function initialize($custom = false)
    {
        if ($this->init) {
            return $this;
        }

        $defaultConfigFile = \Bonsai\PROJECT_ROOT . '/' . self::DEFAULT_INI;
        if (!file_exists($defaultConfigFile)) {
            throw new \Exception("Default Configuration for " . self::PROJECT_NAME . " not found.");
        }
        $defaultConfig = parse_ini_file($defaultConfigFile);

        if (!empty($custom)) {
            $customConfigFile = \Bonsai\DOCUMENT_ROOT . '/' . $custom;
            if (!file_exists($customConfigFile)) {
                throw new \Exception("Custom Configuration for " . self::PROJECT_NAME . " not found at $customConfigFile.");
            }
            $customConfig = parse_ini_file($customConfigFile);

            $this->config = array_merge($defaultConfig, $customConfig);
        } else {
            $this->config = $defaultConfig;
        }

        $this->init = true;

        return $this;
    }

    public function __get($name)
    {
        if (!$this->init) {
            $this->initialize();
        }

        return !empty($this->config[$name]) ? $this->config[$name] : null;
    }

    public static function get($name)
    {
        return self::getInstance()->$name;
    }

    public function __set($name, $value)
    {
        throw new \Exception("Configuration for " . self::PROJECT_NAME . " cannot be modified at runtime.");
    }

    public function setLocale($locale)
    {
        $this->locale = intval($locale);
    }

    public function getLocale()
    {
        return $this->locale;
    }
    
    public static function pdo(){
        return self::getInstance()->getPDO();
    }

    private function getPDO(){
        if (isset($this->pdo)){
            return $this->pdo;
        }
        
        $this->pdo = new \PDO($this->config['dns'], $this->config['username'], $this->config['passwd']);
        
        return $this->pdo;
    }

    /**
     * Private constructor to prevent creating a new instance via 'new'
     * @return void
     */
    private function __construct()
    {
        
    }

    /**
     * Private constructor to prevent creating a new instance via 'clone'
     * @return void
     */
    private function __clone()
    {
        
    }

    /**
     * Private constructor to prevent creating a new instance via 'unserialize'
     * @return void
     */
    private function __wakeup()
    {
        
    }

}
