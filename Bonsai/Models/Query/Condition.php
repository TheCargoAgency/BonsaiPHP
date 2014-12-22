<?php

namespace Bonsai\Models\Query;

use \Bonsai\Models\Query\Query;
use \Bonsai\Models\Query\ConditionInterface;
use \Bonsai\Exception\QueryBuilderException;

class Condition implements ConditionInterface
{
    private $next;
    private $condition;
    
    public function __construct($field, $values, $comparison = Query::EQUAL, $next = Query::WHERE_AND){
        if (!in_array($comparison, array(Query::EQUAL,Query::NOT_EQUAL,Query::GREATER_EQUAL,Query::LESSER_EQUAL,Query::GREATER,Query::LESSER,Query::IS_NOT))){
            throw new QueryBuilderException('Unsupported comparison.');
        }

        if (!in_array($next, array(Query::WHERE_AND,Query::WHERE_OR))){
            throw new QueryBuilderException('Unsupported next action.');
        }
        
        if (is_array($values) && count($values)){
            $comparison = Query::WHERE_IN;

            $set = "(";
            
            foreach ($values as $key => $value){
                $values[$key] = (is_numeric($value)) || (substr($value, 0, 1) == ':') ? $value : "'$value'";
            }
                
            $set .= implode (Query::FIELD_SEPARATOR, $values);
            $set .= ")";
            $values = $set;
        }
        
        $this->next = $next;
        $this->condition = "$field$comparison$values";
    }
    
    public function getNext(){
        return $this->next;
    }
    
    public function getCondition($indent = ''){
        $indent .= Query::INDENT;
        return PHP_EOL . $indent . $this->condition;
    }
}