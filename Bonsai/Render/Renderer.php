<?php

namespace Bonsai\Render;

use \Bonsai\Module\Registry;
use \Bonsai\Tools;
use \Bonsai\Exception\RenderException;
use \Bonsai\Exception\BonsaiStrictException;

class Renderer
{

    const CONTENT_EDIT = 'bonsai-content';
    const NODE_EDIT = 'bonsai-node';
    const TEMPLATE_EXT = '.phtml';
    const TEMPLATE_PATH = 'Template';

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

    public static function getRenderFile($basename)
    {
        $file = $basename . self::TEMPLATE_EXT;
        $defaultFile = Registry::get('defaultTemplate') . self::TEMPLATE_EXT;
        $userTemplates = \Bonsai\DOCUMENT_ROOT . '/' . Registry::get('renderTemplateLocation') . '/';
        $internalTemplates = \Bonsai\PROJECT_ROOT . '/' . self::TEMPLATE_PATH . '/';

        if (file_exists("$userTemplates$file")) {
            return "$userTemplates$file";
        } elseif (file_exists("$internalTemplates$file")) {
            return "$internalTemplates$file";
        } elseif (file_exists("$userTemplates$defaultFile")) {
            return "$userTemplates$defaultFile";
        } elseif (file_exists("$internalTemplates$defaultFile")) {
            return "$internalTemplates$defaultFile";
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
        return self::getAttributes($data, array(self::CONTENT_EDIT, self::NODE_EDIT));
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

    public static function printField($data, $properties, $format = '', $process = '', $processargs = array())
    {
        print self::renderField($data, $properties, $format, $process, $processargs);
    }

    public static function renderField($data, $properties, $format = '', $process = '', $processargs = array())
    {
        if (empty($data)) {
            return '';
        }

        $args = array();
        $args[] = empty($format) ? '%s' : $format;
        if (!is_array($properties)) {
            $properties = array($properties);
        }

        $process = self::resolvePreprocessor($process);
        print $process . PHP_EOL;

        foreach ($properties as $propertyKey => $property) {
            if (empty($data->$property)) {
                return '';
            }

            if ($process && Tools::class_implements($process, 'Bonsai\Render\PreProcess\PreProcess')) {
                $processor = new $process($data->$property, $processargs);
                $properties[$propertyKey] = $processor->preProcess();
                if ($properties[$propertyKey] == null) {
                    return '';
                }
            } else {
                $properties[$propertyKey] = $data->$property;
            }
        }

        $args = array_merge($args, $properties);

        return call_user_func_array('sprintf', $args);
    }

    public static function resolvePreprocessor($process)
    {
        if (empty($process)) {
            return false;
        }

        $userNamespace = Registry::get('preProcessor');
        $internalNamespace = "\\Bonsai\\Render\\PreProcess\\";

        if (class_exists($userNamespace . $process)) {
            return $userNamespace . $process;
        } elseif (class_exists($internalNamespace . $process)) {
            return $internalNamespace . $process;
        } elseif (Registry::get('strict')) {
            throw new BonsaiStrictException("Strict Standards: Cannot find $userNamespace$process or $internalNamespace$process");
        } else {
            Registry::log("Strict Standards: Cannot find $userNamespace$process or $internalNamespace$process", __FILE__, __METHOD__, __LINE__);
        }

        return false;
    }

}
