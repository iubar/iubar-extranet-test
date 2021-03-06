<?php

namespace Extranet;

use GuzzleHttp\Client;
use Iubar\Net\Pop3;
use Iubar\Net\MailgunUtil;
use League\CLImate\CLImate;
use Iubar\Tests\RestApi_TestCase;

/**
 * API Test
 *
 * @author Borgo
 * @global env TRANSACT_SECRET_API_KEY
 * @global env MAIL_HOST
 * @global env MAIL_PORT
 * @global env MAIL_SSL  
 * @global env MAIL_USER
 * @global env MAIL_PASSWORD
 * @see: https://mailgun.com/app/domains/fatturatutto.it 
 * @see: https://documentation.mailgun.com/api-events.html#examples
 */
class TransactionalEmailTest extends RestApi_TestCase {

    const FROM_USER = 'postmaster';
    
    const FROM_NAME = 'Postmaster';

    const EMAIL_DOMAIN = 'fatturatutto.it';
    
    const RECIPIENT = 'info@iubar.it';

    const CONTACT = 'contact';

    const PREFIX_SUBJECT = 'msgTest';
    
    /** 
     * seconds to wait before logging to the pop3 mailbox to delete the message
     */
    const EMAIL_WAIT = 30;
    
    /**
     * seconds to wait before Mailgun writes log
     *  @see: https://documentation.mailgun.com/api-events.html#event-polling
     */
    const LOG_WAIT = 10;
      
    protected static $transact_secret_api_key = null;
    
    protected static $pop3 = null;
 

    public static function setUpBeforeClass() : void {
        parent::init();        
        self::$transact_secret_api_key = getenv('TRANSACT_SECRET_API_KEY');        
        self::$client = self::factoryClient();
        self::$pop3 = self::factoryPop3();        
    }
    
    public function setUp() : void {
        // nothing to do
    }
    
    public static function factoryPop3() {
                
		if (!extension_loaded('imap')) {
			die("Attivare estensione imap in php.ini");
		}

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

        self::$climate->info('TRANSACTIONAL MESSAGES FROM: ' . self::FROM_NAME . ' <' . self::FROM_USER . '@' . self::EMAIL_DOMAIN . '>');
        self::$climate->info('TRANSACTIONAL TRANSACT_SECRET_API_KEY: ' . '******');
        self::$climate->info('RECIPIENT HOST: ' . $host);
        self::$climate->info('RECIPIENT PORT: ' . $port);
        self::$climate->info('RECIPIENT SSL: ' . $ssl);
        self::$climate->info('RECIPIENT USER: '  . $user);
        self::$climate->info('RECIPIENT PASSWORD: '  . '******');
        
        $pop3 = new Pop3();        
        $pop3->setHost($host);
        $pop3->setPort($port);
        $pop3->setSsl($ssl);
        $pop3->setUser($user);
        $pop3->setPassword($password);
        return $pop3;
    }



    private function cleanMailbox($expected_subject){
        self::$climate->info('cleanMailbox()...');
        // Connect to the inbox folder of the pop3 server
        $conn = self::$pop3->pop3_login();
        $bOk = false;
        $recipient = getenv('MAIL_USER');
        if (!$conn) {
            $this->fail('Pop3 connection error');
        } else {
            $subject = '';
            $msg_num = -1;
            $messages = self::$pop3->pop3_list();
            self::$climate->info('Scanning recipient\'s mailbox: ' . $recipient);
            $tot = count($messages);            
            if ($tot > 0) {
                self::$climate->info($tot . ' messages found');
                $i = 0;
                foreach ($messages as $msg) {
                    $i++;
                    $subject = $msg['subject'];
                    $msg_num = $msg['msgno'];
                    self::$climate->info('Message ' . $i  . '/' . $tot . ' Subject is: ' . $subject . ' (id:' . $msg_num . ')');
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
                self::$climate->info('Number of messages into the recipient\'s mailbox: ' . self::$pop3->countMessages() . ' (' . self::$pop3->countMessages2() . ')');
                // delete the uniqid msg
                $del = self::$pop3->pop3_dele($msg_num);
                if (!$del) {
                    $this->fail('Can\'t delete the message with id ' . $msg_num . ' with subject ' . $subject);
                } else {
                    self::$climate->info('Message deleted.');
                }
                self::$climate->info('Number of messages into the recipient\'s mailbox after deletion: ' . self::$pop3->countMessages() . ' (' . self::$pop3->countMessages2() . ')');
            } else {
                $this->fail('ERROR: I haven\'t found the message into the recipient\'s mailbox. Please check if in the meantime some other mail client has downloaded the message.');
            }
            $bOk = self::$pop3->pop3_close();
        }
    }
    

}