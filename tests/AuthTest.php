<?php

namespace Extranet;

use Iubar\Tests\RestApi_TestCase;

class AuthTest extends RestApi_TestCase {
    
    const AUTH_ROUTE = 'auth';
    
    public static function setUpBeforeClass() {
        parent::init();
        self::$client = self::factoryClient();  
    }
    
    public function setUp() {
        // nothing to do
    }

    public function testAuth1() {  // Send a GET request
        self::$climate->comment('testAuth1()...');
        $array = array(
            'data' => 'Hello World !'
        );
        $response = $this->sendGetReq(self::AUTH_ROUTE, $array);
        $data = $this->checkResponse($response);       
        // TODO: $this->assert...;
    }
    
    public function testAuth2() { // Send a GET request (using Psr7\Request)
        self::$climate->comment('testAuth2()...');
        $array = array(
            'data' => 'Hello World !'
        );
        $request = new Request(self::GET, self::AUTH_ROUTE . '?' . $encoded_data, $headers);
        $response = self::$client->send($request, [
            'timeout' => self::TIMEOUT
        ]);
        $data = $this->checkResponse($response);
        // TODO: $this->assert...;
    }    
    
   
}
