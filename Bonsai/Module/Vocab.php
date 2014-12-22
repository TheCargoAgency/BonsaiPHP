<?php

namespace Bonsai\Module;

use \Bonsai\Model\Query\Select;
use \Bonsai\Model\Query\Query;
use \Bonsai\Model\Query\Condition;
use \Bonsai\Model\Query\ConditionSet;
use \Bonsai\Module\Registry;

class Vocab {
    private $vocab = array();
    private $queries = 0;
    private $cached = 0;
    
    /** @var \Bonsai\Module\Vocab Self */
    private static $instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Vocab();
        }
        return self::$instance;
    }

    public function getVocab($key){
        if (isset($this->vocab[$key])){
            $this->cached++;
            return $this->vocab[$key];
        }

        $registry = Registry::getInstance();
        
        $pdo = Registry::pdo();

        $columns = array(
            'id' => "cr.{$registry->get('contentRegistry.id')}",
            'content' => "c.{$registry->get('content.content')}",
            'localeID' => "c.{$registry->get('content.localeID')}",
        );
            
        $select = new Select();

        $conditions = ConditionSet::create()
                ->add(new Condition("cr.{$registry->get('contentRegistry.contentTypeID')}", 2))
                ->add(new Condition("cr.{$registry->get('contentRegistry.reference')}", $select->pdo('key', $key)))
                ->add(new Condition("c.{$registry->get('content.localeID')}", array(0,$select->pdo('locale', Registry::getInstance()->getLocale()))));

        $select->columns($columns)
                ->from("{$registry->get('contentRegistry')} cr")
                ->join("{$registry->get('content')} c", "c.{$registry->get('content.id')}", "cr.{$registry->get('contentRegistry.id')}")
                ->where($conditions)
                ->orderBy($registry->get('content.localeID'));
        
        $stmt = $pdo->prepare($select);
        $stmt->execute($select->getValues());

        $vocab = $stmt->fetch();
        $this->vocab[$key] = $vocab ? $vocab['content'] : '[' . $key . ']';
        $this->queries++;
        return $this->vocab[$key];
    }

    public static function translate($key)
    {
        return self::getInstance()->getVocab($key);
    }
}