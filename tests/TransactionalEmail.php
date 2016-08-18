<?php

namespace Extranet;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Iubar\Net\Pop3;
use Iubar\Net\MailgunUtil;
use League\CLImate\CLImate;
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
class TransactionalEmail extends Extranet_TestCase {

    const FROM_USER = 'postmaster';
    
    const FROM_NAME = 'Postmaster';

    const EMAIL_DOMAIN = 'fatturatutto.it';
    
    const RECIPIENT = 'info@iubar.it';
 
    const GET = 'get';

    const CONTACT = 'contact';

    const PREFIX_SUBJECT = 'msgTest';
    
    /** 
     * seconds to wait before logging to the pop3 mailbox to delete the message
     * 
     */
    const EMAIL_WAIT = 60;
    
    /**
     * seconds to wait before Mailgun writes log
     *  @see: https://documentation.mailgun.com/api-events.html#event-polling
     */
    const LOG_WAIT = 15;
      
    protected static $transact_secret_api_key = null;
    
    protected $pop3 = null;
 
    /**
     * Create a Client
     */
    public function setUp() {
        
        parent::init();
                
        $host = getenv('MAIL_HOST');
        $port = getenv('MAIL_PORT');
        $ssl = getenv('MAIL_SSL');
        $user = getenv('MAIL_USER');
        $password = getenv('MAIL_PASSWORD');      
        
        if (!$host) {
            die('Missing parameter: host' . PHP_EOL);
        }
        if (!$port) {
            die('Missing parameter: port' . PHP_EOL);
        }
        if (!$user) {
            die('Missing parameter: user' . PHP_EOL);
        }
        if (!$password) {
            die('Missing parameter: password' . PHP_EOL);
        }
                
        $this->pop3 = new Pop3();
        
        $this->pop3->setHost($host);
        $this->pop3->setPort($port);
        $this->pop3->setSsl($ssl);
        $this->pop3->setUser($user);
        $this->pop3->setPassword($password);
        self::$transact_secret_api_key = getenv('TRANSACT_SECRET_API_KEY');
        
        self::$climate->info('RECIPIENT HOST: ' . $host);
        self::$climate->info('RECIPIENT PORT: ' . $port);
        self::$climate->info('RECIPIENT SSL: ' . $ssl);
        self::$climate->info('RECIPIENT USER: '  . $user);
        self::$climate->info('RECIPIENT PASSWORD: '  . '******');
        self::$climate->info('TRANSACTIONAL MESSAGES FROM: ' . self::FROM_NAME . ' <' . self::FROM_USER . '@' . self::EMAIL_DOMAIN . '>');
        self::$climate->info('TRANSACTIONAL TRANSACT_SECRET_API_KEY: ' . '******');

    }


    /**
     * Send an email with a uniqid subject and delete it
     */
    public function testSendDeleteEmail() {
        self::$climate->info('Testing SendDeleteEmail...');
        $response = null;        
        $from_email = self::FROM_USER . '@' . self::EMAIL_DOMAIN;
        
        // 1) Send the message through Mailgun
        $expected_subject = uniqid(self::PREFIX_SUBJECT); // Create a unique id for the subject of the email to identify it
        try {
            $array = array(
                'from_name' => self::FROM_NAME,
                'from_email' => $from_email,
                'from_domain' => self::EMAIL_DOMAIN,
                'subject' => $expected_subject,
                'message' => 'This is an api test'
            );
            $response = $this->sendRequest(self::GET, self::CONTACT, $array, self::TIMEOUT);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        $data = $this->checkResponse($response);
        
        // 2) Read the Mailgun event log
        $this->sleep(self::LOG_WAIT);  // Wait until the log is updated
        $mailgun = new MailgunUtil(self::$transact_secret_api_key);
        $mailgun->setDomain(self::EMAIL_DOMAIN);
        $recipient = getenv('MAIL_USER');
        $b = $mailgun->checkEvents($from_email, $recipient, $expected_subject);        
        if(!$b){
            $this->fail('ERROR: Mailgun error');
        }else{            
            // 3) Cleanup the recipient's mailbox                        
            $this->sleep(self::EMAIL_WAIT); // Wait until the email arrived
            $this->cleanMailbox($expected_subject);            
        }        

    }

    private function cleanMailbox($expected_subject){
        self::$climate->info('cleanMailbox()...');
        // Connect to the inbox folder of the pop3 server
        $conn = $this->pop3->pop3_login();
        $bOk = false;
        $recipient = getenv('MAIL_USER');
        if (!$conn) {
            $this->fail('Pop3 connection error');
        } else {
            $subject = '';
            $msg_num = -1;
            $messages = $this->pop3->pop3_list();
            self::$climate->info('Scanning recipient\'s mailbox: ' . $recipient);
            $tot = count($messages);
            if ($tot > 0) {
                $i = 0;
                foreach ($messages as $msg) {
                    $i++;
                    $subject = $msg['subject'];
                    $msg_num = $msg['msgno'];
                    self::$climate->info('Message ' . $i  . '/' . $tot . ' Subject is: ' . $subject);
                    $pos = strpos($subject, $expected_subject);
                    if ($pos !== false) {
                        $bOk = true;
                        break;
                    }
                }
            }else{
                self::$climate->info('No messages found into the recipient\'s mailbox.');
            }
    
            if ($bOk) {
                self::$climate->info('I have found the message into the recipient\'s mailbox. Now I\'m trying to delete it...');
                self::$climate->info('Number of messages into the recipient\'s mailbox: ' . $this->pop3->countMessages() . ' (' . $this->pop3->countMessages2() . ')');
                // delete the uniqid msg
                $del = $this->pop3->pop3_dele($msg_num);
                if (!$del) {
                    $this->fail('Can\'t delete the message ' . $msg_num . ' with subject ' . $subject);
                } else {
                    self::$climate->info('Message deleted.');
                }
                self::$climate->info('Number of messages into the recipient\'s mailbox after deletion: ' . $this->pop3->countMessages() . ' (' . $this->pop3->countMessages2() . ')');
            } else {
                $this->fail('ERROR: I haven\'t found the message into the recipient\'s mailbox.');
            }
            $bOk = $this->pop3->pop3_close();
        }
    }
    

}