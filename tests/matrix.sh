#!/bin/sh
# Roda a suíte em containers Docker (imagens oficiais php:<versão>-cli-alpine), um por versão do PHP.
# Cada container resolve as dependências do zero e recebe o PHPUnit compatível com o seu PHP
# (5.7 no PHP 5.6 ... 13 no PHP 8.4+). O diretório do projeto é montado só para leitura.
#
# Uso: sh tests/matrix.sh [versão ...]        ex.: sh tests/matrix.sh 5.6 8.5

set -u
cd "$(dirname "$0")/.." || exit 1

VERSIONS=${*:-5.6 7.0 7.1 7.2 7.3 7.4 8.0 8.1 8.2 8.3 8.4 8.5}
# Volume com os composer.phar e o cache de pacotes, reaproveitado entre execuções.
VOLUME=moodle-rest-api-matrix

docker volume create "$VOLUME" >/dev/null || exit 1

# O Composer atual exige PHP 7.2.5+; a linha 2.2 LTS é a última que roda no PHP 5.6 a 7.2.4.
docker run --rm -v "$VOLUME":/cache php:8.5-cli-alpine sh -c '
    cd /cache &&
    { [ -f composer.phar ] || php -r "exit(copy(\"https://getcomposer.org/download/latest-stable/composer.phar\", \"composer.phar\") ? 0 : 1);"; } &&
    { [ -f composer-2.2.phar ] || php -r "exit(copy(\"https://getcomposer.org/download/latest-2.2.x/composer.phar\", \"composer-2.2.phar\") ? 0 : 1);"; }
' || { echo "Falha ao baixar o Composer."; exit 1; }

failed=""
for version in $VERSIONS; do
    image="php:$version-cli-alpine"
    if ! docker image inspect "$image" >/dev/null 2>&1 && ! docker pull -q "$image" >/dev/null; then
        echo "== PHP $version: não foi possível baixar $image"
        failed="$failed $version"
        continue
    fi

    output=$(docker run --rm -v "$PWD":/src:ro -v "$VOLUME":/cache \
        -e COMPOSER_HOME=/cache/home -e COMPOSER_ALLOW_SUPERUSER=1 "$image" sh -c '
        composer=/cache/composer.phar
        php -r "exit(PHP_VERSION_ID >= 70205 ? 0 : 1);" || composer=/cache/composer-2.2.phar
        echo "@@php $(php -r "echo PHP_VERSION;")"
        mkdir /app && cd /src &&
        tar -cf - --exclude=./.git --exclude=./vendor --exclude=./tools --exclude=./composer.lock . | tar -xf - -C /app &&
        cd /app &&
        php "$composer" install --no-interaction --no-progress 2>&1 &&
        php vendor/bin/phpunit --colors=never 2>&1
    ' 2>&1)
    status=$?

    php_version=$(printf '%s\n' "$output" | sed -n 's/^@@php //p')
    phpunit_version=$(printf '%s\n' "$output" | grep -o -m1 'PHPUnit [0-9][0-9.]*')
    summary=$(printf '%s\n' "$output" | grep -E '^(OK|Tests:)' | tail -n1)
    echo "== PHP ${php_version:-$version} | ${phpunit_version:-PHPUnit ?} | ${summary:-sem resumo}"

    if [ "$status" -ne 0 ]; then
        printf '%s\n' "$output" | sed 's/^/   /'
        failed="$failed $version"
    fi
done

if [ -n "$failed" ]; then
    echo "Falhou em:$failed"
    exit 1
fi
echo "Todas as versões passaram."
