<?php
namespace Extranet;

use League\CLImate\CLImate;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Iubar\Tests\RestApi_TestCase;
use Iubar\Tests\HttpStatusCode;

/**
 * API Test
 *
 * @author Borgo
 * @global env transact_email_user
 * @global env host
 * @global env port
 * @global env user
 * @global env password
 */
class ExceptionTest extends RestApi_TestCase {

	const EXCEPTION1_ROUTE = 'exception1'; // questa rotta non è associata ad alcun controller, il comportamento è definito inline nel file index.php

	const EXCEPTION2_ROUTE = 'exception2';

	const APP_HTML_CT = 'text/html';

	private $app_debug = false;

	public static function setUpBeforeClass() {
		parent::init();
		self::$client = self::factoryClient();
	}

	public function setUp() {
		$this->app_debug = true; // true se $app->config('debug2') == 1
	}

	public function testException1WithAjax() {
		self::$climate->info('Testing exception...');
		$expected_status_code = HttpStatusCode::INTERNAL_SERVER_ERROR;
		$txt = 'This is a fake exception';
		$array = array(
			'txt' => $txt
		);
		$response = $this->sendGetReq(self::EXCEPTION1_ROUTE, $array);
		$content_type = $response->getHeader(self::CONTENT_TYPE)[0];

		$data = $this->checkResponse($response, $expected_status_code);
		$actualJson = json_encode($data);

		$this->assertEquals($expected_status_code, $response->getStatusCode());
		$this->assertContains(self::APP_JSON_CT, $content_type);
		$this->assertArrayHasKey("error", $data);
		$this->assertArrayHasKey("message", $data["error"]);
		$this->assertEquals($txt, $data["error"]["message"]);
	}

	public function testException1WithoutAjax() {
		self::$climate->info('Testing exception...');
		$expected_status_code = HttpStatusCode::INTERNAL_SERVER_ERROR;
		$txt = 'This is a fake exception';
		$array = array(
			'txt' => $txt
		);
		$response = $this->sendGetReqNoAjax(self::EXCEPTION1_ROUTE, $array);
		$content_type = $response->getHeader(self::CONTENT_TYPE)[0];

		$body = $response->getBody()->getContents();

		$this->assertEquals($expected_status_code, $response->getStatusCode());
		$this->assertContains(self::APP_HTML_CT, $content_type);
		$this->assertContains('Whoops! There was an error.', $body);
	}

	public function testException2WithAjax() {
		self::$climate->info('Testing exception2...');
		$expected_status_code = HttpStatusCode::INTERNAL_SERVER_ERROR;
		$txt = 'This is another fake exception';
		$array = array(
			'txt' => $txt
		);
		$response = $this->sendGetReq(self::EXCEPTION2_ROUTE, $array);
		$content_type = $response->getHeader(self::CONTENT_TYPE)[0];

		$data = $this->checkResponse($response, $expected_status_code);

		if ($this->app_debug) {
			$this->assertEquals($expected_status_code, $response->getStatusCode());
			$this->assertArrayHasKey("response", $data);
			//$this->assertArrayHasKey("message", $data["error"]);
			//$this->assertEquals($txt, $data["error"]["message"]);
		} else {
			$this->assertEquals($expected_status_code, $response->getStatusCode());
			$this->assertContains(self::APP_JSON_CT, $content_type);
			$actualJson = json_encode($data);
			$expectedJson = "{\"code\": 500, \"response\": \"$txt\"}";
			$this->assertJsonStringEqualsJsonString($expectedJson, $actualJson);
		}
	}

	public function testException2WithoutAjax() {
		self::$climate->info('Testing exception...');
		$expected_status_code = HttpStatusCode::INTERNAL_SERVER_ERROR;
		$txt = 'This is a fake exception';
		$array = array(
			'txt' => $txt
		);
		$response = $this->sendGetReqNoAjax(self::EXCEPTION2_ROUTE, $array);
		$content_type = $response->getHeader(self::CONTENT_TYPE)[0];

		if ($this->app_debug) {
			$body = $response->getBody()->getContents();
			$this->assertEquals($expected_status_code, $response->getStatusCode());
			$this->assertContains(self::APP_JSON_CT, $content_type);
			$this->assertContains('Whoops! There was an error.', $body);
		} else {
			$this->assertEquals($expected_status_code, $response->getStatusCode());
			$this->assertContains(self::APP_JSON_CT, $content_type);
			$data = $this->checkResponse($response, $expected_status_code);
			$actualJson = json_encode($data);
			$expectedJson = "{\"code\": 500, \"response\": \"$txt\"}";
			$this->assertJsonStringEqualsJsonString($expectedJson, $actualJson);
		}
	}

	protected function sendGetReqNoAjax($partial_uri, array $array, $timeout = null) {
		$response = null;
		if (!$timeout) {
			$timeout = self::TIMEOUT;
		}
		if (!self::$client) {
			throw new \Exception("Client obj is null");
		}
		try {
			$request = new Request(self::GET, $partial_uri);

			self::$climate->comment(PHP_EOL . "Request: " . PHP_EOL . "\tUrl:\t" . $partial_uri . PHP_EOL . "\tQuery:\t" . json_encode($array, JSON_PRETTY_PRINT));

			$response = self::$client->send($request, [
				'headers' => [
					'User-Agent' => 'restapi_testcase/' . self::VERSION,
					'Accept' => 'application/json'
					// 'X-Requested-With' => 'XMLHttpRequest' // for Whoops' JsonResponseHandler
				],
				'query' => $array,
				'timeout' => $timeout
			]);
		} catch (ConnectException $e) { // Is thrown in the event of a networking error. (This exception extends from GuzzleHttp\Exception\RequestException.)
			$this->handleException($e);
		} catch (ClientException $e) { // Is thrown for 400 level errors if the http_errors request option is set to true.
			$this->handleException($e);
		} catch (RequestException $e) { // In the event of a networking error (connection timeout, DNS errors, etc.), a GuzzleHttp\Exception\RequestException is thrown.
			$this->handleException($e);
		} catch (ServerException $e) { // Is thrown for 500 level errors if the http_errors request option is set to true.
			$this->handleException($e);
		}
		return $response;
	}

	protected function checkResponse($response, $expected_status_code = self::HTTP_OK) {
		$data = null;
		if ($response) {

			$body = $response->getBody()->getContents(); // Warning: call 'getBody()->getContents()' only once ! getContents() returns the remaining contents, so that a second call returns nothing unless you seek the position of the stream with rewind or seek

			$this->printBody($body);

			// Format the response
			$data = json_decode($body, true); // returns an array

			$content_type = $response->getHeader(self::CONTENT_TYPE)[0];

			if ($response->getStatusCode() != self::HTTP_OK) {
				// Response
				self::$climate->comment('Status code: ' . $response->getStatusCode());
				self::$climate->comment('Content-Type: ' . json_encode($response->getHeader('Content-Type'), JSON_PRETTY_PRINT));
				// self::$climate->info('Access-Control-Allow-Origin: ' . json_encode($response->getHeader('Access-Control-Allow-Origin'), JSON_PRETTY_PRINT));
			}

			// Asserzioni
			self::$climate->comment('Checking assertions...');
			$this->assertEquals($expected_status_code, $response->getStatusCode());
			$this->assertContains(self::APP_JSON_CT, $content_type);
			self::$climate->comment('...ok');
		}
		return $data;
	}
}
