<?php

namespace Bonsai\Exception;

class BonsaiStrictException extends \Exception
{

    public function __construct($message = null, $code = null, $previous = null)
    {
        $message = PHP_EOL . PHP_EOL . $message . PHP_EOL . PHP_EOL . "*Strict Standards can be deactivated in the Bonsai Configuration File, See the Help Documentation for Details" . PHP_EOL . PHP_EOL;
        parent::__construct($message, $code, $previous);
    }

}
