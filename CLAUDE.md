# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Visão geral

Pacote Composer `puerari/moodle_rest_api`: uma biblioteca PHP que encapsula chamadas à REST API de web services do Moodle (`<moodle>/webservice/rest/server.php`). Suporta PHP 5.6 a 8.5 (`"php": "^5.6 || ^7.0 || ^8.0"`). Não há aplicação executável. Os testes usam um Moodle falso; para validar uma chamada de verdade, é preciso um servidor Moodle com web services habilitados e um token (ver https://docs.moodle.org/dev/Creating_a_web_service_client).

## Comandos

```bash
composer install                              # instala o PHPUnit compatível com o PHP local (5.7 no PHP 5.6 ... 13 no 8.4+)
composer test                                 # suíte no PHP local
vendor/bin/phpunit --filter testToString      # um único teste (ou tests/CoreTest.php para um arquivo)
composer test:matrix                          # suíte em Docker no PHP 5.6, 7.0–7.4 e 8.0–8.5
sh tests/matrix.sh 5.6 8.5                    # matriz só nas versões indicadas
composer compat                               # PHPCompatibility em src/ e tests/ (config em phpcs.xml.dist)
composer rector                               # Rector em dry-run; `composer rector:fix` aplica
```

Rector e PHPCompatibility ficam em `tools/` (com `composer.json` próprio, instalado sob demanda pelos scripts `compat`/`rector`) e não no `require-dev` da raiz. O motivo: eles exigem PHP ≥ 7.4, e na raiz impediriam o `composer install` (e, portanto, os testes) no PHP 5.6–7.3. O `rector.php` usa `withPhpSets()` sem argumentos, de propósito: assim o alvo é a menor versão do `require.php` do `composer.json`. Fixar uma versão maior (por exemplo, `php85: true`) faz o Rector introduzir sintaxe do PHP 7/8, como promoção de propriedades no construtor e tipos de retorno.

O PHPCompatibility é estático e não detecta problemas que dependem dos dados (por exemplo, acessar índice de string quando a resposta do Moodle é um erro). Esses só aparecem executando o código em PHP 8+.

## Arquitetura

- `MoodleRestApi` (`src/MoodleRestApi.php`) concentra o estado (token, URL, formato de retorno, `ssl_verify`, `$data`) e o transporte HTTP via cURL. Os wrappers de wsfunctions do Moodle ficam em **traits** incluídos com `use`. Hoje só existe `Core` (`src/Core.php`). O `use Core/*, Server*/` comentado indica o padrão pretendido: um trait por área da API.
- **Fluxo de um wrapper:** sobrescrever `$this->data` com os parâmetros da wsfunction, definir `$this->data['wsfunction']` e chamar `execGetCurl()` ou `execPostCurl()`. Em seguida, `config()` injeta `wstoken` e `moodlewsrestformat`, e `formattedReturn()` trata a resposta. `$this->data` é estado compartilhado da instância, então todo wrapper deve reiniciá-lo antes de preenchê-lo.
- **Formato de retorno:** `RETURN_ARRAY` (padrão) não é um formato do Moodle. A requisição sai como `json` e a resposta passa por `json_decode(..., true)`. `RETURN_JSON` e `RETURN_XML` devolvem a string crua. Um wrapper que precisa processar o resultado troca temporariamente para `RETURN_ARRAY` e restaura o formato anterior (ver `getEnrolledStudents`).
- **GET vs POST:** no GET, tudo vai na query string. No POST, `wstoken`, `moodlewsrestformat` e `wsfunction` ficam na query string e o restante vai no corpo. Arrays aninhados em `$this->data` são serializados por `http_build_query` na notação `options[ids][0]=...` que o Moodle espera.
- `request($wsfunction, $parameters, $method)` é a saída genérica para wsfunctions que ainda não têm wrapper.
- **Erros:** `MraException` prefixa a mensagem com `MoodleRestApi: `. Erros do próprio Moodle (token inválido, função não habilitada no serviço etc.) chegam no corpo da resposta (chaves `exception`/`errorcode`) e **não** viram exceção. Falha do cURL também não lança: `curl_exec` retorna `false`, que vira `null` em `RETURN_ARRAY`.

## Testes

- `tests/bootstrap.php` sobe `Tests\Support\FakeMoodleServer` (um `php -S` com `tests/Support/fake-moodle-router.php`) uma vez para a suíte toda. O router grava cada requisição recebida (method, path, query, body) e devolve o corpo definido pelo teste. Padrão de um teste: `$api = $this->api()` (zera o estado do servidor, por isso vem **antes** de `respondWith`), depois `FakeMoodleServer::respondWithJson(...)`, a chamada e asserções sobre o retorno e sobre `FakeMoodleServer::lastRequest()`. Para simular falha de conexão, use `FakeMoodleServer::unreachableUrl()`.
- Toda chamada à biblioteca passa por `$this->withoutPhpErrors(function () { ... })`, que falha o teste em qualquer notice, warning ou deprecation. A detecção é feita à mão porque cada versão do PHPUnit trata deprecations de um jeito diferente. O bootstrap também aborta se carregar `src/` emitir algo.
- CI: `.github/workflows/tests.yml` roda a suíte no GitHub Actions do PHP 5.6 ao 8.5 (via `shivammathur/setup-php`, que usa o Composer 2.2 LTS no PHP 5.6–7.1), mais um job com `composer compat` e `composer rector`. Esse job falha se o Rector tiver migração pendente. Ao suportar uma nova versão do PHP, atualize a lista no workflow e em `tests/matrix.sh`. O Scrutinizer (`.scrutinizer.yml`, disparado por webhook) só analisa o `src/` e não instala dependências nem roda testes.
- O mesmo código de teste roda do PHPUnit 5.7 ao 13, então: não sobrescreva `setUp()`/`tearDown()` (o `: void` exigido desde o PHPUnit 8 não compila no PHP 5.6), não use data providers nem anotações (removidas no PHPUnit 12) e use só asserções que já existiam no 5.7. O `phpunit.xml.dist` também não pode ter atributos específicos de uma versão.

## Convenções

- **Compatibilidade de PHP 5.6 a 8.5:** type hints escalares, tipos de retorno, visibilidade de constantes e `Throwable` ficam **comentados inline** (`/*string*/ $field`, `)/*: array*/`, `/*public*/ const`). Siga esse padrão em código novo, sem ativar a sintaxe do PHP 7+. Para APIs deprecated em versões novas, prefira um equivalente que funcione em todas as versões em vez de `if (PHP_VERSION_ID ...)`. Exemplo: `unset($ch)` no lugar de `curl_close()`, que é deprecated no 8.5.
- Antes de acessar índices de uma resposta em `RETURN_ARRAY`, confira com `is_array`/`isset`: a resposta pode ser `null` (falha do cURL) ou um array de erro do Moodle, e no PHP 8+ acessar índice de string lança `TypeError`.
- PSR-2. O CONTRIBUTING.md também pede que o README seja atualizado quando o comportamento mudar.
- Arquivo novo de desenvolvimento na raiz (configuração de ferramenta, docs internas) precisa de uma linha `export-ignore` no `.gitattributes`. Assim o pacote instalado pelo Composer continua só com `src/`, `composer.json`, `README.md` e `LICENSE`.

## Armadilhas

- O construtor remove a barra final da URL base e anexa `/webservice/rest/server.php`. `setServerUrl()` **não** faz isso: grava a URL exatamente como recebida.
- Há resquícios do projeto irmão `cwp_api` que não são referências reais: `@package Puerari\Cwp` no docblock de `Core.php` e o link para `puerari/cwp_api` no CONTRIBUTING.md.
- O README documenta os métodos, os formatos de retorno e o tratamento de erros. Ao criar ou mudar um método público, atualize a tabela "Methods".
