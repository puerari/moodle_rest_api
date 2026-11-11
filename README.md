# Moodle REST API, by @puerari

[![Maintainer](http://img.shields.io/badge/maintainer-@leandropuerari-blue.svg?style=flat-square)](https://www.linkedin.com/in/leandropuerari)
[![Source Code](http://img.shields.io/badge/source-puerari/moodle_rest_api-blue.svg?style=flat-square)](https://github.com/puerari/moodle_rest_api)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/puerari/moodle_rest_api.svg?style=flat-square)](https://packagist.org/packages/puerari/moodle_rest_api)
[![Latest Version](https://img.shields.io/github/release/puerari/moodle_rest_api.svg?style=flat-square)](https://github.com/puerari/moodle_rest_api/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Tests](https://img.shields.io/github/actions/workflow/status/puerari/moodle_rest_api/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/puerari/moodle_rest_api/actions/workflows/tests.yml)
[![Build](https://img.shields.io/scrutinizer/build/g/puerari/moodle_rest_api.svg?style=flat-square)](https://scrutinizer-ci.com/g/puerari/moodle_rest_api)
[![Quality Score](https://img.shields.io/scrutinizer/g/puerari/moodle_rest_api.svg?style=flat-square)](https://scrutinizer-ci.com/g/puerari/moodle_rest_api)
[![Total Downloads](https://img.shields.io/packagist/dt/puerari/moodle_rest_api.svg?style=flat-square)](https://packagist.org/packages/puerari/moodle_rest_api)

## About Moodle REST API

###### Moodle REST API is a PHP package that abstracts the interaction with Moodle through its REST API

Moodle REST API é um pacote PHP que abstrai a interação com o Moodle através de sua API.

## About Moodle

###### [Moodle](https://moodle.org/) Moodle is the world's most popular learning management system. Start creating your online learning site in minutes!

O Moodle é o sistema de gestão da aprendizagem mais popular do mundo. Comece a criar seu site de aprendizado online em minutos!

### Highlights

- Easy to set up (Fácil de configurar)
- Composer ready (Pronto para o composer)
- PSR-2 compliant (Compatível com PSR-2)
- PHP 5.6 to 8.5, tested on every version (PHP 5.6 a 8.5, testado em todas as versões)

## Requirements

- PHP 5.6 to 8.5
- PHP extensions: `curl`, `mbstring` and `json`
- A Moodle site with web services and the REST protocol enabled, and a token for a service that allows the functions you call

## Installation

MOODLE_REST_API is available via Composer:

```bash
composer require puerari/moodle_rest_api
```

or add the following line to the `require` section of your `composer.json` file:

```json
"puerari/moodle_rest_api": "^1.2"
```

## Usage

Follow the Moodle documentation to enable the web services on your server and generate a token:

[https://docs.moodle.org/dev/Creating_a_web_service_client](https://docs.moodle.org/dev/Creating_a_web_service_client)

Include the Composer autoloader, create the client and call the methods you need.
Example: list the courses of a category.

```php
<?php

require_once 'vendor/autoload.php';

use Puerari\Moodle\MoodleRestApi;

$api = new MoodleRestApi('https://yourmoodledomain.com', 'YourWebServiceToken');

$response = $api->getCoursesByField('category', '1');

if (!is_array($response)) {
    exit('Could not connect to Moodle.');
}
if (isset($response['exception'])) {
    // Moodle errors (invalid token, function not allowed for the service...) come back in the response
    exit($response['message']);
}

foreach ($response['courses'] as $course) {
    echo $course['fullname'], PHP_EOL;
}
```

The client appends `/webservice/rest/server.php` to the URL given to the constructor.

### Methods

| Method | Moodle web service function | Notes |
|--------|-----------------------------|-------|
| `getCoursesByField($field, $value)` | `core_course_get_courses_by_field` | `$field`: `''` (all courses), `id`, `ids`, `shortname`, `idnumber` or `category` |
| `getCourses(array $ids)` | `core_course_get_courses` | |
| `getEnrolledUsers($courseId, array $options = [])` | `core_enrol_get_enrolled_users` | `$options` as Moodle expects them, e.g. `[['name' => 'onlyactive', 'value' => 1]]` |
| `getEnrolledStudents($courseId, array $options = [])` | `core_enrol_get_enrolled_users` | Only users with the `student` role. Always returns an array, empty on errors |
| `request($wsfunction, array $parameters = [], $method = MoodleRestApi::METHOD_GET)` | any function | For functions without a dedicated method |

Use `request()` to call any function enabled for your token. Use `MoodleRestApi::METHOD_POST` for large payloads: only the token, the format and the function name go in the query string.

```php
$siteInfo = $api->request('core_webservice_get_site_info');

$api->request('core_user_update_users', [
    'users' => [['id' => 7, 'email' => 'new.email@example.com']],
], MoodleRestApi::METHOD_POST);
```

### Return formats

Responses are PHP arrays by default. Pass a format to the constructor, or call `setReturnFormat()`, to get the raw string returned by Moodle instead:

```php
$api = new MoodleRestApi($url, $token, MoodleRestApi::RETURN_JSON); // raw JSON string
$api->setReturnFormat(MoodleRestApi::RETURN_XML);                  // raw XML string
```

### Errors

- Invalid arguments (URL, `getCoursesByField()` field, return format) throw `Puerari\Moodle\MraException`.
- Errors reported by Moodle are **returned**, not thrown: an array with the `exception`, `errorcode` and `message` keys (or the equivalent JSON/XML).
- If the connection fails, methods return `null` (array format) or `false` (JSON and XML formats).

### SSL certificate verification

SSL certificate verification is **disabled by default**, to keep compatibility with earlier versions. Enable it in production:

```php
$api = new MoodleRestApi($url, $token, MoodleRestApi::RETURN_ARRAY, true);
// or
$api->setSslVerify(true);
```

## Development

```bash
composer install       # installs the PHPUnit version that matches your PHP (5.7 to 13)
composer test          # runs the tests against a fake Moodle server, no real Moodle needed
composer test:matrix   # runs the tests on PHP 5.6 to 8.5 with Docker
composer compat        # checks PHP 5.6+ compatibility with PHPCompatibility (needs PHP 7.4+)
composer rector        # shows pending Rector migrations (needs PHP 7.4+)
```

GitHub Actions runs the tests on every PHP version from 5.6 to 8.5 for each push to `main` and each pull request.

## Support

###### Security: If you discover any security related issues, please use the issue tracker on [GitHub](https://github.com/puerari/moodle_rest_api/issues).

Se você descobrir algum problema relacionado à segurança, por favor utilize o rastreador de problemas do [GitHub](https://github.com/puerari/moodle_rest_api/issues).

## Credits

- [Leandro Puerari](https://github.com/puerari) (Developer)
- [Contributors](https://github.com/puerari/moodle_rest_api/contributors)
- [Moodle](https://moodle.org/)

## License

The MIT License (MIT). Please see [License File](https://github.com/puerari/moodle_rest_api/blob/main/LICENSE) for more information.

## Contributing

Please see [contributing page](https://github.com/puerari/moodle_rest_api/blob/main/CONTRIBUTING.md) for details.

## Thank You

**Let's Code...**
