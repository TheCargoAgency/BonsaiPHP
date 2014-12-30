<?php

namespace Bonsai\Module;

use Bonsai\Model\Query\Select;
use Bonsai\Model\Query\Condition;
use Bonsai\Exception\BonsaiException;

/**
 * Registry for settings related to the Bonsai
 *
 * @author abenedict
 */
class Registry
{

    const DEFAULT_INI = "Config/config.ini";
    const PROJECT_NAMESPACE = "Bonsai";
    const PROJECT_NAME = "Bonsai";

    /** @var boolean */
    private $init = false;

    /** @var int */
    private $localeID = null;

    /** @var string */
    private $localeStr = null;

    /** @var array */
    private $config;

    /** @var array */
    private $bonsaiLog = array();

    /** @var PDO */
    private $pdo;

    /** @var Bonsai\Module\Registry */
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

        $defaultConfigFile = constant(self::PROJECT_NAMESPACE . "\\PROJECT_ROOT") . '/' . self::DEFAULT_INI;
        if (!file_exists($defaultConfigFile)) {
            throw new BonsaiException("Default Configuration for " . self::PROJECT_NAME . " not found.");
        }
        $defaultConfig = parse_ini_file($defaultConfigFile);

        if (!empty($custom)) {
            $customConfigFile = constant(self::PROJECT_NAMESPACE . "\\DOCUMENT_ROOT") . '/' . $custom;
            if (!file_exists($customConfigFile)) {
                throw new BonsaiException("Custom Configuration for " . self::PROJECT_NAME . " not found at $customConfigFile.");
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
        throw new BonsaiException("Configuration for " . self::PROJECT_NAME . " cannot be modified at runtime.");
    }

    public function setLocale($locale)
    {
        $select = new Select();

        $columns = array(
            self::get('locale.code'),
        );

        $conditions = new Condition(self::get('locale.id'), $select->pdo('locale', intval($locale)));

        $select->columns($columns)
                ->from(self::get('locale'))
                ->where($conditions);

        $pdo = self::pdo();

        $stmt = $pdo->prepare($select);
        $stmt->execute($select->getValues());

        if ($result = $stmt->fetch()) {
            $this->localeID = intval($locale);
            $this->localeStr = $result['code'];
        } else {
            throw new BonsaiException("Specified locale ($locale) could not be located in the '" . self::get('locale') . "' table.");
        }
    }

    public function getLocale()
    {
        return $this->localeID;
    }

    public function getLocaleString()
    {
        return $this->localeStr;
    }

    public static function pdo()
    {
        return self::getInstance()->getPDO();
    }

    private function getPDO()
    {
        if (isset($this->pdo)) {
            return $this->pdo;
        }

        $this->pdo = new \PDO($this->config['dns'], $this->config['username'], $this->config['passwd']);

        return $this->pdo;
    }

    public function addLog($message, $file, $method, $line)
    {
        $this->bonsaiLog[] = array(
            'message' => $message,
            'file' => $file,
            'method' => $method,
            'line' => $line,
        );
    }

    public static function log($message, $file, $method, $line)
    {
        if (self::get('strict')) {
            throw new \Bonsai\Module\BonsaiStrictException($message);
        }
        
        self::getInstance()->addLog($message, $file, $method, $line);
    }

    public static function getLog()
    {
        return self::getInstance()->bonsaiLog;
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
