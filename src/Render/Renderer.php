<?php

namespace Bonsai\Render;

use \Bonsai\Module\Registry;
use \Bonsai\Module\Tools;
use \Bonsai\Exception\RenderException;
use \Bonsai\Exception\BonsaiStrictException;

class Renderer
{

    const CONTENT_EDIT = 'bonsai-content';
    const NODE_EDIT = 'bonsai-node';
    const TEMPLATE_EXT = '.phtml';
    const TEMPLATE_PATH = 'Template';

    public $plugins = array();

    public function __construct()
    {
        $this->fetchPlugins();
    }

    public static function render($template, $content, $data)
    {
        $renderer = new Renderer();
        return $renderer->renderContent($template, $content, $data);
    }

    public function renderContent($template, $content, $data)
    {
        if (empty($template)) {
            return $content;
        }

        $file = $this->getRenderFile($template);

        return $this->partial($file, $content, $data);
    }

    public function getRenderFile($basename)
    {
        $files = array();
        $templates = array();

        $files[] = $basename . static::TEMPLATE_EXT;
        $files[] = Registry::get('defaultTemplate') . static::TEMPLATE_EXT;

        $templates[] = \Bonsai\DOCUMENT_ROOT . '/' . Registry::get('renderTemplateLocation') . '/';
        
        static::fetchPluginTemplates($templates);
        
        $templates[] = \Bonsai\PROJECT_ROOT . '/' . static::TEMPLATE_PATH . '/';

        foreach ($files as $file) {
            foreach ($templates as $template) {
                if (file_exists("$template$file")) {
                    return "$template$file";
                }
            }
        }

        throw new \Bonsai\Exception\RenderException("Fallback to default template failed: $internalTemplates$defaultFile not found.");
    }

    public function partial($file, $content, $data)
    {
        ob_start();

        include $file;
        $output = ob_get_contents();

        ob_end_clean();

        return $output;
    }

    public static function getEditData($data)
    {
        return static::getAttributes($data, array(static::CONTENT_EDIT, static::NODE_EDIT));
    }

    public static function getAttributes($data, $attrs = array())
    {
        $attributes = '';

        foreach ($attrs as $attrkey => $attr) {
            if (!empty($data->$attr)) {
                $attributes .= ' ';
                $attributes .=!is_numeric($attrkey) ? $attrkey : $attr;
                $attributes .= '="' . htmlspecialchars($data->$attr) . '"';
            }
        }

        return $attributes;
    }

    public function printField($data, $properties, $format = '')
    {
        print $this->renderField($data, $properties, $format);
    }

    public function renderField($data, $properties, $format = '')
    {
        if (empty($data)) {
            return '';
        }

        $args = array();
        $args[] = empty($format) ? '%s' : $format;
        if (!is_array($properties)) {
            $properties = array($properties);
        }
        
        $processed = array();
        
        foreach ($properties as $propertyKey => $property) {
            //fetch the name if the property is an array
            if (is_array($property)){
                $propertyName = $property[0];
            }else{
                $propertyName = $property;
            }
            
            //check to see if the field exists or, if the reverse flag is set, doesn't exist
            if(trim($propertyName, '!') == $propertyName){
                if (empty($data->$propertyName)) { return ''; }
            }else{
                $propertyName = trim($propertyName, '!');
                if (!empty($data->$propertyName)) { return ''; }
                continue;
            }
            
            if (is_array($property)){
                $processed[$propertyKey] = $this->preProcess($data, $property);
            }else{
                $processed[$propertyKey] = $data->$propertyName;
            }
        }

        $args = array_merge($args, $processed);

        return call_user_func_array('sprintf', $args);
    }

    protected function preProcess($data, $property){
        $propertyName = $property[0];
        $process = !empty($property[1]) ? $this->resolvePreprocessor($property[1]) : false;
        $processargs = (!empty($property[2]) && is_array($property[2])) ? $property[2] : array();

        if ($process && Tools::class_implements($process, 'Bonsai\Render\PreProcess\PreProcess')) {
            $processor = new $process($data->$propertyName, $processargs);
            $processed = $processor->preProcess();
            
            if (is_null($processed)) {
                return '';
            }else{
                return $processed;
            }
        } else {
            return $data->$propertyName;
        }
    }
    
    protected function resolvePreprocessor($process)
    {
        return Registry::resolveClass('preProcessor', $process, '\\Render\\PreProcess');
    }

    public function fetchPlugins()
    {
        $plugins = Registry::get('plugin');
        if (!empty($plugins)) {
            if (!is_array($plugins)) {
                $plugins = [$plugins];
            }

            foreach ($plugins as $plugin) {
                if (class_exists('\\' . $plugin . '\\Module\\Registry')) {
                    $registry = $plugin . '\\Module\\Registry';
                    $this->plugins[$plugin] = $registry::getInstance();
                }
            }

        }
    }
    
    protected function fetchPluginTemplates(&$templates)
    {
        $renderer = new Renderer();
        
        foreach ($renderer->plugins as $pluginNamespace => $plugin){
            $authorizedRenderers = $plugin->renderTemplate;
            if(is_array($authorizedRenderers)){
                if (in_array(get_class($this), $authorizedRenderers)){
                    $templates[] = constant($pluginNamespace . '\\PROJECT_ROOT') . '/' . $plugin->renderTemplateLocation . '/';
                }
            }else{
                $templates[] = constant($pluginNamespace . '\\PROJECT_ROOT') . '/' . $plugin->renderTemplateLocation . '/';
            }
        }
    }

}
