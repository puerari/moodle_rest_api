# Moodle REST API, by @puerari

[![Maintainer](http://img.shields.io/badge/maintainer-@leandropuerari-blue.svg?style=flat-square)](https://www.linkedin.com/in/leandropuerari)
[![Source Code](http://img.shields.io/badge/source-puerari/moodle_rest_api-blue.svg?style=flat-square)](https://github.com/puerari/moodle_rest_api)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/puerari/moodle_rest_api.svg?style=flat-square)](https://packagist.org/packages/puerari/moodle_rest_api)
[![Latest Version](https://img.shields.io/github/release/puerari/moodle_rest_api.svg?style=flat-square)](https://github.com/puerari/moodle_rest_api/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
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

## Installation

MOODLE_REST_API is available via Composer:

add the following line on your composer.json file

```bash
"puerari/moodle_rest_api": "^1.0"
```

or run

```bash
composer require puerari/moodle_rest_api
```

## Usage

Follow the Moodle documentation to enable API on your server:

[https://docs.moodle.org/dev/Creating_a_web_service_client](https://docs.moodle.org/dev/Creating_a_web_service_client)

Include the Composer autoloader file;

```bash
require_once 'vendor/autoload.php';
```

Instantiate the MoodleRestApi class

```bash
$api = new MoodleRestApi('https://yourmoodledomain.com', 'YourAccessTokenGeneratedOnYouMoodleServer');
```

Call the methods that solve your necessities.
Example: how to get all courses by a field.

```bash
$response = $cwpApi->getCoursesByField('category', '1');
exit(json_decode($response));
```

## Support

###### Security: If you discover any security related issues, please use the issue tracker on [GitHub](https://github.com/puerari/moodle_rest_api/issues).

Se você descobrir algum problema relacionado à segurança, por favor utilize o rastreador de problemas do [GitHub](https://github.com/puerari/moodle_rest_api/issues).

## Credits

- [Leandro Puerari](https://github.com/puerari) (Developer)
- [Contributors](https://github.com/puerari/moodle_rest_api/contributors)
- [Moodle](https://moodle.org/)

## License

The MIT License (MIT). Please see [License File](https://github.com/puerari/moodle_rest_api/blob/master/LICENSE) for more information.

## Contributing

Please see [contributing page](https://github.com/puerari/moodle_rest_api/blob/master/CONTRIBUTING.md) for details.

## Thank You

**Let's Code...**
