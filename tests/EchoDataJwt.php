<?php

namespace Extranet;

use GuzzleHttp\Psr7\Request;
use Iubar\Tests\RestApi_TestCase;

class EchoDataJwt extends RestApi_TestCase {

    const TOKEN_ROUTE = 'jwt/token/email/daniele.montesi@iubar.it/apikey/abcd123';
    const TOKEN_ROUTE_2 = 'jwt/token';
    const DATA_ROUTE = 'jwt/data/token/';

    const EMAIL = 'daniele.montesi@iubar.it';
    const API_KEY = 'abcd123';

    private static $token = null;

    public static function setUpBeforeClass() {
        parent::init();
        self::$client = self::factoryClient();
    }

    public function testPostToken(){
    	self::$climate->comment('testPostToken');
    	$request_data = [ 'email' => self::EMAIL, 'apikey' => self::API_KEY ];
    	$headers = ['Content-Type' => 'application/x-www-form-urlencoded', 'X-Requested-With' => 'XMLHttpRequest'];
    	$request = new Request(self::POST, self::TOKEN_ROUTE_2, $headers, json_encode($request_data));
    	$response = self::$client->send($request, [
    		'timeout' => self::TIMEOUT
    	]);

    	$data = $this->checkResponse($response);
    	self::$token = $data['data']['token'];
    }

    public function testGetData() {
    	self::$climate->comment('testGetData()');
    	$headers = ['X-Requested-With' => 'XMLHttpRequest'];
    	$request = new Request(self::GET, self::DATA_ROUTE. self::$token, $headers);
    	$response = self::$client->send($request, [
    		'timeout' => self::TIMEOUT
    	]);

    	$this->checkResponse($response);
    }

}
