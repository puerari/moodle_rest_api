<?php

namespace Puerari\Moodle;

/**
 * @trait Account
 * @package Puerari\Cwp
 * @author Leandro Puerari <leandro@puerari.com.br>
 */
trait Core
{
    /**
     * @param string $field
     * @param string $value
     * @return array|string
     * @throws MraException
     */
    public function getCoursesByField(/*string*/ $field, /*string*/ $value)/*: array|string*/
    {
        $validFields = ['', 'id', 'ids', 'shortname', 'idnumber', 'category'];
        if (!in_array($field, $validFields)) {
            throw new MraException("Invalid field value: '$field'.\n Valid values: " . implode(', ', $validFields));
        }
        $this->data = compact('field', 'value');
        $this->data['wsfunction'] = 'core_course_get_courses_by_field';
        return $this->execGetCurl();
    }

    /**
     * @param array $ids
     * @return array|string
     * @throws MraException
     */
    public function getCourses(array $ids)
    {
        $this->data = [];
        $this->data['wsfunction'] = 'core_course_get_courses';
        $cont = 0;
        foreach ($ids as $vlr) {
            $this->data['options']['ids'][$cont] = intval($vlr);
            ++$cont;
        }
        return $this->execGetCurl();
    }

    /**
     * @param $course_id
     * @param array $options
     * @return array|string
     * @throws MraException
     */
    public function getEnrolledUsers(/*string*/ $course_id, array $options = [])
    {
        $this->data = [];
        $this->data['courseid'] = $course_id;
        $this->data['options'] = $options;
        $this->data['wsfunction'] = 'core_enrol_get_enrolled_users';
        return $this->execGetCurl();
    }

    /**
     * @param $course_id
     * @param array $options
     * @return array
     * @throws MraException
     */
    public function getEnrolledStudents(/*string*/ $course_id, array $options = [])/*: array*/
    {
        $oldReturnFormat = $this->getReturnFormat();
        $this->setReturnFormat(self::RETURN_ARRAY);
        $students = $this->getEnrolledUsers($course_id, $options);
        $this->setReturnFormat($oldReturnFormat);
        $studentsToReturn = [];
        foreach ($students as $student) {
            if (is_array($student['roles'])) {
                foreach ($student['roles'] as $role) {
                    if ($role['shortname'] == 'student') {
                        $studentsToReturn[] = $student;
                    }
                }
            }
        }
        return $studentsToReturn;
    }
}
