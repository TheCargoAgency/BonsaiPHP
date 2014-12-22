<?php

namespace Bonsai\Mapper\Converter;

class HideEmpty implements Converter
{

    protected $empty;
    protected $value;

    public function __construct($empty = 'hidden', $value = 'visible')
    {
        $this->empty = $empty;
        $this->value = $value;
    }

    public function convert($output)
    {
        $output = trim($output, "-");
        if (empty($output)) {
            return $this->empty;
        } else {
            return $this->value;
        }
    }

}
