<?php
namespace Iubar\Net;

use Mailgun\Mailgun;

class MailgunUtil {

    private $mgClient = null;

    private $domain = null;

    function __construct($secret_api_key) {
        $this->mgClient = new Mailgun($secret_api_key);
    }

    function setDomain($domain) {
        $this->domain = $domain;
    }

    public function checkEvents($from, $recipient, $subject_substr) {
        $b = false;
        
        // Fetches the first page of log records
        $queryString = array(            
         //   'from'    => $from,            
            'begin'     => $this->getNow(-5),
            'end'       => $this->getNow(),
            'limit'     => 10,
            'pretty'    => 'yes',
            'recipient' => $recipient
        );
        
        $queryString = array(
            //   'from'    => $from,
            'begin'     => $this->getNow(),
            'ascending' => 'no',   // Defines the direction of the search time range if the range end time is not specified
                                    // i.e. descending means older than
            'limit'     => 10,
            'pretty'    => 'yes',
            'recipient' => $recipient
        );        
        
        $this->printQuery($queryString);
        
        // Make the call to the client.
        $result = $this->mgClient->get($this->domain . "/events", $queryString);
        // $json = json_encode($result, JSON_PRETTY_PRINT);
        
        $items = $result->http_response_body->items;
        echo "Items fetched: ". count($items) . PHP_EOL;
        echo "Search pattern: "  . $subject_substr . PHP_EOL;
        foreach ($items as $item) {
            $subject = $item->message->headers->subject;
            echo "Reading subject: ". $subject . PHP_EOL;
            $pos = strpos($subject, $subject_substr);
            if ($pos !== false) {
                if($item->event == 'delivered'){
                    echo "Ok: message was delivered !" . PHP_EOL;
                    $b = true;                    
                }else{
                    echo "ERROR: message status is '" . $item->event . "' !" . PHP_EOL;
                    $status = $item->delivery-status;
                    $json = json_encode($status, JSON_PRETTY_PRINT);
                    echo "Delivery Status:" . PHP_EOL;
                    echo $json-status . PHP_EOL;
                }
                break;
            }
        }        
        return $b;
    }

    
    /**
     *  Only as example
     */
    private function checkErrorEvents() {
        
        // Fetches the first page of log records that contain different types of failure, starting from the most recent:
        $queryString = array(
            'pretty' => 'yes',
            'event' => 'rejected OR failed'
        );
        $this->printQuery($queryString);
        $result = $this->mgClient->get($this->domain . "/events", $queryString);
        
//     {
//   "items": [
//     {
//       "severity": "temporary",
//       "tags": [],
//       "envelope": {
//         "sender": "me@samples.mailgun.org",
//         "transport": ""
//       },
//       "delivery-status": {
//         "code": 498,
//         "message": "No MX for [example.com]",
//         "retry-seconds": 900,
//         "description": "No MX for [example.com]"
//       },
//       "campaigns": [],
//       "reason": "generic",
//       "user-variables": {},
//       "flags": {
//         "is-authenticated": true,
//         "is-test-mode": false
//       },
//       "timestamp": 1376435471.10744,
//       "message": {
//         "headers": {
//           "to": "baz@example.com, bar@example.com",
//           "message-id": "20130813230036.10303.40433@samples.mailgun.org",
//           "from": "Excited User <me@samples.mailgun.org>",
//           "subject": "Hello"
//         },
//         "attachments": [],
//         "recipients": [
//           "baz@example.com",
//           "bar@example.com"
//         ],
//         "size": 370
//       },
//       "recipient": "bar@example.com",
//       "event": "failed"
//     }
//   ],
//   "paging": {
//     "next":
//         "https://api.mailgun.net/v3/samples.mailgun.org/events/W3siY...",
//     "previous":
//         "https://api.mailgun.net/v3/samples.mailgun.org/events/Lkawm..."
//   }
// }
        
        return $result;
    }

    public function getNextPage($result) {
        $nextPage = $result->next;
        $result = $this->mgClient->get($this->domain . "/events/" . $nextPage);
        return $result;
    }

    public function toRfc2822($datetime) {
        $datetime = $datetime->format(\DateTime::RFC2822);
        return $datetime;
    }

    public function getNow($minutes = 0) {
        $now = new \DateTime();
        
        $tz_object = new \DateTimeZone('Europe/Rome');
        //$tz_object = new \DateTimeZone('UTC');
        $now->setTimezone($tz_object);
        
        if ($minutes) {
            $now->modify("{$minutes} minutes");
        }
        $now_str = $this->toRfc2822($now);
        return $now_str;
    }
    
    private function printQuery($queryString){
        echo "Query is: " . PHP_EOL . json_encode($queryString, JSON_PRETTY_PRINT) . PHP_EOL;
    }
    
}