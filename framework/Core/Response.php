<?php

namespace Kazinduzi\Core;

/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/).
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 *
 * @link      http://kazinduzi.com
 *
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 */
class Response
{
    public static $statuses =
        [
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            507 => 'Insufficient Storage',
            509 => 'Bandwidth Limit Exceeded',
    ];
    private static $instance;
    protected $status = 200;
    protected $status_text = 'OK';
    protected $cgi;
    protected $headers = [];
    protected $output = null;
    protected $mime_types =
        [
            'text/html'                => 'html',
            'application/xhtml+xml'    => 'html',
            'application/xml'          => 'xml',
            'text/xml'                 => 'xml',
            'text/javascript'          => 'js',
            'application/javascript'   => 'js',
            'application/x-javascript' => 'js',
            'application/json'         => 'json',
            'text/x-json'              => 'json',
            'application/rss+xml'      => 'rss',
            'application/atom+xml'     => 'atom',
            '*/*'                      => 'html',
            'default'                  => 'html',
        ];

    /**
     * @return type
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            return self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Sets up the response with a body and a status code.
     *
     * @param string $output
     * @param int    $status
     * @param array  $headers
     */
    public function __construct($output = null, $status = 200, array $headers = [])
    {
        foreach ($headers as $header) {
            $this->addHeader($header);
        }
        $this->output = $output;
        $this->status = $status;
        $cgi = (strpos(php_sapi_name(), 'cgi') !== false);
        $this->setCgi($cgi);
    }

    /**
     * Optionally send responses as if in CGI mode. (This changes how the
     * status header is sent.).
     *
     * @param bool $cgi True to force into CGI mode, false to not do so.
     *
     * @return void
     */
    public function setCgi($cgi)
    {
        $this->cgi = (bool) $cgi;

        return $this;
    }

    /**
     * Is the transport sending responses in CGI mode?
     *
     * @return bool
     */
    public function isCgi()
    {
        return (bool) $this->cgi;
    }

    /**
     * @param type $status
     *
     * @return Response
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return type
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the HTTP status text for the message.
     *
     * @param string $text The status text.
     */
    public function setStatusText($text)
    {
        $text = trim(str_replace(["\r", "\n"], '', $text));
        $this->status_text = $text;

        return $this;
    }

    /**
     * Returns the HTTP status text for the message.
     *
     * @return string
     */
    public function getStatusText()
    {
        return $this->status_text;
    }

    /**
     * @return type
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * @param type $header
     *
     * @return Response
     */
    public function addHeader($name, $value, $replace = true)
    {
        if ($replace) {
            $this->headers[$name] = $value;
        } else {
            $this->headers[] = [$name, $value];
        }

        return $this;
    }

    /**
     * @return type
     */
    public function output($output = null)
    {
        if ($output) {
            $this->output = $output;

            return $this;
        }

        return $this->output;
    }

    /**
     * Sends the headers if they haven't already been sent.  Returns whether
     * they were sent or not.
     *
     * @return bool
     */
    public function sendHeaders()
    {
        if (!headers_sent()) {
            if ($this->isCgi()) {
                header('Status: '.$this->status.' '.static::$statuses[$this->status]);
            } else {
                $protocol = $_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
                header($protocol.' '.$this->status.' '.static::$statuses[$this->status]);
            }
            foreach ($this->headers as $name => $value) {
                if (is_int($name) and is_array($value)) {
                    isset($value[0]) and $name = $value[0];
                    isset($value[1]) and $value = $value[1];
                }
                is_string($name) && $value = "{$name}: {$value}";
                header($value, true);
            }

            return true;
        }

        return false;
    }

    /**
     * @param type $send_headers
     */
    public function send($send_headers = false)
    {
        if ($send_headers) {
            $this->sendHeaders();
        }
        if ($this->output != null) {
            echo $this->output;
        }
    }
}
