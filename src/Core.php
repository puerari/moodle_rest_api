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
}
