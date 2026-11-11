<?php

use Puerari\Moodle\Tests\Support\FakeMoodleServer;

require __DIR__ . '/../vendor/autoload.php';

// Carrega as classes da biblioteca sob um handler próprio: deprecations emitidas na compilação
// (por exemplo, uma assinatura que ficou deprecated) acontecem fora de qualquer teste.
$compileErrors = [];
set_error_handler(function ($level, $message, $file, $line) use (&$compileErrors) {
    $compileErrors[] = sprintf('[%d] %s (%s:%d)', $level, $message, basename($file), $line);
    return true;
});
class_exists(\Puerari\Moodle\MoodleRestApi::class);
class_exists(\Puerari\Moodle\MraException::class);
restore_error_handler();
if ($compileErrors !== []) {
    fwrite(STDERR, 'Carregar src/ no PHP ' . PHP_VERSION . " emitiu:\n" . implode("\n", $compileErrors) . "\n");
    exit(1);
}

FakeMoodleServer::start();
register_shutdown_function([FakeMoodleServer::class, 'stop']);
