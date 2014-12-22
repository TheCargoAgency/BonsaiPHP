<?php

namespace Bonsai\Mapper\Converter;

use \Bonsai\Tools;

class Link implements Converter
{
    protected $link;
    
    public function __construct($link='')
    {
        $this->link = $link;
    }

    public function convert($output)
    {
        return '<a href="' . Tools::localizeURL($this->link) . '">' . $output . "</a>";
    }

}
