<?php

namespace Bonsai\Model\Query;

use \Bonsai\Model\Query\Query;
use \Bonsai\Model\Query\ConditionInterface;
use \Bonsai\Exception\QueryBuilderException;

class ConditionSet implements ConditionInterface
{
    private $next;
    private $conditions = array();
    
    public static function create($next = Query::WHERE_AND){
        return new ConditionSet($next);
    }
    
    public function __construct($next = Query::WHERE_AND){
        if (!in_array($next, array(Query::WHERE_AND,Query::WHERE_OR))){
            throw new QueryBuilderException('Unsupported next action.');
        }
        $this->next = $next;
    }
    
    public function add(ConditionInterface $condition){
        $this->conditions[] = $condition;
        
        return $this;
    }
    
    public function getNext(){
        return $this->next;
    }
    
    public function getCondition($indent = ''){
        
        $indent .= Query::INDENT;
        
        $first = true;
        
        $conditions = PHP_EOL . $indent . "(";
        
        foreach ($this->conditions as $condition){
            if (!$first){
                $conditions .=  PHP_EOL . $indent . Query::INDENT . $condition->getNext();
            }
            
            $conditions .= $condition->getCondition($indent);
            $first = false;
        }
        
        $conditions .= PHP_EOL . $indent . ")";
        
        return $conditions;
    }
}