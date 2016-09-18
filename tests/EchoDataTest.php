<?php

namespace Extranet;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7;
use Iubar\Tests\RestApi_TestCase;

/*
 * @see: http://stackoverflow.com/questions/4007969/application-x-www-form-urlencoded-or-multipart-form-data
 * @see: https://httpbin.org/
 */
class EchoDataTest extends RestApi_TestCase {
    
    const ECHO_ROUTE = 'echo';
    
    public static function setUpBeforeClass() {
        parent::init();
        self::$client = self::factoryClient();           
    }
    
    // From Guzzle's doc:
    // Use 'form_params' for application/x-www-form-urlencoded requests,
    // and 'multipart' for multipart/form-data requests.
    //
    // @see: 'form_params' http://guzzle.readthedocs.io/en/latest/request-options.html?highlight=getconfig#form-params
    // @see: 'multipart' http://guzzle.readthedocs.io/en/latest/request-options.html?highlight=getconfig#multipart
    //
    // As it is explained in W3, the content type "multipart/form-data" should be used for submitting forms that contain files, non-ASCII data, and binary data.
    //
    // POST requests in Guzzle are sent with an application/x-www-form-urlencoded Content-Type header if POST fields are present but no files are being sent in the POST. If files are specified in the POST request, then the Content-Type header will become multipart/form-data.
    
    public function setUp() {
        // nothing to do
    }

    public function testEchoGet() {  // Send a GET request
        self::$climate->comment('Testing Echo->get(...');
        $array = array(
            'Foo' => 'Bar1'
        );

        $data = [
            'headers' => [
                'User-Agent' => 'testing/1.0',
                'Accept'     => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest' // for Whoops' JsonResponseHandler
            ],
            'query' => $array
        ];
                
        $response = self::$client->request(self::GET, self::ECHO_ROUTE, $data);
        $data = $this->checkResponse($response);
        $this->assertJsonStringEqualsJsonString(json_encode($array), json_encode($data['data']));
    }
    
    public function testEchoGet2() { // Send a GET request (using Psr7\Request)
        self::$climate->comment('Testing Echo->get(...');
        $array = array(
            'Foo' => 'Bar2'
        );
        $headers = ['X-Requested-With' => 'XMLHttpRequest']; // Ok
        $encoded_data = http_build_query($array, null, '&');
        $request = new Request(self::GET, self::ECHO_ROUTE . '?' . $encoded_data, $headers);
        $response = self::$client->send($request, [
            'timeout' => self::TIMEOUT
        ]);
        $data = $this->checkResponse($response);
        $this->assertJsonStringEqualsJsonString(json_encode($array), json_encode($data['data']));
    }    
    
    public function testEchoPost() { // Send an 'application/x-www-form-urlencoded' POST request (using Psr7\Request)
        
        self::$climate->comment('Testing Echo->post(...');
        $array = array(
            'Foo' => 'Bar1'
        );
        $encoded_data = http_build_query($array, null, '&'); // @see: http://php.net/manual/en/function.http-build-query.php
        self::$climate->info('Request data: ' . $encoded_data);
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded', 'X-Requested-With' => 'XMLHttpRequest'];
        $request = new Request(self::POST, self::ECHO_ROUTE, $headers, $encoded_data);
        $response = self::$client->send($request, [
            'timeout' => self::TIMEOUT
        ]);        
        $data = $this->checkResponse($response);        
        $this->assertJsonStringEqualsJsonString(json_encode($array), json_encode($data['data']));
    }
    
    public function testEchoPost2() { // Post Json data
        
        self::$climate->comment('Testing Echo->post(...');
        $array = array(
            'Foo' => 'Bar2'
        );        
        $json = json_encode($array);
        self::$climate->info('Request data: ' . $json);     
        $response = self::$client->request(self::POST, self::ECHO_ROUTE, [
            'headers' => [
                'Content-Type' => 'application/json; charset=UTF-8', 
                'X-Requested-With' => 'XMLHttpRequest'
                ],
            'body' => $json 
        ]);
        $data = $this->checkResponse($response);
        $this->assertJsonStringEqualsJsonString($json, json_encode($data['data']));
    }
   
        
    public function testEchoPost3() { // Send an 'application/x-www-form-urlencoded' POST request
        
        self::$climate->comment('Testing Echo->post(...');
        $array = array(
            'Foo' => 'Bar3'
        );
        $json = json_encode($array);
        self::$climate->info('Request data: ' . $json);
        $response = self::$client->request(self::POST, self::ECHO_ROUTE, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded', // obbligatorio quando si usa 'form_params'
                'X-Requested-With' => 'XMLHttpRequest'
            ],            
           'form_params' => $array, // This option cannot be used with body, multipart, or json
		   'connect_timeout' => self::TIMEOUT, 	// The number of seconds to wait while trying to connect to a server
		   'timeout' => self::TIMEOUT 			// The timeout of the request in seconds
		]);                       
        $data = $this->checkResponse($response);
        $this->assertJsonStringEqualsJsonString($json, json_encode($data['data']));
    }    
    
    
// MULTIPART 


    public function testEchoPost6() { // Send a 'multipart/form-data' POST request
        self::$climate->comment('Testing Echo->post(...');
        $array = [
       
            'headers'  => ['X-Requested-With' => 'XMLHttpRequest'],
            'multipart' => [
                [
                    'name'     => 'John',
                    'contents' => 'Worker',
                    
                ],
                 [
                     'name'     => 'Bob',
                     'contents' => fopen(__FILE__, 'r')
                 ],
                [
                    'name'     => 'Alice',
                    'contents' => fopen(__FILE__, 'r'),
                    'filename' => 'custom_filename.txt'
                ],
            ]
        ];
        
        $encoded_data = json_encode($array);
        self::$climate->info('Request data: ' . $encoded_data);
        $response = self::$client->request(self::POST, self::ECHO_ROUTE, $array);
        $data = $this->checkResponse($response);
        $actual_value = $data['data']['files']['Alice']['name'];
        $expected_value = $array['multipart']['2']['filename'];
        $this->assertEquals($expected_value, $actual_value);
        
    }
    
    /**
     * Metodo incompleto: restituisce error 'Fatal error: Class 'GuzzleHttp\Psr7\stream_for' not found'
     * @see http://guzzle.readthedocs.io/en/latest/psr7.html#streams
     */
    public function NO_testEchoPost4() { // Send a 'multipart/form-data' POST request (using Psr7\Request)
    
        self::$climate->comment('Testing Echo->post(...');
        $array = array(
            'name'     => 'field_name',
            'contents' => 'abc'
        );
        $json = json_encode($array);
        self::$climate->info('Request data: ' . $json);
    
        // $stream = GuzzleHttp\Psr7\stream_for('foo');
        $body = new Psr7\stream_for(http_build_query($array)); // http://guzzle.readthedocs.io/en/latest/psr7.html#streams
        $boundary = uniqid();
        $headers = ['Content-Type' => 'multipart/form-data; boundary=' . $boundary];
        //$headers = ['Content-Type' => 'multipart/form-data'];
        $request = new Request(self::POST, self::ECHO_ROUTE, $headers, $stream);
        $response = self::$client->send($request, [
            'timeout' => self::TIMEOUT
        ]);
        $data = $this->checkResponse($response);
        $this->assertJsonStringEqualsJsonString($json, json_encode($data['data']));
    }
    
}
