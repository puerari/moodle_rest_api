<?php

/*
 * Router do servidor Moodle falso (php -S), controlado por FakeMoodleServer.
 *
 * Registra cada requisição recebida em request.json e responde com o corpo definido por
 * FakeMoodleServer::respondWith(). Sem resposta definida, ecoa a requisição em JSON.
 * O diretório de estado é derivado da porta, que os dois processos conhecem.
 */

$stateDir = sys_get_temp_dir() . '/fake-moodle-' . $_SERVER['SERVER_PORT'];

$request = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
    'query' => $_GET,
    'body' => $_POST,
];
file_put_contents($stateDir . '/request.json', json_encode($request));

if (is_file($stateDir . '/response')) {
    echo file_get_contents($stateDir . '/response');
} else {
    echo json_encode($request);
}
