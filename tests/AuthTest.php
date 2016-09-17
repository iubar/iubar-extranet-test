<?php

namespace Extranet;

use Iubar\Tests\RestApi_TestCase;

class AuthTest extends RestApi_TestCase {
    
    const ECHO_ROUTE = 'auth';
    
    public static function setUpBeforeClass() {
        parent::init();
        self::$client = self::factoryClient(self::getHost() . '/echo');
    }
    
    /**
     * Create a Client
     */
    public function setUp() {
        
    }

    public function testAuth1() {  // Send a GET request
        self::$climate->info('Testing Echo->get(...');
        $array = array(
            'data' => 'Hello World !'
        );
        $response = $this->sendGetReq(self::ECHO_ROUTE, $array);
        $data = $this->checkResponse($response);       
        // TODO: $this->assert...;
    }
    
    public function testAuth2() { // Send a GET request (using Psr7\Request)
        self::$climate->info('Testing Echo->get(...');
        $array = array(
            'data' => 'Hello World !'
        );
        $request = new Request(self::GET, self::ROUTE_BASE . $route, $headers, $encoded_data);
        $response = self::$client->send($request, [
            'timeout' => self::TIMEOUT
        ]);
        $data = $this->checkResponse($response);
        // TODO: $this->assert...;
    }    
    
   
}
