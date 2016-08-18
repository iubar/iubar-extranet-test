<?php

namespace Extranet;

use League\CLImate\CLImate;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Extranet\Base\Extranet_TestCase;

/**
 * API Test
 *
 * @author Borgo, Matteo
 * @global env transact_email_user
 * @global env host
 * @global env port
 * @global env user
 * @global env password
 */
class Others extends Extranet_TestCase {

    const ELEM_LIMIT = 3;
 
    const GET = 'get';

    const TWITTER = 'twitter';

    const LENGTH = 100;

    const RSS = 'rss';
 
    /**
     * Create a Client
     */
    public function setUp() {
        parent::init();        
    }

    /**
     * Test Twitter
     *
     * @uses Some tweet could be filtered so the ELEM_LIMIT and the number of tweet could be different
     */
    public function testTwitter() {
        self::$climate->info('Testing Twitter...');
        $response = null;
        try {
            $array = array(
                'limit' => self::ELEM_LIMIT
            );
            $response = $this->sendRequest(self::GET, self::TWITTER, $array, self::TIMEOUT);
            // echo PHP_EOL . $response->getBody();
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        $body = $this->checkResponse($response);
        $data = $body['data'];
        $this->assertEquals(self::ELEM_LIMIT, count($data));
        $first_obj = $data[0];
        $this->assertArrayHasKey('short_text', $first_obj);
        
//         foreach ($response->getHeaders() as $name => $values) {
//             echo $name . ': ' . implode(', ', $values) . PHP_EOL;         
//         }
        
    }

    /**
     * Test Rss
     */
    public function testRss() {
        self::$climate->info('Testing Rss...');
        $response = null;
        try {
            $array = array(
                'limit' => self::ELEM_LIMIT,
                'length' => self::LENGTH
            );
            $response = $this->sendRequest(self::GET, self::RSS, $array, self::TIMEOUT);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        $data = $this->checkResponse($response);
        $this->assertEquals(self::ELEM_LIMIT, count($data));
    }


    

    


}