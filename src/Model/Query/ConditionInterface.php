<?php

namespace Bonsai\Model\Query;

interface ConditionInterface
{

    public function getNext(); //return string

    public function getCondition($indent); //return string
}
