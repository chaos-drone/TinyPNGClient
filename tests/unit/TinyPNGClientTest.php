<?php

namespace Cadrone\TinyPNGClient;

/**
 * Description of TinyPNGClient
 *
 * @author Pavel Petrov <pavepet@gmail.com>
 */
class TinyPNGClientTest extends \PHPUnit_Framework_TestCase
{

    public function testClientSendsAndReceivesProperDataToPNGAndDownloadsOptimizedImage()
    {
        $apiKey = "123";
        $apiUrl = "https://api.tinify.com/shrink";
        $filePathPNG = realpath(__DIR__) . "/../data/png.png";
        $controlOutputFile = realpath(__DIR__) . "/../data/tinypng.png";
        $outputFile = realpath(__DIR__) . "/../data/tmp/tinypng.png";
        
        $certFileName = 'cert.pem';
        $responseHeaders = array(
            "Compression-Count" => 1,
            "Location" => "https://api.tinify.com/output/2xnsp7jn34e5.png",
            "Content-Type" => "application/json; charset=utf-8",
        );
        $response = file_get_contents($controlOutputFile);
        
        if (true === file_exists($outputFile)) {
            unlink($outputFile);
        }
        
        $this->assertFileNotExists($outputFile, "Outputfile does not exist before download.");

        $curl = $this->getMockBuilder("Curl\Curl")
                ->setMethods(array('post', 'get'))
                ->getMock()
        ;

        $curl->expects($this->once())
                ->method("post")
                ->with($apiUrl, file_get_contents($filePathPNG))
        ;
        $curl->http_status_code = 201;
        
        $client = new TinyPNGClient($apiKey, $curl);
        $shrinkReturn = $client->shrink($filePathPNG);
        
        $this->assertSame($client, $shrinkReturn, "The 'shrink' method implements method chaining.");
        $this->assertEquals("api:$apiKey", $curl->getOpt(CURLOPT_USERPWD), "Client sets proper authentication data.");
        $this->assertTrue($curl->getOpt(CURLOPT_BINARYTRANSFER), "CURLOPT_BINARYTRANSFER is set to true for the request.");
        $this->assertTrue($curl->getOpt(CURLOPT_RETURNTRANSFER), "CURLOPT_RETURNTRANSFER is set to true for the request.");
        $this->assertTrue($curl->getOpt(CURLOPT_HEADER), "CURLOPT_HEADER is set to true for the request.");
        $this->assertTrue($curl->getOpt(CURLOPT_SSL_VERIFYPEER), "CURLOPT_SSL_VERIFYPEER is set to true for the request.");
        
        $client->setCertFile($certFileName);
        $this->assertEquals($certFileName, $curl->getOpt(CURLOPT_CAINFO), "CURLOPT_CAINFO is set correctly.");
        
        //mock headers
        $curl->response = true;
        $curl->response_headers = $responseHeaders;
        
        $curl->expects($this->once())
                ->method('get')
                ->with($responseHeaders["Location"])
                ->will($this->returnValue($response))
        ;
        
        $client->downloadImage($outputFile);
        
        $this->assertTrue($curl->getOpt(CURLOPT_RETURNTRANSFER), "CURLOPT_RETURNTRANSFER is set to true for the response.");
        $this->assertTrue($curl->getOpt(CURLOPT_SSL_VERIFYPEER), "CURLOPT_SSL_VERIFYPEER is set to true for the response.");
        $this->assertFalse($curl->getOpt(CURLOPT_HEADER), "CURLOPT_HEADER is set to false for the response.");
        $this->assertFileExists($outputFile);
//        $this->assertFileEquals($controlOutputFile, $outputFile);
    }
    
    public function testClientThrowsExceptionOnErrorResponse()
    {
        $this->setExpectedException("Cadrone\TinyPNGClient\TinyPNGResponseException");
        
        $filePathPNG = realpath(__DIR__) . "/../data/png.png";
        
        $curl = $this->getMockBuilder("Curl\Curl")
                ->setMethods(array('post'))
                ->getMock()
        ;
        
        $curl->method('post')
                ->will($this->returnValue(true));
        
        $curl->http_status_code = 415;
        
        $client = new TinyPNGClient('123', $curl);
        $client->shrink($filePathPNG);
    }
    
    public function testClientThrowExceptionOnDownloadingImageWithotShrinkRequest()
    {
        $this->setExpectedException("LogicException");
        
        $curl = $this->getMockBuilder("Curl\Curl")
                ->setMethods(array('get'))
                ->getMock()
        ;
        
        $client = new TinyPNGClient('123', $curl);
        $client->downloadImage("output.png");
    }
    
    public function testClientThrowsExceptionOnCurlError()
    {
        $exceptionName = "Cadrone\TinyPNGClient\TinyPNGRequestException";
        $exceptionMessage = "Exception message";
        $exceptionCode = 567;
        $filePathPNG = realpath(__DIR__) . "/../data/png.png";
        
        $this->setExpectedException($exceptionName, $exceptionMessage, $exceptionCode);
        
        /* @var $curl \Curl\Curl */
        $curl = $this->getMockBuilder("Curl\Curl")
                ->setMethods(array('post'))
                ->getMock()
        ;
        
        $curl->curl_error_message = $exceptionMessage;
        $curl->curl_error_code = $exceptionCode;
        
        $curl->expects($this->once())
                ->method("post")
                ->will($this->returnValue(false))
        ;
        
        $client = new TinyPNGClient("mykey", $curl);
        
        $client->shrink($filePathPNG);
    }

}
