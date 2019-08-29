<?php

namespace Extranet;

use GuzzleHttp\Psr7\Request;
use Iubar\Tests\RestApi_TestCase;

class EchoDataJwt extends RestApi_TestCase {

    //const TOKEN_ROUTE = 'jwt/token/email/daniele.montesi@iubar.it/apikey/abcd123';
    const TOKEN_ROUTE_2 = 'jwt/token';
    const DATA_ROUTE = 'jwt/data';

    const EMAIL = 'daniele.montesi@iubar.it';

    private static $token = null;

    public static function setUpBeforeClass() : void {
        parent::init();
        self::$client = self::factoryClient();
    }

    /**
     * Recupera il token jwt
     */
    public function testPostToken(){
    	self::$climate->comment('testPostToken');
    	$request_data = [ 'email' => self::EMAIL ];
    	$headers = ['Content-Type' => 'application/x-www-form-urlencoded', 'X-Requested-With' => 'XMLHttpRequest'];
    	$request = new Request(self::POST, self::TOKEN_ROUTE_2, $headers, json_encode($request_data));
    	$response = self::$client->send($request, [
    		'timeout' => self::TIMEOUT
    	]);

    	$data = $this->checkResponse($response);
    	self::$token = $data['data']['token'];
    }

    /**
     * Richiede i dati, passando come parametri il token jwt e l'indirizzo email dell'utente
     */
    public function testGetData() {
    	self::$climate->comment('testGetData()');
    	$headers = ['X-Requested-With' => 'XMLHttpRequest'];
    	$route = self::DATA_ROUTE . '?email=' . self::EMAIL . '&token=' . self::$token;
    	// self::$climate->comment('route: ' . $route);
    	$request = new Request(self::GET, $route, $headers);
    	$response = self::$client->send($request, [
    		'timeout' => self::TIMEOUT
    	]);

    	$this->checkResponse($response);
    }

}
