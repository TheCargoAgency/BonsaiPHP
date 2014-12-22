<?php

namespace Bonsai\Render\PreProcess;

class AutoWrap extends PreProcessBase
{
    protected $defaults = array(
        'tag' => 'div',
    );
    
    public function preProcess(){
        $tag = $this->args['tag'];
        
        $output = "";
        
        $contents = explode("\n", $this->input);
        
        foreach ($contents as $content){
            $content = trim($content);
            if (!empty($content)){
               $output .= preg_match("/^<.+>$/", $content) ?
                $content :
                "<{$tag}>{$content}</{$tag}>";
            }
        }
        
        return $output;

    }
}


