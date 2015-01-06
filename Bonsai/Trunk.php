<?php

/**
 * PHP class to provide node functionality
 */

namespace Bonsai;

use Bonsai\Module\Registry;
use Bonsai\Module\Callback;
use Bonsai\Render\Renderer;
use Bonsai\Permissions;

/**
 * Bonsai Tree Base class
 */
abstract class Trunk implements Tree
{

    protected function getData($datafields)
    {
        if (isset($datafields['content'])) {
            unset($datafields['content']);
        }

        if (!empty($datafields['data']) && $this->isJSON($datafields['data'])) {
            $data = json_decode($datafields['data']);
        } else {
            $data = new \stdClass();
            $data->class = $datafields['data'];
        }

        if (isset($datafields['data'])) {
            unset($datafields['data']);
        }

        if (empty($data->class)) {
            $data->class = $datafields['reference'];
        }

        if (Callback::Get('editPermission')) {
            if (isset($datafields['contentref'])) {
                $editname = Renderer::CONTENT_EDIT;
                $data->$editname = $datafields['contentref'];
            }
            if (isset($datafields['reference'])) {
                $editname = Renderer::NODE_EDIT;
                $data->$editname = $datafields['reference'];
            }
        }

        $data->node = $datafields;

        return $data;
    }

    /**
     * 
     * 
     * @param   integer|string   $nodeID
     * @param   integer   $contentID
     * 
     * @return  string
     */
    protected function getCachedContent($nodeID, $contentID = null)
    {
        if (!is_int($nodeID)){
            $model = new Model\Node(Registry::pdo());
            $nodeID = $model->getNodeId($nodeID);
            if (is_null($nodeID)){
                return null;
            }
        }
        
        $cachePath = $this->getCachePath($nodeID, $contentID);
        $cachePath .= $this->getCacheFileName($nodeID, $contentID);

        if (file_exists($cachePath)) {
            return file_get_contents($cachePath);
        } else {
            return null;
        }
    }

    protected function cacheContent($content, $nodeID, $contentID = null)
    {
        $cachePath = $this->getCachePath($nodeID, $contentID);

        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0777, true);
        }

        $cachePath .= $this->getCacheFileName($nodeID, $contentID);

        file_put_contents($cachePath, $content);
    }

    protected function getCachePath($nodeID, $contentID = null)
    {
        $cachePath = \Bonsai\DOCUMENT_ROOT . Registry::get('CacheLocation');
        $cachePath .= Registry::getInstance()->getLocaleString() . '/';
        $cachePath .= 'node/';
        $cachePath .= $this->getCachePathComponent($nodeID);
        if (!empty($contentID)) {
            $cachePath .= 'content/';
            $cachePath .= $this->getCachePathComponent($contentID);
        }

        return $cachePath;
    }

    protected function getCacheFileName($nodeID, $contentID = null)
    {
        $cachePath = $nodeID;
        $cachePath .= empty($contentID) ? '' : '-' . $contentID;
        $cachePath .= ".cache";

        return $cachePath;
    }

    protected function getCachePathComponent($id)
    {
        $id = strrev(strval($id));
        $id = str_split($id, 2);
        $id = implode('/', $id) . '/';

        return $id;
    }

    public static function isJSON($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

}
