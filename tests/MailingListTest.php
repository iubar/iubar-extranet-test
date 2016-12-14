<?php

namespace Extranet;

use League\CLImate\CLImate;
use GuzzleHttp\Client;
use Iubar\Tests\RestApi_TestCase;

/**
 * API Test
 *
 * @author Borgo
 * 
 */
class MailingListTest extends RestApi_TestCase {
    
    const SUBSCRIBE = 'subscribe';
    
    const IS_SUBSCRIBED = 'issubscribed';
    
    const IS_UNSUBSCRIBED = 'isunsubscribed';
    
    const WAS_SUBSCRIBED = 'wassubscribed';

    const UNSUBSCRIBE = 'unsubscribe';
    
    const COUNT = 'count';
    
    const EDIT = 'edit';

    const MAILING_LIST_ID = 1;

    const MAILING_LIST_PROFESSION_ID = 2;

    const FIRST_NAME = 'NomeTest';

    const SECOND_NAME = 'CognomeTest';
    
    const ML_EMAIL_EXAMPLE = 'pippo@iubar.it';
 
    private $force = true;
    
    public static function setUpBeforeClass() {
        parent::init();
        self::$climate->info('MAILING LIST USER EMAIL: ' . self::ML_EMAIL_EXAMPLE);
        $base_url = self::getHost() . '/' . 'mailing-list/';
        self::$client = self::factoryClient($base_url);
    }
    
    public function setUp() {
        // nothing to do
    }
    
    /**
     * Subscribe into the mailing list
     */
    public function testSubscribe() {
        self::$climate->info('testSubscribe...');            
            $array = array(
                'email' => self::ML_EMAIL_EXAMPLE,
                'nome' => self::FIRST_NAME,
                'cognome' => self::SECOND_NAME,
                'idprofessione' => self::MAILING_LIST_PROFESSION_ID,
                'list_id' => self::MAILING_LIST_ID,
                'force' => $this->force                 
            );
                        
            $is_subscribed = $this->isSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID);
            $is_unsubscribed = $this->isUnsubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID);
            $response = $this->sendGetReq(self::SUBSCRIBE, $array);

            if($is_subscribed){   
                $this->assertEquals(self::HTTP_BAD_REQUEST, $response->getStatusCode());
                // $body = $response->getBody()->getContents();
                // $data = json_decode($body, true);
            }else if(!$this->force && $is_unsubscribed){
                $this->assertEquals(self::HTTP_BAD_REQUEST, $response->getStatusCode());
            }else{
                $data = $this->checkResponse($response);
            }

            if($this->force && !$this->isSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID)){
                $this->fail('The user ' . self::ML_EMAIL_EXAMPLE . ' is subscribed to the list ' . self::MAILING_LIST_ID);
            }else if(!$this->force && !$this->wasSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID)){
                $this->fail('The user ' . self::ML_EMAIL_EXAMPLE . ' was not subscribed to the list ' . self::MAILING_LIST_ID);
            }

    }

    /**
     * Edit from the mailing list
     */
    public function testEditSubscription() {
        self::$climate->info('testEditSubscription...');
        if(!$this->force && !$this->wasSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID)){
            $this->fail('The user ' . self::ML_EMAIL_EXAMPLE . ' is NOT subscribed to the list ' . self::MAILING_LIST_ID);
        }
        // Param 'list_id' cannot be modified
        $array = array(
            'email' => self::ML_EMAIL_EXAMPLE,
            'nome' => self::FIRST_NAME,
            'cognome' => self::SECOND_NAME,
            'idprofessione' => self::MAILING_LIST_PROFESSION_ID
        );
        $response = $this->sendGetReq(self::EDIT, $array);
        $data = $this->checkResponse($response);
    }
    

    /**
     * Unsubscribe from the mailing list
     *
     * @uses Can't unsubribe twice but only once. Even if you retry to subscribe, you can't.
     */
    public function testUnsubscribe() {
        self::$climate->info('testUnsubscribe...');
        $array = array(
            'email' => self::ML_EMAIL_EXAMPLE,
            'list_id' => self::MAILING_LIST_ID
        );
        $is_unsub = $this->isUnsubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID);
        $response = $this->sendGetReq(self::UNSUBSCRIBE, $array, self::TIMEOUT_FOR_LONGER_TASK);        
        if($is_unsub){
            $this->assertEquals(self::HTTP_BAD_REQUEST, $response->getStatusCode());
            // $body = $response->getBody()->getContents();
            // $data = json_decode($body, true);
        }else{
            $data = $this->checkResponse($response);
        }

        if($this->isSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID)){
            $this->fail('The user ' . self::ML_EMAIL_EXAMPLE . ' is subscribed to the list ' . self::MAILING_LIST_ID);
        }
    }
    
    /**
     * Count  mailing list users
     */
    public function testCount() {
        self::$climate->info('testCount...');
        $array = array();
        $encoded_data = json_encode($array);
        $response = $this->sendGetReq(self::COUNT, $array);
        
        $body = $this->checkResponse($response);
        $response = $body['data']['count'];
        $this->assertTrue(is_numeric($response));
    }

    private function wasSubscribed($email, $list_id){
        return $this->getSubscribed(self::WAS_SUBSCRIBED, $email, $list_id);
    }
    
    private function isSubscribed($email, $list_id){
        return $this->getSubscribed(self::IS_SUBSCRIBED, $email, $list_id);
    }
    
    private function isUnsubscribed($email, $list_id){
        return $this->getSubscribed(self::IS_UNSUBSCRIBED, $email, $list_id);
    }
    
    private function getSubscribed($route, $email, $list_id){
        $b = false;
        self::$climate->info('getSubscribed() beging...');
        $array = array(
            'email' => self::ML_EMAIL_EXAMPLE,
            'list_id' => self::MAILING_LIST_ID
        );

        $response = $this->sendGetReq($route, $array);
                
        $this->assertContains(self::APP_JSON_CT, $response->getHeader(self::CONTENT_TYPE)[0]);                              
        if($response->getStatusCode() == self::HTTP_OK){
                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);
                 $result = $data['data']['result'];
            if($result==='true'){ 
                $b = true;
            }else if($result==='false'){ 
                $b = false;
            }else{
                self::$climate->error('Response body: ' . PHP_EOL . $body);
                $this->fail('Situazione imprevista');
            }
        }else{
            self::$climate->error('Response code is: ' . $response->getStatusCode());
            $this->fail('Situazione imprevista');
        }
        
        // Getting data
        // $data = json_decode($response->getBody()->getContents(), true);
        
        self::$climate->info('..,isSubscribed() end');
        return $b;
    }
}