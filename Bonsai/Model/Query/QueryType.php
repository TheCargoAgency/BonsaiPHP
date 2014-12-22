<?php

namespace Bonsai\Model\Query;

use \Bonsai\Model\Query\ConditionInterface;

interface QueryType
{
    public static function create(); //return new instance
    public function table($table, $action); //return new instance
    public function __toString(); //return string
}