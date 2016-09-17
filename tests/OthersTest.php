<?php

namespace Extranet;

use League\CLImate\CLImate;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Iubar\Tests\RestApi_TestCase;

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
class OthersTest extends RestApi_TestCase {

    const TWITTER_ROUTE = 'twitter';
    
    const RSS_ROUTE = 'rss';
    
    const CHECKSUM_ROUTE = 'checksum';
    
    CONST IP2GEO_ROUTE = 'ip2geo';
    
    CONST GEODECODER_ROUTE = 'geodecode';
    
    CONST GEOREVERSEDECODER_ROUTE = 'georeversedecode';
        
    const ELEM_LIMIT = 3;

    const LENGTH = 100;
 
    public static function setUpBeforeClass() {
        parent::init();
    }
    
    /**
     * Create a Client
     */    
    public function setUp() {
        self::$client = self::factoryClient(self::getHost() . '/');        
    }

    /**
     * Test for the ClientTwitter.php class
     *
     * @uses Some tweet could be filtered so the ELEM_LIMIT and the number of tweet could be different
     */
    public function testTwitter() {
        self::$climate->info('Testing Twitter...');
        $array = array(
            'limit' => self::ELEM_LIMIT
        );
        $response = $this->sendGetReq(self::TWITTER_ROUTE, $array);
        // echo PHP_EOL . $response->getBody()->getContents();

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
     * Test for the ClientRss.php class
     */
    public function testRss() {
        self::$climate->info('Testing Rss...');
        $array = array(
            'limit' => self::ELEM_LIMIT,
            'length' => self::LENGTH
        );
        $response = $this->sendGetReq(self::RSS_ROUTE, $array);
        $data = $this->checkResponse($response);
        $this->assertEquals(self::ELEM_LIMIT, count($data));
    }

     /**
     * Test for the Checksum.php class
     */
    public function testChecksum() {
        self::$climate->info('Testing Checksum...');

        if(self::getHost() == 'http://www.iubar.it/extranet/api'){
            $host = 'iubar.it';
 
            $file = 'downloads/assistenza/TeamViewerQS_9_it.exe';
            $remote_file = '/web/htdocs/www.' . $host . '/home/' . $file;
            
            $remote_url = 'http://www.' . $host . '/' . $file;        
            $exists = $this->remoteFileExists($remote_url);
            if (!$exists) {
               $this->fail('Remote file ' . $file  . ' does not exist on host ' . $host);
            }
        }else if(self::getHost() == 'http://extranet/api'){
            $host = 'extranet';
 
            $file = 'public/img/iubar_logo_75.png';
            $remote_file = 'C:/Users/Daniele/workspace_php/php_www_iubar_extranet/www/' . $file;
            
            $remote_url = 'http://' . $host . '/' . $file;
            $exists = $this->remoteFileExists($remote_url);
            if (!$exists) {
                $this->fail('Remote file ' . $file  . ' does not exist on host ' . $host);
            }
        }else{
            $this->fail('Test config error');
        }
                
        $array = array(
            'file' => $remote_file
        );
        $response = $this->sendGetReq(self::CHECKSUM_ROUTE, $array);
        $data = $this->checkResponse($response); 
 
        $this->assertArrayHasKey('hash', $data['data']);
        $this->assertEquals(40, strlen($data['data']['hash']));

    }
    
    /**
     * Test for the Benchmark.php class
     */
    public function testBenchmark() {
        self::$climate->info('Testing Benchmark(...');
        $bench = new \Ubench();            
        $bench->start();            
        $sec = 15;
        $array = array(); // no params required
        $route = null;
        if(self::getHost()  == 'http://www.iubar.it/extranet/api'){
            $route = 'http://www.iubar.it/extranet/sandbox/benchmark';
        }else if(self::getHost()  == 'http://extranet/api'){
            $route = 'http://extranet/sandbox/benchmark';
        }else{
            $this->fail('Test config error');
        }
        
        
        $response = $this->sendGetReq($route. '/' . $sec, $array, 20);
        $data = $this->checkResponse($response);          
        $bench->end();
        self::$climate->info('TIME: ' . $bench->getTime());
        self::$climate->info('You should subtract ' . $sec . ' seconds');
        $this->assertEquals($sec, $data['data']['number']);
    }
    
    public function testGeoDecoder() {
        // e.g.: http://extranet/api/geodecode?address=Via%20Arco%20di%20Augusto%2076,%20Fano%20(PU),%20Italy
        
        self::$climate->info('Testing testIp2Geo(...');
        $address = 'Via Arco di Augusto 76, Fano (PU), Italy';
        $array = array(
            'address' => $address
        );
        $response = $this->sendGetReq(self::GEODECODER_ROUTE, $array);
        $data = $this->checkResponse($response);
        
        $this->assertEquals('Via Arco D\'Augusto', $data['data']['results'][0]['address_components'][2]['long_name']);

    }
    
    public function testGeoReverseDecoder() {
        // e.g.: http://extranet/api/georeversedecode?lat=43.8443835&lng=13.0163099
        
        self::$climate->info('Testing testGeoReverseDecoder(...');
        $array = array(
            'lat' => '43.8446287',
            'lng' => '13.0166416'
        );
        $response = $this->sendGetReq(self::GEOREVERSEDECODER_ROUTE, $array);
        //$data = $this->checkResponse($response);
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('Piazza Andrea Costa', $data['data']['results'][0]['address_components'][1]['long_name']);
    }
    
    public function testIp2Geo() {
        
        // e.g.: http://extranet/api/ip2geo?ip=95.224.129.129
        self::$climate->info('Testing testIp2Geo(...');
        $ip = '95.224.129.129';
        $array = array(
            'ip' => $ip
        );
        $response = $this->sendGetReq(self::IP2GEO_ROUTE, $array);
        $data = $this->checkResponse($response);
        // $data = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('geoname_id', $data['data']['city']);
        $this->assertEquals('Fano', $data['data']['city']['names']['en']);
        
    }

    /*
     * Duplicato del metodo omonimo della classe https://github.com/iubar/iubar-php-common/blob/master/src/Iubar/Web/WebUtil.php
     */
    private function remoteFileExists($url) {
        $curl = curl_init($url);    
        //don't fetch the actual page, you only want to check the connection is ok
        curl_setopt($curl, CURLOPT_NOBODY, true);    
        //do request
        $result = curl_exec($curl);    
        $ret = false;    
        //if request did not fail
        if ($result !== false) {
            //if request was ok, check response code
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);    
            if ($statusCode == 200) {
                $ret = true;
            }
        }    
        curl_close($curl);    
        return $ret;
    }
    
    
    
    
    
    /**
     * Test for the Stripe.php class
     */
    //     public function testStripeGet() {
    //         // TODO: vedere con Daniele. La rotta 'stripe' non è ancora implementata
    //     }
    
    //     public function testStripePost() {
    //         // TODO: vedere con Daniele. La rotta 'stripe' non è ancora implementata
    //     }
    
    /**
     * Test for the ValidazioneCedolino.php class
     */
    //     public function testValidazioneCedolino() {
    //         $route = 'validazione-cedolino';
    //         $method = 'POST';
    //     }

}