<?php

/**
 * @author Matteo
 *
 */
class Mail {

    public function testEmail() {
        $expected_subject = uniqid('messaggio');
        
        // $client-> .... chiamata all'api' manda il messaggio
        // asserzione codice restituito 200 altrimenti fallisce
        
        // wait for email arrived
        sleep(40);
        
        // connect to the email inbox
        // $b = $mbox = imap_open ("{" . $host . ":" . $port . "/pop3}INBOX", $user, $password );
        $conn = $this->pop3_login($host, $port, $user, $password, "INBOX", true);
        $bOk = false;
        if (! $conn) {
            echo "Connection error" . PHP_EOL;
        } else {
            $messages = pop3_list($conn);
            if (count($messages) > 0) {
                foreach ($messages as $msg) {
                    $subject = $msg['subject'];
                    echo "subject --> " . $subject . PHP_EOL;
                    if ($subject == $expected_subject) {
                        $bOk = true;
                        $this->pop3_dele($conn, $msg);
                        break;
                    }
                }
            }
        }
        if ($bOk) {
            echo 'I have found your uniqid email and i delete it!!!!!!!!!!!!!!!' . PHP_EOL;
        } else {
            $this->fail('ERROR: I haven\'t find your email');
        }
    }
}