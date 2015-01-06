<?php

/**
 * Basic model for content access
 */

namespace Bonsai\Model;

use Bonsai\Module\Registry;
use Bonsai\Model\Query\Query;
use Bonsai\Model\Query\Condition;
use Bonsai\Model\Query\ConditionSet;
use Bonsai\Model\Query\Select;
use PDO;

/**
 * Basic model for content access
 *
 */
class Content
{

    /** @var array */
    protected $locale;

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
     * @param int $node
     * @param int|bool $contentID
     *
     * @return  array
     */
    public function getContent($node, $contentID = false)
    {
        $registry = Registry::getInstance();

        $columns = array(
            'template' => "n.{$registry->get('node.template')}",
            'reference' => "n.{$registry->get('node.reference')}",
            'data' => "n.{$registry->get('node.renderdata')}",
            'contentref' => "cr.{$registry->get('contentRegistry.reference')}",
            'contentid' => "c.{$registry->get('content.id')}",
            'content' => "c.{$registry->get('content.content')}",
            'startdate' => "cr.{$registry->get('contentRegistry.startdate')}",
            'enddate' => "cr.{$registry->get('contentRegistry.enddate')}",
        );

        $select = Select::create()
                ->from("{$registry->get('node')} n");

        if ($contentID) {
            $select->join("{$registry->get('content')} c", intval($contentID), "c.{$registry->get('content.id')}");
        } else {
            $select->join("{$registry->get('content')} c", "n.{$registry->get('node.contentid')}", "c.{$registry->get('content.id')}");
        }

        $select->join(Registry::get('contentRegistry') . ' cr', 'cr.' . Registry::get('contentRegistry.id'), 'c.' . Registry::get('content.id'))
                ->columns($columns);

        $conditions = ConditionSet::create();

        if (is_numeric($node)) {
            $conditions->add(new Condition("n.{$registry->get('node.id')}", $select->pdo('node', intval($node))));
        } else {
            $conditions->add(new Condition("n.{$registry->get('node.reference')}", $select->pdo('node', $node)));
        }

        if (!is_null($this->locale)) {
            $conditions->add(new Condition("c.{$registry->get('content.localeID')}", array(0, $select->pdo('locale', $this->locale))));

            $select->orderBy("c.{$registry->get('content.localeID')}", Query::SORT_DESC);
        }

        $select->where($conditions);

        $stmt = $this->pdo->prepare($select);
        $stmt->execute($select->getValues());

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public static function indateContentRegistry($reference, $contentTypeID, $dataFormat = null, $contentCategoryID = 0, $startDate = null, $endDate = null, $active = null){
        $indate = \Bonsai\Model\Query\Indate::create();

        $indate->table(Registry::get('contentRegistry'))
            ->addKeyField(Registry::get('contentRegistry.reference'), $indate->pdo('reference', $reference))
            ->addField(Registry::get('contentRegistry.contentTypeID'), $indate->pdo('contentTypeID', $contentTypeID))
            ->addField(Registry::get('contentRegistry.dataFormat'), $indate->pdo('dataFormat', $dataFormat))
            ->addField(Registry::get('contentRegistry.contentCategoryID'), $indate->pdo('contentCategoryID', $contentCategoryID));

        if (Registry::get('contentByDate') && !is_null($startDate)){
            $indate->addField(Registry::get('contentRegistry.startDate'), $indate->pdo('startDate', $startDate));
        }

        if (Registry::get('contentByDate') && !is_null($endDate)){
            $indate->addField(Registry::get('contentRegistry.endDate'), $indate->pdo('endDate', $endDate));
        }
        
        if (Registry::get('contentByActive') && !is_null($active)){
            $indate->addField(Registry::get('contentRegistry.active'), $indate->pdo('active', $active));
        }

        $pdo = Registry::pdo();
        
        $stmt = $pdo->prepare($indate);
        $stmt->execute($indate->getValues());

        return $pdo->lastInsertId();
    }

    public static function indateContent($contentRegistryID, $content, $localeID = 0){
        $indate = \Bonsai\Model\Query\Indate::create();

        $indate->table(Registry::get('content'))
            ->addKeyField(Registry::get('content.id'), $indate->pdo('crid', $contentRegistryID))
            ->addKeyField(Registry::get('content.localeID'), $indate->pdo('lid', $localeID = 0))
            ->addField(Registry::get('content.content'), $indate->pdo('content', $content));

        $pdo = Registry::pdo();
        
        $stmt = $pdo->prepare($indate);
        $stmt->execute($indate->getValues());

        return $pdo->lastInsertId();
    }    
    
}
