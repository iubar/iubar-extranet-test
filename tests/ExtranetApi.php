<?php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Iubar\Tests\RestApi_TestCase;
use Iubar\Net\Pop3;
use Iubar\Net\MailgunUtil;
use League\CLImate\CLImate;

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
class ExtranetApi extends RestApi_TestCase {

    const IUBAR_EXTRANET_API = "http://www.iubar.it/extranet/api/";
    
    // seconds
    const TIMEOUT = 4;

    const ELEM_LIMIT = 3;

    const PARTIAL_EMAIL = 'postmaster@';

    const EMAIL_DOMAIN = 'fatturatutto.it';
    
    const RECIPIENT = 'info@iubar.it';

    const GET = 'get';

    const TWITTER = 'twitter';

    const LENGTH = 100;

    const CONTENT_TYPE = 'Content-Type';

    const APP_JSON_CT = 'application/json';
    
    const HTTP_OK = 200;

    const RSS = 'rss';

    const CONTACT = 'contact';

    const MAILING_LIST = 'mailing-list/';

    const SUBSCRIBE = 'subscribe';

    const EDIT = 'edit';

    const UNSUBSCRIBE = 'unsubscribe';

    const ID_SUBSCRIBE = 1;

    const ID_EDIT_UNSUBSCRIBE = 2;

    const NOME = 'NomeTest';

    const COGNOME = 'CognomeTest';

    const PREFIX_SUBJECT = 'msgTest';
    
    // seconds to wait before logging to the pop3 mailbox to delete the message
    const EMAIL_WAIT = 60;
    
    /**
     * seconds to wait before Mailgun writes log
     *  @see: https://documentation.mailgun.com/api-events.html#event-polling
     */
    const LOG_WAIT = 15;

    protected static $transact_email_user = null;
    
    protected static $transact_secret_api_key = null;
    

    protected $pop3 = null;


    // easily output colored text and special formatting
    protected static $climate;
    
 
    /**
     * Create a Client
     */
    public function setUp() {
        self::$climate = new CLImate();
        
        // Base URI is used with relative requests
        // You can set any number of default request options.
        $this->client = new Client([
            'base_uri' => self::IUBAR_EXTRANET_API,
            'timeout' => self::TIMEOUT
        ]);
        
        
        $host = getenv('MAIL_HOST');
        $port = getenv('MAIL_PORT');
        $ssl = getenv('MAIL_SSL');
        $user = getenv('MAIL_USER');
        $password = getenv('MAIL_PASSWORD');      
        
        if (!$host) {
            die("Missing parameter: host" . PHP_EOL);
        }
        if (!$port) {
            die("Missing parameter: port" . PHP_EOL);
        }
        if (!$user) {
            die("Missing parameter: user" . PHP_EOL);
        }
        if (!$password) {
            die("Missing parameter: password" . PHP_EOL);
        }
                
        $this->pop3 = new Pop3();
        
        $this->pop3->setHost($host);
        $this->pop3->setPort($port);
        $this->pop3->setSsl($ssl);
        $this->pop3->setUser($user);
        $this->pop3->setPassword($password);
        self::$transact_email_user = getenv('TRANSACT_EMAIL_USER');
        self::$transact_secret_api_key = getenv('TRANSACT_SECRET_API_KEY');
        
        self::$climate->info("HOST: " . $host);
        self::$climate->info("PORT: " . $port);
        self::$climate->info("SSL: " . $ssl);
        self::$climate->info("USER: "  . $user);
        self::$climate->info("PASSWORD: " . $password);
        self::$climate->info("TRANSACT_EMAIL_USER: " . self::$transact_email_user);        

    }

    /**
     * Test Twitter
     *
     * @uses Some tweet could be filtered so the ELEM_LIMIT and the number of tweet could be different
     */
    public function _testTwitter() {
        self::$climate->info("Testing Twitter...");
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
        /*
         * foreach ($response->getHeaders() as $name => $values) { echo $name . ': ' . implode(', ', $values) . "\r\n"; }
         */
    }

    /**
     * Test Rss
     */
    public function _testRss() {
        self::$climate->info("Testing Rss...");
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

    /**
     * Send an email with a uniqid subject and delete it
     */
    public function testSendDeleteEmail() {
        self::$climate->info("Testing SendDeleteEmail...");
        $response = null;
        
        // create a unique id for the subject of the email to identify it
        $expected_subject = uniqid(self::PREFIX_SUBJECT);
        try {
            $array = array(
                'from_name' => self::$transact_email_user,
                'from_email' => self::PARTIAL_EMAIL . self::EMAIL_DOMAIN,
                'from_domain' => self::EMAIL_DOMAIN,
                'subject' => $expected_subject,
                'message' => 'This is an api test'
            );
            $response = $this->sendRequest(self::GET, self::CONTACT, $array, self::TIMEOUT + 10);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        $data = $this->checkResponse($response);
        
        /////////////////////
        
        sleep(self::LOG_WAIT);
        
        
        $mailgun = new MailgunUtil(self::$transact_secret_api_key);
        $mailgun->setDomain(self::EMAIL_DOMAIN);
        $recipient = self::RECIPIENT;
        $from = self::PARTIAL_EMAIL . self::EMAIL_DOMAIN;
        $b = $mailgun->checkEvents($from, $recipient, $expected_subject);
        if($b){
            echo "OK" . PHP_EOL;            
        }else{
            echo "KO" . PHP_EOL;
        }
        die("!");
        
        ////////////////////
        
        // wait for email arrived
        self::$climate->info("Waiting " . self::EMAIL_WAIT . " seconds...");
        sleep(self::EMAIL_WAIT);
        
        // connect to the email inbox
        $conn = $this->pop3->pop3_login();
        $bOk = false;
        if (!$conn) {
            $this->fail('Connection error');
        } else {
            $subject = "";
            $msg_num = -1;
            $messages = $this->pop3->pop3_list();
            if (count($messages) > 0) {
                foreach ($messages as $msg) {
                    $subject = $msg['subject'];
                    $msg_num = $msg['msgno'];
                    self::$climate->info("subject --> " . $subject);
                    $pos = strpos($subject, $expected_subject);
                    if ($pos !== false) {
                        $bOk = true;
                        break;
                    }
                }
            }
            
            if ($bOk) {
                self::$climate->info('I have found your email and I\'m trying to delete it...');
                self::$climate->info("Number of messages in the mailbox: " . $this->pop3->countMessages() . ' (' . $this->pop3->countMessages2() . ')');
                // delete the uniqid msg
                $del = $this->pop3->pop3_dele($msg_num);
                if (!$del) {
                    $this->fail("Can't delete the message " . $msg_num . " with subject " . $subject);
                } else {
                    self::$climate->info("Message deleted");
                }
                self::$climate->info("Number of messages in the mailbox after deletion: " . $this->pop3->countMessages() . ' (' . $this->pop3->countMessages2() . ')');
            } else {
                $this->fail('ERROR: I haven\'t found your email');
            }
            $this->pop3->pop3_close();
        }
    }
    

    /**
     * Unsubscribe from the mailing list
     *
     * @uses Can't unsubribe twice but only once. Even if you retry to subscribe, you can't.
     */
    public function _testUnsubscribeMailingList() {
        self::$climate->info("Testing UnsubscribeMailingList...");
        $response = null;
        try {
            $array = array(
                'email' => self::$transact_email_user
            );
            $response = $this->sendRequest(self::GET, self::MAILING_LIST . self::UNSUBSCRIBE, $array, self::TIMEOUT);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        $data = $this->checkResponse($response);
    }

    /**
     * Subscribe into the mailing list
     */
    public function _testSubscribeMailingList() {
        self::$climate->info("Testing SubscribeMailingList...");
        $response = null;
        try {
            $array = array(
                'email' => self::$transact_email_user,
                'nome' => self::NOME,
                'cognome' => self::COGNOME,
                'idprofessione' => self::ID_SUBSCRIBE,
                'list_id' => self::ID_SUBSCRIBE
            );
            $response = $this->sendRequest(self::GET, self::MAILING_LIST . self::SUBSCRIBE, $array, self::TIMEOUT);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        $data = $this->checkResponse($response);
    }

    /**
     * Edit from the mailing list
     */
    public function _testEditMailingList() {
        self::$climate->info("Testing EditMailingList...");
        $response = null;
        try {
            // Param 'list_id' cannot be modified
            $array = array(
                'email' => self::$transact_email_user,
                'nome' => self::NOME,
                'cognome' => self::COGNOME,
                'idprofessione' => self::ID_EDIT_UNSUBSCRIBE
            );
            $response = $this->sendRequest(self::GET, self::MAILING_LIST . self::EDIT, $array, self::TIMEOUT);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        $data = $this->checkResponse($response);
    }


    public function testFinish() {
        self::$climate->info('TEST API OK!!!!!!!!');
    }

    
    /**
     * Send an http request and return the response
     *
     * @param string $method the method
     * @param string $partial_uri the partial uri
     * @param string $array the query
     * @param int $timeout the timeout
     * @return string the response
     */
    private function sendRequest($method, $partial_uri, $array, $timeout) {
        $response = null;
        try {
            $request = new Request($method, $partial_uri);
            $response = $this->client->send($request, [
                'timeout' => $timeout,
                'query' => $array
            ]);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        return $response;
    }
    
    /**
     * Check the OK status code and the APP_JSON_CT content type of the response
     *
     * @param string $response the response
     * @return string the body of the decode response
     */
    private function checkResponse($response) {
        $data = null;
        if ($response) {
            // Response
            $this->assertContains(self::APP_JSON_CT, $response->getHeader(self::CONTENT_TYPE)[0]);
            $this->assertEquals(self::HTTP_OK, $response->getStatusCode());
    
            self::$climate->shout("Response code is: " . $response->getStatusCode());
            // Getting data
            $data = json_decode($response->getBody(), true);
        }
        return $data;
    }
    

}