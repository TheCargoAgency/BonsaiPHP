<?php

/**
 * Basic model for node-access
 */

namespace Bonsai\Model;

use Bonsai\Module\Registry;
use Bonsai\Model\Query\Condition;
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

    }

    public static function getNodeId($nodeReference){
        $registry = Registry::getInstance();

        $columns = array(
            'id' => "{$registry->get('node.id')}",
        );

        $select = Select::create()
                ->from("{$registry->get('node')}")
                ->columns($columns);

        $condition = new Condition("{$registry->get('node.reference')}", $select->pdo('node', $nodeReference));

        $select->where($condition);

        $pdo = $registry->pdo();
        
        $stmt = $pdo->prepare($select);
        $stmt->execute($select->getValues());

        if($result = $stmt->fetch()){
            return $result['id'];
        }else{
            return null;
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
            'template' => "pn.{$registry->get('node.template')}",
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

    public static function indateNode($reference, $template, $renderdata, $contentID = 0){
        $indate = \Bonsai\Model\Query\Indate::create();

        $indate->table(Registry::get('node'))
            ->addKeyField(Registry::get('node.reference'), $indate->pdo('reference', $reference))
            ->addField(Registry::get('node.contentid'), $indate->pdo('contentid', $contentID))
            ->addField(Registry::get('node.template'), $indate->pdo('template', $template))
            ->addField(Registry::get('node.renderdata'), $indate->pdo('renderdata', $renderdata));
        
        $pdo = Registry::pdo();
        
        $stmt = $pdo->prepare($indate);
        $stmt->execute($indate->getValues());

        return $pdo->lastInsertId();
    }    

    public static function indateNodeToNode($parent, $child, $sort = null){
        $indate = \Bonsai\Model\Query\Indate::create();

        $indate->table(Registry::get('nodetonode'))
            ->addKeyField(Registry::get('nodetonode.parent'), $indate->pdo('parent', $parent))
            ->addKeyField(Registry::get('nodetonode.child'), $indate->pdo('child', $child))
            ->addField(Registry::get('nodetonode.sort'), $indate->pdo('sort', $sort));
        
        $pdo = Registry::pdo();
        
        $stmt = $pdo->prepare($indate);
        $stmt->execute($indate->getValues());

        return $pdo->lastInsertId();
    }    
    
}
