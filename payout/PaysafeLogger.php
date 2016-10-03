<?php

/**
 * @author marcoeble
 *
 */
class PaysafeLogger
{

    private $filename = "";

    public function __construct($filename = "log.txt")
    {
        $this->filename = $filename;
        setlocale(LC_TIME, "de_DE");
    }

    /**
     * log request to file
     * @param mixed $request
     * @param mixed $http
     * @param mixed $response
     * @return null
     */
    public function log($request, $http, $response)
    {
        file_put_contents($this->filename, strftime("%A, %d. %B %Y H:i:s"), FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, 'Request:', FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, print_r($request, true), FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, 'HTTP:', FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, print_r($http, true), FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, 'Response:', FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, print_r($response, true), FILE_APPEND | LOCK_EX);
        file_put_contents($this->filename, '--------------------------------------------', FILE_APPEND | LOCK_EX);
    }
}
