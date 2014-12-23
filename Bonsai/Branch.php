<?php

/**
 * PHP class to provide node functionality
 */

namespace Bonsai;

use Bonsai\Module\Callback;
use Bonsai\Module\Registry;
use Bonsai\Model;
use Bonsai\Render\Renderer;

require_once __DIR__ . '/Bonsai.php';

/**
 * Branch container
 */
class Branch extends Trunk
{

    /** @var string */
    protected $renderer;

    /** @var tree[] */
    protected $children = array();

    /** @var array */
    protected $conf;

    /** @var boolean */
    protected $cache;

    /** @var string */
    protected $cachedContent;

    /** @var integer */
    protected $nodeID;

    /** @var integer */
    protected $parentID;

    /** @var \Bonsai\Render\Renderer */
    protected $bonsaiRenderer;
    
    /**
     * Construct the object, fetch child data and instantiate child classes
     *
     * @param int $nodeID
     */
    public function __construct($nodeID, $cache = true, \Bonsai\Render\Renderer $renderer = null)
    {
        $this->cache = Callback::Get('cacheOn') ? $cache : false;
        $this->cachedContent = $cache ? $this->getCachedContent($nodeID) : null;

        if (!is_null($this->cachedContent)) {
            return;
        }

        $this->bonsaiRenderer = empty($renderer) ? new Renderer() : $renderer;
        
        $nodeModel = new Model\Node(Registry::pdo());

        //fetch and process the children
        $children = $nodeModel->getChildren($nodeID);

        //if the node does not exist, create a null node.
        if (count($children) == 0 && !Registry::get('autoPrune')) {
            $this->buildNullNode();
            return false;
        }

        $this->addChildren($children);
        $this->registerViewData($children[0]);
    }

    protected function buildNullNode()
    {
        $this->renderer = "div";
        $this->reference = Registry::get('nullContent');
        $data = new \stdClass();
        $data->class = $this->reference;
        $this->data = $data;
        $this->children[] = new Leaf(Registry::get('nullContent'));
    }

    protected function addChildren($children)
    {
        foreach ($children as $child) {
            //if contentid is non-zero, child is a leaf
            if ($child['contentID']) {
                $this->children[] = new Leaf($child['child'], false, false, $this->bonsaiRenderer);
                //if requested id has no children, set renderer to null and load as leaf
            } elseif (is_null($child['contentID'])) {
                $this->parentID = $child['parentContentID'];
                $child['renderer'] = null;
                $this->children[] = new Leaf($child['parent'], false, false, $this->bonsaiRenderer);
                //otherwise child is a node
            } else {
                $this->children[] = new Node($child['child'], false, $this->bonsaiRenderer);
            }
        }
    }

    protected function registerViewData($child)
    {
        $this->renderer = $child['renderer'];
        $this->reference = $child['reference'];
        $this->data = $this->getData($child);
        $this->nodeID = $child['parent'];
    }

    /**
     * Access view and render content
     *
     * @return string
     */
    public function getContent()
    {
        if (!is_null($this->cachedContent)) {
            return $this->cachedContent;
        }

        $content = '';
        foreach ($this->children as $child) {
            $content .= $child->getContent();
        }

        if (empty($content) && Registry::get('autoPrune')) {
            return '';
        }

        $output = $this->bonsaiRenderer->renderContent($this->renderer, $content, $this->data);

        if ($this->cache) {
            $this->cacheContent($output, $this->nodeID);
        }

        return $output;
    }

    /**
     * Access content tree as an array
     *
     * @param  array $withContent
     * @return array
     */
    public function getTreeArray($withContent = false)
    {
        $tree = array();

        $tree['node'] = $this->nodeID;
        $tree['reference'] = $this->reference;
        $tree['content'] = array();

        foreach ($this->children as $child) {
            if (!is_null($this->renderer) || $this->parentID !== 0) {
                $tree['content'][count($tree['content']) + 1] = $child->getTreeArray($withContent);
            }
        }

        return !is_null($this->renderer) || !isset($tree['content'][1]) ? $tree : $tree['content'][1];
    }

    /**
     * Access content tree as a list
     *
     * @param  boolean $withContent
     * @param  boolean $withTop
     * @return array
     */
    public function getTreeList($withContent = false, $withTop = true, $preview = false)
    {
        $tree = $this->getTreeArray($withContent);

        return $this->parseTreeArray($tree, $withTop, $preview);
    }

    /**
     * Parse content tree array as list
     *
     * @param  mixed $content
     * @param  boolean $top
     * @return array
     */
    public function parseTreeArray($content, $top = true, $preview = false)
    {
        $controls = '<span title="Click to show/hide children" class="disclose fa fa-caret-down"></span>'
                . '<div class="controls">';
        $controls .= '<span title="Click to edit node" class="fa fa-pencil" onclick="window.open(\'node?reference=' . $content['reference'] . '\');"></span>';
        $controls .= $preview ? '<span title="Click to preview branch" class="preview fa fa-eye" onclick="window.open(\'' . $preview . $content['node'] . '\');"></span>' : '';
        $controls .= '<span title="Click to insert a branch" class="create fa fa-plus"></span>'
                . '<span title="Click to remove branch" class="destroy fa fa-remove"></span>'
                . '</div>';
        $leafcontrols = '<div class="controls">';
        $leafcontrols .= '<span title="Click to edit node" class="fa fa-pencil" onclick="window.open(\'node?reference=' . $content['reference'] . '\');"></span>';
        $leafcontrols .= $preview ? '<span title="Click to preview node" class="preview fa fa-eye" onclick="window.open(\'' . $preview . $content['node'] . '\');"></span>' : '';
        $leafcontrols .= isset($content['contentref']) ? '<span title="Click to edit content" class="fa fa-pencil-square-o" onclick="window.open(\'content?reference=' . $content['contentref'] . '\');"></span>' : '';
        $leafcontrols .= '<span title="Click to remove node" class="destroy fa fa-remove"></span>'
                . '</div>';

        if (is_array($content['content'])) {
            $output = $top ? '<ol class="sortable">' : '';
            $output .= "<li nodetreeid=\"{$content['node']}\" class=\"mjs-nestedSortable-branch mjs-nestedSortable-expanded\"><div class=\"node-heading ui-sortable-handle\">$controls {$content['reference']}</div><ol>";

            foreach ($content['content'] as $entry) {
                $output .= $this->parseTreeArray($entry, false, $preview);
            }

            $output .= "</ol></li>";
            $output .= $top ? "</ol>" : '';
        } else {
            $output = "<li nodetreeid=\"{$content['node']}\" class=\"mjs-nestedSortable-no-nesting mjs-nestedSortable-leaf\"><div class=\"ui-sortable-handle\"><div class=\"node-heading\">$leafcontrols {$content['reference']}</div><div>{$content['content']}</div></div></li>";
        }

        if ($top) {
            $output = $this->cleanseOutput($output);
        }

        return $output;
    }

    /**
     * Parse content tree array as list
     *
     * @param  string $content
     * @return array
     */
    public static function cleanseOutput($content, $process = array('iframe', 'a'))
    {
        $content = str_replace("&", "&amp;", $content);
        $dom = new \DOMDocument();
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        if (in_array('iframe', $process)) {
            $results = $xpath->query("//iframe");
            foreach ($results as $result) {
                $result->parentNode->removeChild($result);
            }
        }

        if (in_array('a', $process)) {
            $results = $xpath->query("//a");
            foreach ($results as $result) {
                $children = $result->childNodes;
                foreach ($children as $child) {
                    $result->parentNode->appendChild($child);
                }
                $result->parentNode->removeChild($result);
            }
        }

        $output = $dom->saveHTML($dom->getElementsByTagName('ol')->item(0));
        return $output;
    }

}
