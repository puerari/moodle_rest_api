<?php

namespace Puerari\Moodle\Tests;

use Puerari\Moodle\MoodleRestApi;
use Puerari\Moodle\MraException;
use Puerari\Moodle\Tests\Support\FakeMoodleServer;

class MoodleRestApiTest extends TestCase
{
    public function testConstructorAppendsWebServiceEndpointToBaseUrl()
    {
        $this->withoutPhpErrors(function () {
            $withSlash = new MoodleRestApi('https://moodle.example.com/', 'token');
            $withoutSlash = new MoodleRestApi('https://moodle.example.com', 'token');
            $inSubdirectory = new MoodleRestApi('https://example.com/moodle/', 'token');

            $this->assertSame('https://moodle.example.com/webservice/rest/server.php', $withSlash->getServerUrl());
            $this->assertSame('https://moodle.example.com/webservice/rest/server.php', $withoutSlash->getServerUrl());
            $this->assertSame('https://example.com/moodle/webservice/rest/server.php', $inSubdirectory->getServerUrl());
        });
    }

    public function testConstructorRejectsInvalidUrl()
    {
        $this->expectException(MraException::class);
        $this->expectExceptionMessage('MoodleRestApi: Invalid URL!');

        $this->withoutPhpErrors(function () {
            new MoodleRestApi('moodle.example.com', 'token');
        });
    }

    public function testSetServerUrlStoresUrlWithoutAppendingEndpoint()
    {
        $api = $this->api();
        $this->withoutPhpErrors(function () use ($api) {
            $api->setServerUrl('https://moodle.example.com/');
        });

        // Diferente do construtor: a URL é gravada exatamente como recebida.
        $this->assertSame('https://moodle.example.com/', $api->getServerUrl());
    }

    public function testSetServerUrlRejectsInvalidUrl()
    {
        $api = $this->api();

        $this->expectException(MraException::class);
        $this->expectExceptionMessage('MoodleRestApi: Invalid URL!');

        $this->withoutPhpErrors(function () use ($api) {
            $api->setServerUrl('not a url');
        });
    }

    public function testAccessorsAndFluentSetters()
    {
        $api = $this->api();
        $this->withoutPhpErrors(function () use ($api) {
            $this->assertSame(self::TOKEN, $api->getAccessToken());
            $this->assertSame($api, $api->setAccessToken('outro-token'));
            $this->assertSame('outro-token', $api->getAccessToken());

            $this->assertSame(MoodleRestApi::RETURN_ARRAY, $api->getReturnFormat());
            $this->assertSame($api, $api->setReturnFormat(MoodleRestApi::RETURN_XML));
            $this->assertSame(MoodleRestApi::RETURN_XML, $api->getReturnFormat());

            $this->assertFalse($api->getSslVerify());
            $api->setSslVerify(true);
            $this->assertTrue($api->getSslVerify());
        });
    }

    public function testSetReturnFormatRejectsUnknownFormat()
    {
        $api = $this->api();

        $this->expectException(MraException::class);
        $this->expectExceptionMessage("Invalid return format: 'yaml'");

        $this->withoutPhpErrors(function () use ($api) {
            $api->setReturnFormat('yaml');
        });
    }

    public function testArrayFormatRequestsJsonAndDecodesResponse()
    {
        $api = $this->api();
        FakeMoodleServer::respondWithJson(['courses' => [['id' => 2, 'shortname' => 'C1']]]);

        $result = $this->withoutPhpErrors(function () use ($api) {
            return $api->request('core_course_get_courses');
        });

        $this->assertSame(['courses' => [['id' => 2, 'shortname' => 'C1']]], $result);
        $request = FakeMoodleServer::lastRequest();
        $this->assertSame('/webservice/rest/server.php', $request['path']);
        $this->assertSame(self::TOKEN, $request['query']['wstoken']);
        $this->assertSame('json', $request['query']['moodlewsrestformat']);
        $this->assertSame('core_course_get_courses', $request['query']['wsfunction']);
    }

    public function testJsonFormatReturnsRawResponse()
    {
        $api = $this->api(MoodleRestApi::RETURN_JSON);
        FakeMoodleServer::respondWith('{"courses":[]}');

        $result = $this->withoutPhpErrors(function () use ($api) {
            return $api->request('core_course_get_courses');
        });

        $this->assertSame('{"courses":[]}', $result);
        $request = FakeMoodleServer::lastRequest();
        $this->assertSame('json', $request['query']['moodlewsrestformat']);
    }

    public function testXmlFormatRequestsXmlAndReturnsRawResponse()
    {
        $api = $this->api(MoodleRestApi::RETURN_XML);
        $xml = '<?xml version="1.0" encoding="UTF-8" ?><RESPONSE><MULTIPLE></MULTIPLE></RESPONSE>';
        FakeMoodleServer::respondWith($xml);

        $result = $this->withoutPhpErrors(function () use ($api) {
            return $api->request('core_course_get_courses');
        });

        $this->assertSame($xml, $result);
        $request = FakeMoodleServer::lastRequest();
        $this->assertSame('xml', $request['query']['moodlewsrestformat']);
    }

    public function testGetRequestSendsEverythingInQueryString()
    {
        $api = $this->api();
        $this->withoutPhpErrors(function () use ($api) {
            $api->request('local_test_function', ['options' => ['ids' => [3, 5]]]);
        });

        $request = FakeMoodleServer::lastRequest();
        $this->assertSame('GET', $request['method']);
        $this->assertSame('local_test_function', $request['query']['wsfunction']);
        $this->assertSame(['ids' => ['3', '5']], $request['query']['options']);
        $this->assertSame([], $request['body']);
    }

    public function testPostRequestKeepsOnlyAuthenticationInQueryString()
    {
        $api = $this->api();
        $this->withoutPhpErrors(function () use ($api) {
            $api->request('local_test_function', ['users' => [['id' => 7]]], MoodleRestApi::METHOD_POST);
        });

        $request = FakeMoodleServer::lastRequest();
        $this->assertSame('POST', $request['method']);
        $this->assertSame(
            ['wstoken' => self::TOKEN, 'moodlewsrestformat' => 'json', 'wsfunction' => 'local_test_function'],
            $request['query']
        );
        $this->assertSame(['users' => [['id' => '7']]], $request['body']);
    }

    public function testParametersDoNotLeakIntoNextRequest()
    {
        $api = $this->api();
        $this->withoutPhpErrors(function () use ($api) {
            $api->request('local_first', ['leftover' => 'x']);
            $api->getEnrolledUsers(10);
        });

        $request = FakeMoodleServer::lastRequest();
        $this->assertSame('core_enrol_get_enrolled_users', $request['query']['wsfunction']);
        $this->assertArrayNotHasKey('leftover', $request['query']);
    }

    public function testMoodleErrorIsReturnedNotThrown()
    {
        $api = $this->api();
        $error = ['exception' => 'moodle_exception', 'errorcode' => 'invalidtoken', 'message' => 'Invalid token'];
        FakeMoodleServer::respondWithJson($error);

        $result = $this->withoutPhpErrors(function () use ($api) {
            return $api->request('core_course_get_courses');
        });

        $this->assertSame($error, $result);
    }

    public function testConnectionFailureReturnsNullForArrayAndFalseForRawFormats()
    {
        $this->withoutPhpErrors(function () {
            $arrayApi = new MoodleRestApi(FakeMoodleServer::unreachableUrl(), 'token');
            $jsonApi = new MoodleRestApi(FakeMoodleServer::unreachableUrl(), 'token', MoodleRestApi::RETURN_JSON);

            $this->assertNull($arrayApi->request('core_course_get_courses'));
            $this->assertFalse($jsonApi->request('core_course_get_courses'));
        });
    }
}
