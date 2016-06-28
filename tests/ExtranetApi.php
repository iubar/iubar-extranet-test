<?php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Iubar\RestApi_TestCase;
use League\CLImate\CLImate;

/**
 * API Test
 *
 * @author Matteo
 * @global env ft_username
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

    const PARTIAL_EMAIL = "postmaster@";

    const EMAIL_DOMAIN = "fatturatutto.it";

    const GET = 'get';

    const TWITTER = 'twitter';

    const LENGTH = 100;

    const CONTENT_TYPE = 'Content-Type';

    const APP_JSON_CT = 'application/json';
    
    // http status code
    const OK = 200;

    const RSS = 'rss';

    const CONTACT = 'contact';

    const MAILING_LIST = 'mailing-list/';

    const SUBSCRIBE = 'subscribe';

    const EDIT = 'edit';

    const UNSUBSCRIBE = 'unsubscribe';

    const ID_SUBSCRIBE = 1;

    const ID_EDIT_UNSUBSCRIBE = 2;

    const NOME = "NomeTest";

    const COGNOME = "CognomeTest";

    const PREFIX_SUBJECT = "msgTest";
    
    // seconds to wait before logging to the pop3 mailbox to delete the message
    const EMAIL_WAIT = 40;
    
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
    }

    /**
     * Test Twitter
     *
     * @uses Some tweet could be filtered so the ELEM_LIMIT and the number of tweet could be different
     */
    public function testTwitter() {
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
    public function testRss() {
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
        $response = null;
        
        // create a unique id for the subject of the email to identify it
        $expected_subject = uniqid(self::PREFIX_SUBJECT);
        try {
            $array = array(
                'from_name' => getEnv('FT_USERNAME'),
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
        
        // wait for email arrived
        sleep(self::EMAIL_WAIT);
        
        // connect to the email inbox
        $conn = $this->pop3_login(getenv('host'), getenv('port'), getenv('user'), getenv('password'), "INBOX", true);
        $bOk = false;
        if (!$conn) {
            $this->fail('Connection error');
        } else {
            $subject = "";
            $msg_num = -1;
            $messages = $this->pop3_list($conn);
            if (count($messages) > 0) {
                foreach ($messages as $msg) {
                    $subject = $msg['subject'];
                    $msg_num = $msg['msgno'];
                    echo "subject --> " . $subject . PHP_EOL;
                    $pos = strpos($subject, $expected_subject);
                    if ($pos !== false) {
                        $bOk = true;
                        break;
                    }
                }
            }
            
            if ($bOk) {
                echo PHP_EOL . 'I have found your email and I\'m trying to delete it...' . PHP_EOL;
                echo "Number of messages in the mailbox: " . $this->countMessages($conn) . PHP_EOL;
                // delete the uniqid msg
                $del = $this->pop3_dele($conn, $msg_num);
                if (!$del) {
                    $this->fail("Can't delete the message " . $msg_num . " with subject " . $subject);
                }
                echo "Number of messages in the mailbox after deletion: " . $this->countMessages($conn) . PHP_EOL;
            } else {
                $this->fail('ERROR: I haven\'t found your email');
            }
            $this->pop3_close($conn);
        }
    }

    /**
     * Subscribe into the mailing list
     */
    public function testSubscribeMailingList() {
        $response = null;
        try {
            $array = array(
                'email' => getenv('FT_USERNAME'),
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
    public function testEditMailingList() {
        $response = null;
        try {
            // Param 'list_id' cannot be modified
            $array = array(
                'email' => getenv('FT_USERNAME'),
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

    /**
     * Unsubscribe from the mailing list
     *
     * @uses Can't unsubribe twice but only once. Even if you retry to subscribe, you can't.
     */
    public function testUnsubscribeMailingList() {
        $response = null;
        try {
            $array = array(
                'email' => getenv('FT_USERNAME')
            );
            $response = $this->sendRequest(self::GET, self::MAILING_LIST . self::UNSUBSCRIBE, $array, self::TIMEOUT);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        $data = $this->checkResponse($response);
    }

    public function testFinish() {
        self::$climate->info('FINE TEST API OK!!!!!!!!');
    }

    /**
     * Make the login with pop3
     *
     * @param string $host the host
     * @param int $port the port
     * @param string $user the user email
     * @param string $pass the password
     * @param string $folder default:INBOX
     * @param string $ssl if you want the ssl certificate
     */
    protected function pop3_login($host, $port, $user, $pass, $folder = "INBOX", $ssl = false) {
        // the version of this function in this webpage is wrog http://php.net/manual/en/book.imap.php
        
        // this is the right code
        $ssl = ($ssl == true) ? "/ssl/novalidate-cert" : "";
        $x = "{" . $host . ":" . $port . "/pop3" . $ssl . "}" . $folder;
        $conn = (imap_open($x, $user, $pass)) or die("Can't connect: " . imap_last_error());
        return $conn;
    }

    /**
     * Delete the given message
     *
     * @param string $connection the connection
     * @param string $msg_num
     */
    protected function pop3_dele($connection, $msg_num) {
        $b = (imap_delete($connection, $msg_num));
        if (!$b) {
            echo "imap_delete() failed: " . imap_last_error() . PHP_EOL;
        }
        return $b;
    }

    /**
     * Count the number of message in connection folder
     *
     * @param string $connection the connection
     * @return number the number of the messages
     */
    protected function countMessages($connection) {
        $n = 0;
        $check = imap_mailboxmsginfo($connection);
        if ($check) {
            $n = $check->Nmsgs;
        } else {
            echo "imap_mailboxmsginfo() failed: " . imap_last_error() . PHP_EOL;
        }
        return $n;
    }

    /**
     * Give all the messages of the connection folder
     *
     * @param string $connection the connection
     * @param string $message the message
     * @return array all the messages
     */
    protected function pop3_list($connection, $message = "") {
        if ($message) {
            $range = $message;
        } else {
            $MC = imap_check($connection);
            $range = "1:" . $MC->Nmsgs;
        }
        $response = imap_fetch_overview($connection, $range);
        foreach ($response as $msg)
            $result[$msg->msgno] = (array) $msg;
        return $result;
    }

    /**
     * Close the connection
     *
     * @param string $conn the connection
     */
    protected function pop3_close($conn) {
        imap_close($conn);
    }

    /**
     * unutilized function
     *
     * @param string $connection
     * @param string $message
     */
    protected function pop3_retr($connection, $message) {
        return (imap_fetchheader($connection, $message, FT_PREFETCHTEXT));
    }

    /**
     * unutilized function
     *
     * @param string $headers
     * @return unknown
     */
    protected function mail_parse_headers($headers) {
        $headers = preg_replace('/\r\n\s+/m', '', $headers);
        preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)?\r\n/m', $headers, $matches);
        foreach ($matches[1] as $key => $value)
            $result[$value] = $matches[2][$key];
        return ($result);
    }

    /**
     * unutilized function
     *
     * @param unknown $imap
     * @param unknown $mid
     * @param string $parse_headers
     * @return unknown
     */
    protected function mail_mime_to_array($imap, $mid, $parse_headers = false) {
        $mail = imap_fetchstructure($imap, $mid);
        $mail = mail_get_parts($imap, $mid, $mail, 0);
        if ($parse_headers)
            $mail[0]["parsed"] = mail_parse_headers($mail[0]["data"]);
        return ($mail);
    }

    /**
     * unutilized function
     *
     * @param unknown $imap
     * @param unknown $mid
     * @param unknown $part
     * @param unknown $prefix
     * @return NULL[]
     */
    protected function mail_get_parts($imap, $mid, $part, $prefix) {
        $attachments = array();
        $attachments[$prefix] = mail_decode_part($imap, $mid, $part, $prefix);
        if (isset($part->parts)) // multipart
{
            $prefix = ($prefix == "0") ? "" : "$prefix.";
            foreach ($part->parts as $number => $subpart)
                $attachments = array_merge($attachments, mail_get_parts($imap, $mid, $subpart, $prefix . ($number + 1)));
        }
        return $attachments;
    }

    /**
     * unutilized function
     *
     * @param unknown $connection
     * @param unknown $message_number
     * @param unknown $part
     * @param unknown $prefix
     * @return boolean[]|NULL[]
     */
    protected function mail_decode_part($connection, $message_number, $part, $prefix) {
        $attachment = array();
        
        if ($part->ifdparameters) {
            foreach ($part->dparameters as $object) {
                $attachment[strtolower($object->attribute)] = $object->value;
                if (strtolower($object->attribute) == 'filename') {
                    $attachment['is_attachment'] = true;
                    $attachment['filename'] = $object->value;
                }
            }
        }
        
        if ($part->ifparameters) {
            foreach ($part->parameters as $object) {
                $attachment[strtolower($object->attribute)] = $object->value;
                if (strtolower($object->attribute) == 'name') {
                    $attachment['is_attachment'] = true;
                    $attachment['name'] = $object->value;
                }
            }
        }
        
        $attachment['data'] = imap_fetchbody($connection, $message_number, $prefix);
        if ($part->encoding == 3) { // 3 = BASE64
            $attachment['data'] = base64_decode($attachment['data']);
        } elseif ($part->encoding == 4) { // 4 = QUOTED-PRINTABLE
            $attachment['data'] = quoted_printable_decode($attachment['data']);
        }
        return ($attachment);
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
        // Response
        $this->assertContains(self::APP_JSON_CT, $response->getHeader(self::CONTENT_TYPE)[0]);
        $this->assertEquals(self::OK, $response->getStatusCode());
        
        // Getting data
        $data = json_decode($response->getBody(), true);
        return $data;
    }
}