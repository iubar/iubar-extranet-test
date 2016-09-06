<?php
		
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

require '../bootstrap.php';
		
/*

http://docs.guzzlephp.org/en/latest/quickstart.html

// Client

$client = new Client([
		// Base URI is used with relative requests
		'base_uri' => 'http://httpbin.org',
		// You can set any number of default request options.
		'timeout'  => 2.0,
]);
// Send a request to https://foo.com/api/test
// 		$res = $client->request('GET', 'test');
// 		Send a request to https://foo.com/root
// 		$res = $client->request('GET', '/root');

// Exceptions

try {
	$client->request('GET', 'https://github.com/_abc_123_404');
} catch (RequestException $e) {
	echo $e->getRequest();
	if ($e->hasResponse()) {
		echo $e->getResponse();
	}
}

try {
	$client->request('GET', 'https://github.com/_abc_123_404');
} catch (ClientException $e) {
	echo $e->getRequest();
	echo $e->getResponse();
}


// Magic methods

$res = $client->get('http://httpbin.org/get');
$res = $client->delete('http://httpbin.org/delete');
$res = $client->post('http://httpbin.org/post');
$res = $client->put('http://httpbin.org/put');

// Query String Parameters

$client->request('GET', 'http://httpbin.org', ['query' => 'foo=bar']);

// Uploading Raw Data

// Provide the body as a string.
$r = $client->request('POST', 'http://httpbin.org/post', [
		'body' => 'raw data'
]);

// POST/Form Requests

// Sending application/x-www-form-urlencoded POST requests requires that you specify the POST fields as an array in the form_params request options
$response = $client->request('POST', 'http://httpbin.org/post', [
		'form_params' => [
				'field_name' => 'abc',
				'other_field' => '123',
				'nested_field' => [
						'nested' => 'hello'
				]
		]
]);


// Using Responses

$code = $res->getStatusCode(); // 200
$reason = $res->getReasonPhrase(); // OK
// Check if a header exists.
if ($response->hasHeader('Content-Length')) {
	echo "It exists";
}
// Get a header from the response.
echo $res->getHeader('Content-Length');
// Get all of the response headers.
foreach ($res->getHeaders() as $name => $values) {
	echo $name . ': ' . implode(', ', $values) . "\r\n";
}

$body = $res->getBody();
// Implicitly cast the body to a string and echo it
echo $body;
// Explicitly cast the body to a string
$stringBody = (string) $body;
// Read the remaining contents of the body as a string
$remainingBytes = $body->getContents();

*/
////////////////////////////////////////////////////////////////////

$client = new GuzzleHttp\Client();

$res = null;

try {
	$res = $client->request('GET', 'https://api.github.com/user', [
			'auth' => ['borgogelli@iubar.it', 'borgo2000'] // WARNING: password hard-coded - high security risk
	]);
} catch (ClientException $e) {
	echo 'Uh oh !!! ' . $e->getMessage() . PHP_EOL;   
    if ($e->hasResponse()) {
        echo "Res body: " .  $e->getResponse()->getBody() . PHP_EOL;
    }
} catch (RequestException $e) {
	echo 'Uh oh! ' . $e->getMessage() . PHP_EOL;
    echo "Req uri: " . $e->getRequest()->getUri() . PHP_EOL;
    echo "Req body: " . $e->getRequest()->getBody() . PHP_EOL;    
    if ($e->hasResponse()) {
        echo "Res body: " .  $e->getResponse()->getBody() . PHP_EOL;
    }
}


if($res){
	echo $res->getStatusCode();
	// "200"
	echo $res->getHeader('content-type');
	// 'application/json; charset=utf8'
	echo $res->getBody();
	// {"type":"User"...'
}

if(false){
	// Send an asynchronous request.
	$request = new \GuzzleHttp\Psr7\Request('GET', 'http://httpbin.org');
	$promise = $client->sendAsync($request)->then(function ($response) {
		echo 'I completed! ' . $response->getBody();
	});
	$promise->wait();
}

