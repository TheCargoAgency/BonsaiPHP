<?php

namespace Bonsai\Render\PreProcess;

class FileCheck extends PreProcessBase
{
    protected $defaults = array(
        'path' => null,
        'data' => null,
        'field' => null,
    );
    
    public function preProcess(){
        $path = $this->args['path'];
        $data = $this->args['data'];
        $field = $this->args['field'];
        
        if (is_null($data) || is_null($field) || empty($data->$field)){
            return null;
        }
                
        if (file_exists($path . $data->$field)){
            return $this->input;
        }
        
        return null;
    }
}


