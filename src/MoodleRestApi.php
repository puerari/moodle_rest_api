<?php

namespace Puerari\Moodle;

/**
 * @class MoodleRestApi
 * @package Puerari\Moodle
 * @author Leandro Puerari <leandro@puerari.com.br>
 */
class MoodleRestApi
{
    /** Constant that defines return format to JSON */
    /*public*/
    const RETURN_JSON = 'json';

    /** Constant that defines return format to XML */
    /*public*/
    const RETURN_XML = 'xml';

    /** Constant that defines return format to ARRAY */
    /*public*/
    const RETURN_ARRAY = 'array';

    /** Constant that defines return format to ARRAY */
    /*public*/
    const METHOD_GET = 'get';

    /** Constant that defines return format to ARRAY */
    /*public*/
    const METHOD_POST = 'post';

    /** @var string Access Token */
    private $access_token;

    /** @var string URL for the Moodle server */
    private $server_url;

    /** @var string Return format for the response (json, array or xml) */
    private $return_format;

    /** @var bool defaults to false - Verify or not the SSL certificate */
    private $ssl_verify;

    /** @var array data to be passed to the API */
    private $data;

    /** use Traits */
    use Core/*, Server*/
        ;

    /** MoodleRestApi constructor.
     * @param string $server_url
     * @param string $access_token
     * @param string $return_format
     * @param bool $ssl_verify
     * @throws MraException
     */
    public function __construct(/*string*/ $server_url, /*string*/ $access_token, /*string*/ $return_format = MoodleRestApi::RETURN_ARRAY, /*bool*/ $ssl_verify = false)
    {
        if (!filter_var($server_url, FILTER_VALIDATE_URL))
            throw new MraException('Invalid URL!');

        $urllen = strlen($server_url) - 1;
        $url = ($server_url[$urllen] == "/") ? mb_substr($server_url, 0, $urllen) : $server_url;

        $this->server_url = $url . '/webservice/rest/server.php';
        $this->access_token = $access_token;
        $this->return_format = $return_format;
        $this->ssl_verify = $ssl_verify;
    }

    /**
     * @return string
     */
    public function getAccessToken()/*: string*/
    {
        return $this->access_token;
    }

    /**
     * @param string $access_token
     * @return MoodleRestApi
     */
    public function setAccessToken(/*string*/ $access_token)/*: MoodleRestApi*/
    {
        $this->access_token = $access_token;
        return $this;
    }

    /**
     * @return string
     */
    public function getServerUrl()/*: string*/
    {
        return $this->server_url;
    }

    /**
     * @param string $server_url
     * @return MoodleRestApi
     * @throws MraException
     */
    public function setServerUrl(/*string*/ $server_url)/*: MoodleRestApi*/
    {
        if (!filter_var($server_url, FILTER_VALIDATE_URL))
            throw new MraException('Invalid URL!');

        $this->server_url = $server_url;
        return $this;
    }

    /**
     * @return bool
     */
    public function getSslVerify()/*: bool*/
    {
        return $this->ssl_verify;
    }

    /**
     * @param bool $ssl_verify
     */
    public function setSslVerify(/*bool*/ $ssl_verify)/*: void*/
    {
        $this->ssl_verify = $ssl_verify;
    }


    /**
     * @return string
     */
    public function getReturnFormat()/*: string*/
    {
        return $this->return_format;
    }

    /**
     * @param string $return_format
     * @return MoodleRestApi
     * @throws MraException
     */
    public function setReturnFormat(/*string*/ $return_format)/*: MoodleRestApi*/
    {
        $valid_formats = [self::RETURN_XML, self::RETURN_JSON, self::RETURN_ARRAY];
        if (!in_array($return_format, $valid_formats)) {
            throw new MraException("Invalid return format: '$return_format'. Valid formats: " . implode(', ', $valid_formats));
        }
        $this->return_format = $return_format;
        return $this;
    }

    public function request(/*string*/ $wsfunction, array $parameters = [], $method = self::METHOD_GET)
    {
        $this->data = $parameters;
        $this->data['wsfunction'] = $wsfunction;
        if ($method == self::METHOD_GET) {
            return $this->execGetCurl();
        } else {
            return $this->execPostCurl();
        }
    }

    /**
     * @return bool|string
     */
    protected function execGetCurl()
    {
        $this->data['wstoken'] = $this->access_token;
        if ($this->return_format == self::RETURN_ARRAY) {
            $this->data['moodlewsrestformat'] = self::RETURN_JSON;
        } else {
            $this->data['moodlewsrestformat'] = $this->return_format;
        }
        $url = $this->server_url . '?' . http_build_query($this->data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->ssl_verify);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($this->return_format == self::RETURN_ARRAY) {
            $response = json_decode($response);
        }
        return $response;
    }

    /**
     * @return bool|string
     */
    protected function execPostCurl()
    {
        $params['wstoken'] = $this->access_token;
        $params['moodlewsrestformat'] = $this->return_format;
        $params['wsfunction'] = $this->data['wsfunction'];
        $url = $this->server_url . '?' . http_build_query($params);
        unset($this->data['wstoken'], $this->data['moodlewsrestformat'], $this->data['wsfunction']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->ssl_verify);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->data));
        curl_setopt($ch, CURLOPT_POST, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
