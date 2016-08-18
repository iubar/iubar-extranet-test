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
class MailingList extends Extranet_TestCase {
    
    const MAILING_LIST = 'mailing-list/';

    const SUBSCRIBE = 'subscribe';

    const UNSUBSCRIBE = 'unsubscribe';
    
    const EDIT = 'edit';

    const MAILING_LIST_ID = 1;

    const MAILING_LIST_PROFESSION_ID = 2;

    const FIRST_NAME = 'NomeTest';

    const SECOND_NAME = 'CognomeTest';
    
    const ML_EMAIL_EXAMPLE = 'pippo@iubar.it';
 
    /**
     * Create a Client
     */
    public function setUp() {        
        parent::init();              
        self::$climate->info('MAILING LIST USER EMAIL: ' . self::ML_EMAIL_EXAMPLE);
    }

    /**
     * Subscribe into the mailing list
     */
    public function testSubscribe() {
        self::$climate->info('testSubscribe...');
        if(!$this->isSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID)){
            $response = null;
            try {
                $array = array(
                    'email' => self::ML_EMAIL_EXAMPLE,
                    'nome' => self::FIRST_NAME,
                    'cognome' => self::SECOND_NAME,
                    'idprofessione' => self::MAILING_LIST_PROFESSION_ID,
                    'list_id' => self::MAILING_LIST_ID
                );
                $response = $this->sendRequest(self::GET, self::MAILING_LIST . self::SUBSCRIBE, $array, self::TIMEOUT);
            } catch (RequestException $e) {
                $this->handleException($e);
            }
            $data = $this->checkResponse($response);
            
            if(!$this->isSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID)){
                $this->fail('The user ' . self::ML_EMAIL_EXAMPLE . ' is NOT subscribed to the list ' . self::MAILING_LIST_ID);
            }
            
        }
    }

    /**
     * Edit from the mailing list
     */
    public function testEditSubscription() {
        self::$climate->info('testEditSubscription...');
        
        if(!$this->isSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID)){
            $this->fail('The user ' . self::ML_EMAIL_EXAMPLE . ' is NOT subscribed to the list ' . self::MAILING_LIST_ID);
        }
        
        $response = null;
        try {
            // Param 'list_id' cannot be modified
            $array = array(
                'email' => self::ML_EMAIL_EXAMPLE,
                'nome' => self::FIRST_NAME,
                'cognome' => self::SECOND_NAME,
                'idprofessione' => self::MAILING_LIST_PROFESSION_ID
            );
            $response = $this->sendRequest(self::GET, self::MAILING_LIST . self::EDIT, $array, self::TIMEOUT);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        $data = $this->checkResponse($response);
    }
    


    /**
     * Unsubscribe from the mailing list
     *
     * @uses Can't unsubribe twice but only once. Even if you retry to subscribe, you can't.
     */
    public function testUnsubscribe() {
        self::$climate->info('testUnsubscribe...');
        
        if(!$this->isSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID)){
            $this->fail('The user ' . self::ML_EMAIL_EXAMPLE . ' is NOT subscribed to the list ' . self::MAILING_LIST_ID);    
        }
        
        $response = null;
        try {
            $array = array(
                'email' => self::ML_EMAIL_EXAMPLE
            );
            $response = $this->sendRequest(self::GET, self::MAILING_LIST . self::UNSUBSCRIBE, $array, self::TIMEOUT);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        $data = $this->checkResponse($response);
        
        if($this->isSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID)){
            $this->fail('The user ' . self::ML_EMAIL_EXAMPLE . ' is subscribed to the list ' . self::MAILING_LIST_ID);
        }
        
    }
    
    private function isSubscribed($email, $list_id){
        $b = false;
        self::$climate->info('isSubscribed...');

        $response = null;
        try {
            $array = array(
                'email' => self::ML_EMAIL_EXAMPLE
            );
            $response = $this->sendRequest(self::GET, self::MAILING_LIST . self::EDIT, $array, self::TIMEOUT);
            
            // Response
            $this->assertContains(self::APP_JSON_CT, $response->getHeader(self::CONTENT_TYPE)[0]);            
            self::$climate->shout('Response code is: ' . $response->getStatusCode());            
            if($response->getStatusCode() == self::HTTP_OK){
                 $b = true;
            }
            // Getting data
            // $data = json_decode($response->getBody(), true);
            
            
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        return $b;
    }
    

    


}