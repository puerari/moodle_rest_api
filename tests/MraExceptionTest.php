<?php

namespace Puerari\Moodle\Tests;

use Puerari\Moodle\MraException;

class MraExceptionTest extends TestCase
{
    public function testMessageIsPrefixedAndCodeIsKept()
    {
        $exception = $this->withoutPhpErrors(function () {
            return new MraException('Falhou', 42);
        });

        $this->assertSame('MoodleRestApi: Falhou', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
    }

    public function testToString()
    {
        $string = $this->withoutPhpErrors(function () {
            return (string)new MraException('Falhou', 42);
        });

        $this->assertSame("Puerari\\Moodle\\MraException: [42]: MoodleRestApi: Falhou\n", $string);
    }
}
