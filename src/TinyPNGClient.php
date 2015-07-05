<?php

namespace Cadrone\TinyPNGClient;

/**
 * Description of TinyPNGClient
 *
 * @author Pavel Petrov <pavepet@gmail.com>
 */
class TinyPNGClient
{

    /**
     * Instance of php-curl-class Curl
     * 
     * @see https://github.com/php-curl-class/php-curl-class
     * @var \Curl\Curl
     */
    private $curl;

    public function __construct($key, $curl)
    {
        $this->curl = $curl;
        $this->curl->setOpt(CURLOPT_USERPWD, "api:$key");
    }

    public function shrink($inputFilePath)
    {
        $this->curl->setOpt(CURLOPT_HEADER, true);
        $this->curl->setOpt(CURLOPT_BINARYTRANSFER, true);
        $this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, true);
        
        $response = $this->curl->post("https://api.tinify.com/shrink", file_get_contents($inputFilePath));

        if (false === $response) {
            throw new TinyPNGRequestException($this->curl->curl_error_message, $this->curl->curl_error_code);
        }
        
        if (201 !== $this->curl->http_status_code) {
            throw new TinyPNGResponseException($this->curl->http_error_message, $this->curl->http_status_code);
        }
        
        return $this;
    }
    
    public function downloadImage($outputFile)
    {
        if (!$this->curl->response) {
            throw new \LogicException("Do a shrink request before downloading an image.");
        }
        
        $this->curl->setOpt(CURLOPT_HEADER, false);
        $location = $this->curl->response_headers["Location"];
        
        $this->curl->get($location);
        file_put_contents($outputFile, $this->curl->raw_response);
    }

    public function setCertFile($filePath)
    {
        $this->curl->setOpt(CURLOPT_CAINFO, $filePath);
    }

}
