<?php

namespace Puerari\Moodle\Tests\Support;

/**
 * Servidor HTTP local (php -S) que imita o endpoint REST do Moodle, para testar a biblioteca
 * sem um Moodle real. É iniciado uma vez no bootstrap e compartilhado por todos os testes.
 */
final class FakeMoodleServer
{
    /** @var resource|null */
    private static $process;

    /** @var int */
    private static $port;

    /** @var string */
    private static $stateDir;

    /**
     * @throws \RuntimeException
     */
    public static function start()
    {
        self::$port = self::freePort();
        self::$stateDir = sys_get_temp_dir() . '/fake-moodle-' . self::$port;
        if (!is_dir(self::$stateDir)) {
            mkdir(self::$stateDir);
        }

        $command = escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:' . self::$port . ' '
            . escapeshellarg(__DIR__ . '/fake-moodle-router.php');
        $isWindows = DIRECTORY_SEPARATOR === '\\';
        if (!$isWindows) {
            // Sem o exec, proc_terminate() mataria só o sh intermediário e o php -S ficaria órfão.
            $command = 'exec ' . $command;
        }
        $devNull = $isWindows ? 'NUL' : '/dev/null';
        self::$process = proc_open(
            $command,
            [0 => ['pipe', 'r'], 1 => ['file', $devNull, 'w'], 2 => ['file', $devNull, 'w']],
            $pipes,
            null,
            null,
            ['bypass_shell' => true]
        );

        $deadline = microtime(true) + 10;
        while (microtime(true) < $deadline) {
            $connection = @fsockopen('127.0.0.1', self::$port, $errno, $errstr, 0.1);
            if ($connection) {
                fclose($connection);
                return;
            }
            usleep(50000);
        }
        self::stop();
        throw new \RuntimeException('O servidor Moodle falso não subiu na porta ' . self::$port . '.');
    }

    public static function stop()
    {
        if (is_resource(self::$process)) {
            proc_terminate(self::$process);
            proc_close(self::$process);
        }
        self::$process = null;
        if (self::$stateDir !== null && is_dir(self::$stateDir)) {
            self::reset();
            rmdir(self::$stateDir);
        }
    }

    /**
     * URL base do Moodle falso (o cliente acrescenta /webservice/rest/server.php).
     * @return string
     */
    public static function url()
    {
        return 'http://127.0.0.1:' . self::$port;
    }

    /**
     * URL de uma porta sem nada escutando, para simular falha de conexão do cURL.
     * @return string
     */
    public static function unreachableUrl()
    {
        return 'http://127.0.0.1:' . self::freePort();
    }

    /**
     * Apaga a resposta definida e a última requisição registrada.
     */
    public static function reset()
    {
        foreach (['response', 'request.json'] as $file) {
            if (is_file(self::$stateDir . '/' . $file)) {
                unlink(self::$stateDir . '/' . $file);
            }
        }
    }

    /**
     * Define o corpo que o servidor devolve nas próximas requisições, até o próximo reset().
     * @param string $body
     */
    public static function respondWith($body)
    {
        file_put_contents(self::$stateDir . '/response', $body);
    }

    /**
     * @param mixed $data
     */
    public static function respondWithJson($data)
    {
        self::respondWith(json_encode($data));
    }

    /**
     * Última requisição recebida: method, path, query ($_GET) e body ($_POST), ou null se nenhuma.
     * @return array|null
     */
    public static function lastRequest()
    {
        $file = self::$stateDir . '/request.json';
        return is_file($file) ? json_decode(file_get_contents($file), true) : null;
    }

    /**
     * @return int
     */
    private static function freePort()
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        return (int)substr(strrchr($name, ':'), 1);
    }
}
