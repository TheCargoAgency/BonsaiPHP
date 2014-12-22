<?php

namespace Bonsai\Render\PreProcess;

abstract class PreProcessBase implements PreProcess
{
    protected $defaults = array();
    protected $args;
    protected $input;

    public function __construct($input, array $args)
    {
        $this->args = array_merge($this->defaults, $args);
        $this->input = $input;
    }

    public function __toString()
    {
        return $this->preProcess();
    }

}
