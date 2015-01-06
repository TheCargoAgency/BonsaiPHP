<?php

namespace Bonsai\Mapper\Converter;

use \Bonsai\Module\Tools;

class LocalizeUrl implements Converter
{

    public function convert($output)
    {
        return Tools::localizeURL($output);
    }

}
