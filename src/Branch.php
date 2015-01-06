<?php

/**
 * PHP class to provide node functionality
 */

namespace Bonsai;

use Bonsai\Module\Callback;
use Bonsai\Module\Registry;
use Bonsai\Model;
use Bonsai\Render\Renderer;

/**
 * Branch container
 */
class Branch extends Trunk
{

    /** @var string */
    protected $template;

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
    protected $renderer;
    
    /**
     * Construct the object, fetch child data and instantiate child classes
     *
     * @param int $nodeID
     * @param type $cache
     * @param Renderer $renderer
     */
    public function __construct($nodeID, $cache = true, \Bonsai\Render\Renderer $renderer = null)
    {
        $this->cache = Callback::Get('cacheOn') ? $cache : false;
        $this->cachedContent = $cache ? $this->getCachedContent($nodeID) : null;

        if (!is_null($this->cachedContent)) {
            return;
        }

        $this->renderer = empty($renderer) ? new Renderer() : $renderer;
        
        $nodeModel = new Model\Node(Registry::pdo());

        //fetch and process the children
        $children = $nodeModel->getChildren($nodeID);

        //if the node does not exist, create a null node.
        if (count($children) == 0 && !Registry::get('autoPrune')) {
            $this->buildNullNode();
            return;
        }

        $this->addChildren($children);
        $this->registerViewData($children[0]);
    }

    protected function buildNullNode()
    {
        $this->template = "div";
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
                $this->children[] = new Leaf($child['child'], false, false, $this->renderer);
                //if requested id has no children, set renderer to null and load as leaf
            } elseif (is_null($child['contentID'])) {
                $this->parentID = $child['parentContentID'];
                $child['template'] = null;
                $this->children[] = new Leaf($child['parent'], false, false, $this->renderer);
                //otherwise child is a node
            } else {
                $this->children[] = new Node($child['child'], false, $this->renderer);
            }
        }
    }

    protected function registerViewData($child)
    {
        $this->template = $child['template'];
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

        $output = $this->renderer->renderContent($this->renderer, $content, $this->data);

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
            if (!is_null($this->template) || $this->parentID !== 0) {
                $tree['content'][count($tree['content']) + 1] = $child->getTreeArray($withContent);
            }
        }

        return !is_null($this->template) || !isset($tree['content'][1]) ? $tree : $tree['content'][1];
    }
 
}
