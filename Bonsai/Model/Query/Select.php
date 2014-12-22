<?php

namespace Bonsai\Model\Query;

use Bonsai\Model\Query\Query;
use Bonsai\Model\Query\QueryType;
use Bonsai\Exception\QueryBuilderException;

class Select implements QueryType
{

    private $tables = array();
    private $pdoValues = array();
    private $fieldset;

    /** $var Bonsai\Model\Query\ConditionInterface */
    private $conditionset;
    private $group = array();
    private $order = array();
    private $limitval;
    private $offsetval;

    public static function create()
    {
        return new Select();
    }

    public function table($table, $action = Query::FROM)
    {
        if ($action != $action = Query::FROM) {
            throw new QueryBuilderException('Only FROM statements are allowed in select queries.');
        }

        if (count($this->tables) >= 1) {
            throw new QueryBuilderException('Only one FROM statement is allowed allowed in select queries.');
        }

        $this->tables[] = array('table' => $table, 'action' => Query::FROM);

        return $this;
    }

    public function from($table)
    {
        $this->table($table);

        return $this;
    }

    public function join($table, $field1, $field2, $jointype = Query::JOIN)
    {
        if (!in_array($jointype, array(Query::LEFT_JOIN, Query::RIGHT_JOIN, Query::INNER_JOIN, Query::OUTER_JOIN, Query::JOIN))) {
            throw new QueryBuilderException('Invalid join type.');
        }

        $join = array(
            'table' => $table,
            'field1' => $field1,
            'field2' => $field2,
            'jointype' => $jointype,
        );

        if (count($this->tables) < 1) {
            throw new QueryBuilderException('Cannot join a table to nothing.');
        }

        $this->tables[] = $join;

        return $this;
    }

    public function columns($columns)
    {
        $this->columnset = $columns;

        return $this;
    }

    public function where(ConditionInterface $conditions)
    {
        $this->conditionset = $conditions;

        return $this;
    }

    public function groupBy($field)
    {
        $this->group[] = $field;

        return $this;
    }

    public function orderBy($field, $sort = Query::SORT_ASC)
    {
        if (!in_array($sort, array(Query::SORT_ASC, Query::SORT_DESC))) {
            throw new QueryBuilderException('Invalid sort type.');
        }

        $this->order[] = array('field' => $field, 'sort' => $sort);

        return $this;
    }

    public function limit($limit)
    {
        if (intval($limit) < 1) {
            throw new QueryBuilderException('Limit must be a positive integer.');
        }

        $this->limitval = intval($limit);

        return $this;
    }

    public function offset($offset)
    {
        if (intval($offset) < 1) {
            throw new QueryBuilderException('Offset must be a positive integer.');
        }

        $this->offsetval = intval($offset);

        return $this;
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

    public function __toString()
    {
        $query = Query::SELECT;
        $query .= $this->getColumns();
        $query .= $this->getTables();
        $query .= $this->getWhere();
        $query .= $this->getGroup();
        $query .= $this->getOrder();
        $query .= $this->getLimit();
        $query .= $this->getOffset();

        return $query . ";";
    }

    protected function getColumns()
    {
        if (!count($this->columnset)) {
            return Query::ALL_FIELDS . PHP_EOL;
        }

        $columns = array();

        foreach ($this->columnset as $alias => $column) {
            if (is_numeric($alias)) {
                $columns[] = $column;
            } else {
                $columns[] = $column . Query::ALIAS . $alias;
            }
        }

        return PHP_EOL . Query::INDENT . implode(Query::FIELD_SEPARATOR . PHP_EOL . Query::INDENT, $columns);
    }

    protected function getTables()
    {
        if (count($this->tables) < 1) {
            throw new QueryBuilderException('No table defined for select statement.');
        }

        $tables = "";

        foreach ($this->tables as $table) {
            if (isset($table['action'])) {
                $tables .= PHP_EOL . $table['action'] . PHP_EOL . Query::INDENT . $table['table'];
            } elseif (isset($table['jointype'])) {
                $tables .= PHP_EOL . $table['jointype'];
                $tables .= PHP_EOL . Query::INDENT . $table['table'];
                $tables .= PHP_EOL . Query::JOIN_CONDITION;
                $tables .= PHP_EOL . Query::INDENT . $table['field1'] . Query::EQUAL . $table['field2'];
            }
        }

        return $tables;
    }

    protected function getWhere()
    {
        if (isset($this->conditionset)) {
            return PHP_EOL . Query::WHERE . $this->conditionset->getCondition();
        }
    }

    protected function getGroup()
    {
        if (count($this->group)) {
            return PHP_EOL . Query::GROUP . PHP_EOL . Query::INDENT . implode(Query::FIELD_SEPARATOR . PHP_EOL . Query::INDENT, $this->group);
        }
    }

    protected function getOrder()
    {
        if (count($this->order)) {
            $orders = array();

            foreach ($this->order as $order) {
                $orders[] = $order['field'] . $order['sort'];
            }

            return PHP_EOL . Query::ORDER . PHP_EOL . Query::INDENT . implode(Query::FIELD_SEPARATOR . PHP_EOL . Query::INDENT, $orders);
        }
    }

    protected function getLimit()
    {
        if (!empty($this->limitval)) {
            return PHP_EOL . Query::LIMIT . $this->limitval;
        }
    }

    protected function getOffset()
    {
        if (!empty($this->offsetval)) {
            return PHP_EOL . Query::OFFSET . $this->offsetval;
        }
    }

}
