<?php

namespace Extranet\Base;

use Iubar\Tests\RestApi_TestCase;
use League\CLImate\CLImate;

abstract class Extranet_TestCase extends RestApi_TestCase {
    
    // Stop here and mark this test as incomplete.
    //         $this->markTestIncomplete(
    //             'This test has not been implemented yet.'
    //         );
    
    // protected static $iubar_extranet_api = 'http://www.iubar.it/extranet/api/';
    protected static $iubar_extranet_api = 'http://extranet/api/';
    
    // easily output colored text and special formatting
    protected static $climate = null;
       
    protected static function init(){        
        self::$climate = new CLImate();       
    }
    
    protected function sleep($seconds){
        self::$climate->info('Waiting ' . $seconds . ' seconds...');
        sleep($seconds);
    }
    
}