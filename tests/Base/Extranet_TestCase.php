<?php

namespace Extranet\Base;

use Iubar\Tests\RestApi_TestCase;
use League\CLImate\CLImate;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class Extranet_TestCase extends RestApi_TestCase {
    
    const IUBAR_EXTRANET_API = 'http://www.iubar.it/extranet/api/';
    
    // seconds
    const TIMEOUT = 4;
    
    const GET = 'get';
    
    // easily output colored text and special formatting
    protected static $climate = null;
       
    protected function init(){
        
        self::$climate = new CLImate();
        
        // Base URI is used with relative requests
        // You can set any number of default request options.
        $this->client = new Client([
            'base_uri' => self::IUBAR_EXTRANET_API,
            'timeout' => self::TIMEOUT
        ]);
    }
    

    protected function sleep($seconds){
        self::$climate->info('Waiting ' . $seconds . ' seconds...');
        sleep($seconds);
    }
    
}