<?php

namespace Puerari\Moodle\Tests;

use Puerari\Moodle\MoodleRestApi;
use Puerari\Moodle\MraException;
use Puerari\Moodle\Tests\Support\FakeMoodleServer;

class CoreTest extends TestCase
{
    public function testGetCoursesByFieldSendsFieldAndValue()
    {
        $api = $this->api();
        $response = ['courses' => [['id' => 2]], 'warnings' => []];
        FakeMoodleServer::respondWithJson($response);

        $result = $this->withoutPhpErrors(function () use ($api) {
            return $api->getCoursesByField('category', '1');
        });

        $this->assertSame($response, $result);
        $request = FakeMoodleServer::lastRequest();
        $this->assertSame('core_course_get_courses_by_field', $request['query']['wsfunction']);
        $this->assertSame('category', $request['query']['field']);
        $this->assertSame('1', $request['query']['value']);
    }

    public function testGetCoursesByFieldRejectsUnknownFieldWithoutCallingMoodle()
    {
        $api = $this->api();
        try {
            $this->withoutPhpErrors(function () use ($api) {
                $api->getCoursesByField('fullname', 'Curso');
            });
            $this->fail('Deveria lançar MraException.');
        } catch (MraException $exception) {
            $this->assertStringStartsWith("MoodleRestApi: Invalid field value: 'fullname'.", $exception->getMessage());
        }

        $this->assertNull(FakeMoodleServer::lastRequest());
    }

    public function testGetCoursesSendsIdsAsSequentialIntegers()
    {
        $api = $this->api();
        $this->withoutPhpErrors(function () use ($api) {
            $api->getCourses([10 => '3', 20 => 5]);
        });

        $request = FakeMoodleServer::lastRequest();
        $this->assertSame('core_course_get_courses', $request['query']['wsfunction']);
        $this->assertSame(['ids' => ['3', '5']], $request['query']['options']);
    }

    public function testGetEnrolledUsersSendsCourseIdAndOptions()
    {
        $api = $this->api();
        $this->withoutPhpErrors(function () use ($api) {
            $api->getEnrolledUsers(10, [['name' => 'onlyactive', 'value' => 1]]);
        });

        $request = FakeMoodleServer::lastRequest();
        $this->assertSame('core_enrol_get_enrolled_users', $request['query']['wsfunction']);
        $this->assertSame('10', $request['query']['courseid']);
        $this->assertSame([['name' => 'onlyactive', 'value' => '1']], $request['query']['options']);
    }

    public function testGetEnrolledStudentsKeepsOnlyUsersWithStudentRole()
    {
        $api = $this->api();
        $users = [
            ['id' => 1, 'roles' => [['roleid' => 3, 'shortname' => 'editingteacher']]],
            ['id' => 2, 'roles' => [['roleid' => 5, 'shortname' => 'student']]],
            ['id' => 3, 'roles' => [
                ['roleid' => 1, 'shortname' => 'manager'],
                ['roleid' => 5, 'shortname' => 'student'],
            ]],
            ['id' => 4], // 'roles' é VALUE_OPTIONAL na resposta do Moodle
            ['id' => 5, 'roles' => []],
        ];
        FakeMoodleServer::respondWithJson($users);

        $students = $this->withoutPhpErrors(function () use ($api) {
            return $api->getEnrolledStudents(10);
        });

        $this->assertSame([$users[1], $users[2]], $students);
    }

    public function testGetEnrolledStudentsAlwaysReturnsArrayAndRestoresFormat()
    {
        $api = $this->api(MoodleRestApi::RETURN_JSON);
        FakeMoodleServer::respondWithJson([
            ['id' => 2, 'roles' => [['shortname' => 'student']]],
        ]);

        $students = $this->withoutPhpErrors(function () use ($api) {
            return $api->getEnrolledStudents(10);
        });

        $this->assertSame([['id' => 2, 'roles' => [['shortname' => 'student']]]], $students);
        $this->assertSame(MoodleRestApi::RETURN_JSON, $api->getReturnFormat());
    }

    public function testGetEnrolledStudentsReturnsEmptyArrayOnMoodleError()
    {
        // No PHP 8+, percorrer este array de erro como se fosse a lista de usuários lançava TypeError.
        $api = $this->api();
        FakeMoodleServer::respondWithJson([
            'exception' => 'dml_missing_record_exception',
            'errorcode' => 'invalidrecord',
            'message' => 'Can\'t find data record in database table course.',
        ]);

        $students = $this->withoutPhpErrors(function () use ($api) {
            return $api->getEnrolledStudents(999);
        });

        $this->assertSame([], $students);
    }

    public function testGetEnrolledStudentsReturnsEmptyArrayOnConnectionFailure()
    {
        $students = $this->withoutPhpErrors(function () {
            $api = new MoodleRestApi(FakeMoodleServer::unreachableUrl(), 'token');
            return $api->getEnrolledStudents(10);
        });

        $this->assertSame([], $students);
    }
}
