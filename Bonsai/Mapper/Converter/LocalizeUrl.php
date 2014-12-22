<?php

namespace Bonsai\Mapper\Converter;

use \Bonsai\Tools;

class LocalizeUrl implements Converter
{

    public function convert($output)
    {
        return Tools::localizeURL($output);
    }

}
