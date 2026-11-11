<?php

namespace Puerari\Moodle\Tests;

use Puerari\Moodle\MoodleRestApi;
use Puerari\Moodle\Tests\Support\FakeMoodleServer;

/**
 * Base dos testes. A suíte roda do PHPUnit 5.7 (PHP 5.6) ao 13 (PHP 8.4+), por isso:
 * - não sobrescreve setUp()/tearDown(), cuja assinatura ganhou ": void" no PHPUnit 8;
 * - usa só asserções que existem em todas essas versões;
 * - não usa data providers (anotações deixaram de funcionar no PHPUnit 12).
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    const TOKEN = 'token-de-teste';

    /**
     * Cliente apontando para o Moodle falso. Zera o estado do servidor, então chame antes de
     * FakeMoodleServer::respondWith().
     * @param string $returnFormat
     * @return MoodleRestApi
     */
    protected function api($returnFormat = MoodleRestApi::RETURN_ARRAY)
    {
        FakeMoodleServer::reset();
        return $this->withoutPhpErrors(function () use ($returnFormat) {
            return new MoodleRestApi(FakeMoodleServer::url(), TestCase::TOKEN, $returnFormat);
        });
    }

    /**
     * Executa $callback e falha o teste se o PHP emitir qualquer notice, warning ou deprecation.
     * Feito à mão porque cada versão do PHPUnit trata deprecations de um jeito diferente.
     * @param callable $callback
     * @return mixed o retorno de $callback
     */
    protected function withoutPhpErrors(callable $callback)
    {
        $errors = [];
        set_error_handler(function ($level, $message, $file, $line) use (&$errors) {
            $errors[] = sprintf('[%d] %s (%s:%d)', $level, $message, basename($file), $line);
            return true;
        });
        try {
            $result = call_user_func($callback);
        } finally {
            restore_error_handler();
        }
        if ($errors !== []) {
            $this->fail('O PHP ' . PHP_VERSION . " emitiu:\n" . implode("\n", $errors));
        }
        return $result;
    }
}
