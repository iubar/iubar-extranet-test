<?php

namespace Iubar\Net;

class Pop3 {

    protected $connection = null;

    protected static $host = null;

    protected static $port = null;

    protected static $user = null;

    protected static $password = null;

    protected static $ssl = false;

    public function setHost($host) {
        self::$host = $host;
    }

    public function setPort($port) {
        self::$port = $port;
    }

    public function setUser($user) {
        self::$user = $user;
    }

    public function setPassword($password) {
        self::$password = $password;
    }

    public function setSsl($ssl) {
        self::$ssl = $ssl;
    }

    /**
     * Make the login with pop3
     *
     * @param string $folder default:INBOX
     */
    public function pop3_login($folder = "INBOX") {
        $pop3_folder = $this->getPop3Folder($folder);
        $this->connection = (imap_open($pop3_folder, self::$user, self::$password)) or die("Can't connect: " . imap_last_error());
        return $this->connection;
    }

    protected function getPop3Folder($folder = "INBOX") {
        $ssl = (self::$ssl == true) ? "/ssl/novalidate-cert" : "";
        $mail_folder = "{" . self::$host . ":" . self::$port . "/pop3" . $ssl . "}" . $folder;
        return $mail_folder;
    }

    /**
     * Delete the given message
     *
     * @param string $msg_num
     */
    public function pop3_dele($msg_num) {
        $b = (imap_delete($this->connection, $msg_num));
        if (!$b) {
            echo "imap_delete() failed: " . imap_last_error() . PHP_EOL;
        } else {
            imap_expunge($this->connection);
        }
        return $b;
    }

    /**
     * Count the number of message in connection folder
     *
     * @return number the number of the messages
     */
    public function countMessages() {
        $n = 0;
        $check = imap_mailboxmsginfo($this->connection);
        if ($check) {
            $n = $check->Nmsgs;
        } else {
            echo "imap_mailboxmsginfo() failed: " . imap_last_error() . PHP_EOL;
        }
        return $n;
    }

    public function countMessages2() {
        $n = 0;
        $status = imap_status($this->connection, $this->getPop3Folder(), SA_ALL);
        if ($status) {
            $n = $status->messages;
        } else {
            echo "imap_status() failed: " . imap_last_error() . PHP_EOL;
        }
        return $n;
    }

    /**
     * Give all the messages of the connection folder
     *
     * @param string $message the message
     * @return array all the messages
     */
    public function pop3_list($message = "") {
        $result = array();
        if ($message) {
            $range = $message;
        } else {
            $MC = imap_check($this->connection);
            $range = "1:" . $MC->Nmsgs;
        }
        $response = imap_fetch_overview($this->connection, $range);
        foreach ($response as $msg) {
            $result[$msg->msgno] = (array) $msg;
        }
        return $result;
    }

    /**
     * Close the connection
     *
     * @param string $conn the connection
     */
    public function pop3_close() {
        imap_close($this->connection);
    }

    /**
     * unused method !
     *
     * @param string $message
     */
    protected function pop3_retr($message) {
        return (imap_fetchheader($this->connection, $message, FT_PREFETCHTEXT));
    }

    /**
     * unused function
     *
     * @param string $headers
     * @return unknown
     */
    private function mail_parse_headers($headers) {
        $headers = preg_replace('/\r\n\s+/m', '', $headers);
        preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)?\r\n/m', $headers, $matches);
        foreach ($matches[1] as $key => $value)
            $result[$value] = $matches[2][$key];
        return ($result);
    }

    /**
     * unused function
     *
     * @param unknown $imap
     * @param unknown $mid
     * @param string $parse_headers
     * @return unknown
     */
    private function mail_mime_to_array($imap, $mid, $parse_headers = false) {
        $mail = imap_fetchstructure($imap, $mid);
        $mail = $this->mail_get_parts($imap, $mid, $mail, 0);
        if ($parse_headers)
            $mail[0]["parsed"] = $this->mail_parse_headers($mail[0]["data"]);
        return ($mail);
    }

    /**
     * unused function
     *
     * @param unknown $imap
     * @param unknown $mid
     * @param unknown $part
     * @param unknown $prefix
     * @return NULL[]
     */
    private function mail_get_parts($imap, $mid, $part, $prefix) {
        $attachments = array();
        $attachments[$prefix] = $this->mail_decode_part($imap, $mid, $part, $prefix);
        if (isset($part->parts)) // multipart
{
            $prefix = ($prefix == "0") ? "" : "$prefix.";
            foreach ($part->parts as $number => $subpart)
                $attachments = array_merge($attachments, mail_get_parts($imap, $mid, $subpart, $prefix . ($number + 1)));
        }
        return $attachments;
    }

    /**
     * unused function
     *
     * @param unknown $message_number
     * @param unknown $part
     * @param unknown $prefix
     * @return boolean[]|NULL[]
     */
    private function mail_decode_part($message_number, $part, $prefix) {
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
        
        $attachment['data'] = imap_fetchbody($this->connection, $message_number, $prefix);
        if ($part->encoding == 3) { // 3 = BASE64
            $attachment['data'] = base64_decode($attachment['data']);
        } elseif ($part->encoding == 4) { // 4 = QUOTED-PRINTABLE
            $attachment['data'] = quoted_printable_decode($attachment['data']);
        }
        return ($attachment);
    }
}