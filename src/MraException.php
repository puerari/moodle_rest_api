<?php

namespace Puerari\Moodle;

use Exception;
use Throwable;

/**
 * @class MraException
 * @package Puerari\Moodle
 * @author Leandro Puerari <leandro@puerari.com.br>
 */
class MraException extends Exception
{
    public function __construct($message, $code = 0, Throwable $previous = null)
    {
        parent::__construct('MoodleRestApi: ' . $message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
