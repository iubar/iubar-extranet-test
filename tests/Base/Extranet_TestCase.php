<?php

namespace Extranet\Base;

use Iubar\Tests\RestApi_TestCase;
use League\CLImate\CLImate;

abstract class Extranet_TestCase extends RestApi_TestCase {
    
    // Stop here and mark this test as incomplete.
    //         $this->markTestIncomplete(
    //             'This test has not been implemented yet.'
    //         );
        
    // easily output colored text and special formatting
    protected static $climate = null;
       
    protected static function init(){        
        self::$climate = new CLImate();       
    }
    
    protected function sleep($seconds){
        self::$climate->info('Waiting ' . $seconds . ' seconds...');
        sleep($seconds);
    }
    
    protected static function getHost(){
        $http_host = getenv('HTTP_HOST');
        if(!$http_host){
            $this->fail('Wrong config');
        }
    }
    
}