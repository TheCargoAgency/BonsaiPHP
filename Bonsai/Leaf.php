<?php

/**
 * PHP class to provide node functionality
 */

namespace Bonsai;

use Bonsai\Module\Registry;
use Bonsai\Module\Callback;
use Bonsai\Model;
use Bonsai\Render\Renderer;

require_once __DIR__ . '/Bonsai.php';

/**
 * Leaf container
 */
class Leaf extends Trunk
{

    /** @var string */
    protected $renderer;

    /** @var tree[] */
    protected $content;

    /** @var boolean */
    protected $cache;
    
    /** @var string */
    protected $cachedContent;

    /** @var array */
    protected $conf;

    /** @var integer */
    protected $nodeID;

    /** @var integer */
    protected $contentOverride;

    /**
     * Construct the object, fetch child data and instantiate child classes
     *
     * @param int $nodeID
     * @param int|bool $contentOvverride
     */
    public function __construct($nodeID, $contentOverride = false, $cache = true)
    {
        $this->cache = Callback::Get('cacheOn') ? $cache : false;
        $this->cachedContent = $cache ? $this->getCachedContent($nodeID) : null;

        if (!is_null($this->cachedContent)){
            return;
        }
        
        $contentModel = new Model\Content(Registry::pdo());

        $content = $contentModel->getContent($nodeID, $contentOverride);

        $this->nodeID = $nodeID;
        $this->contentOverride = $contentOverride;
        $this->reference = $content['reference'];
        $this->contentref = $content['contentref'];
        $this->renderer = $content['renderer'];
        $this->contentid = $content['contentid'];
        $this->content = $content['content'];
        $this->data = $this->getData($content);
    }

    public static function getContentDataArray($nodeID, $contentOverride)
    {
        $leaf = new leaf($nodeID, $contentOverride, false);
        return $leaf->getContentArray();
    }

    /**
     * Access view and render content
     *
     * @param array $fieldsMap (array that remaps new content fields to initial one, keys are initial content fields, values are new content fields)
     * @return string
     */
    public function getContent($fieldsMap = [])
    {
        if (!is_null($this->cachedContent)){
            return $this->cachedContent;
        }
        
        $content = $this->isJSON($this->content) ? json_decode(utf8_encode($this->content)) : $this->content;
        if (count($fieldsMap) > 0) {
            $content = $this->remapObjectProperties($content, $fieldsMap);
        }
        
        $output = Renderer::render($this->renderer, $content, $this->data);
        
        if ($this->cache){
            $this->cacheContent($output, $this->nodeID, $this->contentOverride);
        }
        
        return $output;
    }

    /**
     * Get Content Array
     *
     * @return string
     */
    public function getContentArray()
    {
        return $this->isJSON($this->content) ? json_decode(utf8_encode($this->content), true) : false;
    }    
    
    /**
     * Remaps object properties according to input $fieldsMap,
     * where keys are initial property names and values are new property names.
     * It also unsets properties with new values = null.
     *
     * @param array $fieldsMap
     * @return array
     */
    private function remapObjectProperties($object, $fieldsMap)
    {
        foreach ($fieldsMap as $initialFieldName => $newFieldName) {
            if (isset($object->$initialFieldName)) {
                if (null !== $newFieldName) {
                    $object->$newFieldName = $object->$initialFieldName;
                }
                unset($object->$initialFieldName);
            }
        }
        return $object;
    }

    /**
     * Access load content tree
     *
     * @param  array $withContent
     * @return array
     */
    public function getTreeArray($withContent = false)
    {
        $tree = array();

        $tree['node'] = $this->nodeID;
        $tree['reference'] = $this->reference;
        $tree['contentref'] = $this->contentref;

        if ($withContent){
            $tree['content'] = $this->getContent();
        }else{
            $tree['content'] = '';
        }

        return $tree;
    }

}
