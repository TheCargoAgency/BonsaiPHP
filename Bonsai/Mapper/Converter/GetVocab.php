<?php

namespace Bonsai\Mapper\Converter;

use \Bonsai\Module\Vocab;
use \Bonsai\Module\Registry;

class GetVocab implements Converter
{

    public function __construct()
    {
        
    }

    public function convert($output)
    {
        return Vocab::translate($output);
    }
}
