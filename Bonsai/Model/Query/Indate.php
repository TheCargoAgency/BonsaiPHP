<?php

namespace Bonsai\Model\Query;

use Bonsai\Model\Query\Query;
use Bonsai\Model\Query\QueryType;
use Bonsai\Exception\QueryBuilderException;

class Indate implements QueryType
{
    protected $table;
    private $pdoValues = array();

    protected $keyFields = array();
    protected $keyValues = array();

    protected $fields = array();
    protected $values = array();
    
    public static function create()
    {
        return new Indate();
    }

    public function table($table, $action = Query::INTO)
    {
        if ($action != Query::INTO) {
            throw new QueryBuilderException('Only INTO statements are allowed in Insert queries.');
        }

        if (isset($this->table)) {
            throw new QueryBuilderException('Only one table is allowed in an INSERT statement.');
        }

        $this->table = $table;

        return $this;
    }

    public function addField($field, $value){
        $this->fields[] = $field;
        $this->values[] = $value;

        return $this;
    }
    
    public function addKeyField($field, $value){
        $this->keyFields[] = $field;
        $this->keyValues[] = $value;

        return $this;
    }
    
    protected function arrayToList($array){
        $list = "(";
        $list .= implode(',', $array);
        $list .= ")";
        
        return $list;
    }

    protected function arrayToParams($fields, $values){
        $list = array();
        foreach ($fields as $key => $field){
            $list[] = "{$fields[$key]}={$values[$key]}";
        }
        
        return implode(', ', $list);
    }
    
    public function pdo($name, $value)
    {
        $name = preg_replace("/[^a-z]/", '', strtolower($name));

        if (isset($this->pdoValues[$name])) {
            throw new QueryBuilderException('PDO references must be unique.');
        }

        $this->pdoValues[$name] = $value;

        return ":" . $name;
    }

    public function getValues()
    {
        return $this->pdoValues;
    }    
    
    public function __toString(){
        $query = '';
        $query .= Query::INSERT . Query::INTO . PHP_EOL;
        $query .= $this->table . PHP_EOL;
        $query .= $this->arrayToList(array_merge($this->keyFields, $this->fields)) . PHP_EOL;
        $query .= Query::VALUES . PHP_EOL;
        $query .= $this->arrayToList(array_merge($this->keyValues, $this->values)) . PHP_EOL;
        $query .= Query::ON_DUP . PHP_EOL;
        $query .= $this->arrayToParams($this->fields, $this->values);
        $query .= ";";
        
        return $query;
    }
}
