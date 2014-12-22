<?php

namespace Bonsai\Mapper\Converter;

class PrettyNumber implements Converter
{
    protected $decimals;
    protected $dec_point;
    protected $thousands_sep;
    
    public function __construct($decimals=0, $dec_point=".", $thousands_sep=",")
    {
        $this->decimals = $decimals;
        $this->dec_point = $dec_point;
        $this->thousands_sep = $thousands_sep;
    }

    public function convert($output)
    {
        return number_format($output, $this->decimals, $this->dec_point, $this->thousands_sep);
    }

}
