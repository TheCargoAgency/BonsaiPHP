<?php

namespace Bonsai\Render\PreProcess;

interface PreProcess
{

    public function __construct($input, array $args);

    public function preProcess(); //return string
}
