<?php

/**
 * Basic model for node-access
 */

namespace Bonsai\Model;

use Bonsai\Module\Registry;
use Bonsai\Model\Query\Query;
use Bonsai\Model\Query\Condition;
use Bonsai\Model\Query\ConditionSet;
use Bonsai\Model\Query\Select;
use PDO;

/**
 * Basic model for node-access
 *
 * Loads nodes and their children
 *
 */
class Node
{

    /** @var PDO */
    protected $pdo;

    /**
     * Prepre the model for use
     * @param PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;

        if (!empty(Registry::getInstance()->getLocale())) {
            $this->locale = Registry::getInstance()->getLocale();
        }
    }

    /**
     * Fetch an array of parent and child data from the database
     *
     * @param   int    $node
     *
     * @return  array
     */
    public function getChildren($node)
    {
        $registry = Registry::getInstance();

        $columns = array(
            'parent' => "pn.{$registry->get('node.id')}",
            'parentContentID' => "pn.{$registry->get('node.contentid')}",
            'contentID' => "cn.{$registry->get('node.contentid')}",
            'child' => "cn.{$registry->get('node.id')}",
            'renderer' => "pn.{$registry->get('node.renderer')}",
            'reference' => "pn.{$registry->get('node.reference')}",
            'data' => "pn.{$registry->get('node.renderdata')}",
            'sort' => "nn.{$registry->get('nodetonode.sort')}",
        );

        $select = Select::create()
                ->from("{$registry->get('node')} pn")
                ->join("{$registry->get('nodetonode')} nn", "nn.{$registry->get('nodetonode.parent')}", "pn.{$registry->get('node.id')}")
                ->join("{$registry->get('node')} cn", "nn.{$registry->get('nodetonode.child')}", "cn.{$registry->get('node.id')}")
                ->columns($columns);

        if (is_numeric($node)) {
            $condition = new Condition("pn.{$registry->get('node.id')}", $select->pdo('node', intval($node)));
        } else {
            $condition = new Condition("pn.{$registry->get('node.reference')}", $select->pdo('node', $node));
        }

        $select->where($condition)
                ->orderBy("nn.{$registry->get('nodetonode.sort')}");

        $stmt = $this->pdo->prepare($select);
        $stmt->execute($select->getValues());

        return $stmt->fetchAll();
    }

}
