<?php
namespace Extranet;

use League\CLImate\CLImate;
use GuzzleHttp\Client;
use Iubar\Tests\RestApi_TestCase;

/**
 * API Test
 *
 * @author Borgo
 */
class MailingListTest extends RestApi_TestCase {

	const SUBSCRIBE = 'mailing-list/subscribe';

	const IS_SUBSCRIBED = 'mailing-list/issubscribed';

	const IS_UNSUBSCRIBED = 'mailing-list/isunsubscribed';

	const WAS_SUBSCRIBED = 'mailing-list/wassubscribed';

	const UNSUBSCRIBE = 'mailing-list/unsubscribe';

	const COUNT = 'mailing-list/count';

	const EDIT = 'mailing-list/edit';

	const MAILING_LIST_ID = 1;

	const MAILING_LIST_PROFESSION_ID = 2;

	const FIRST_NAME = 'NomeTest';

	const SECOND_NAME = 'CognomeTest';

	const ML_EMAIL_EXAMPLE = 'pippo@iubar.it';

	const TOKEN = "000000000";

	const TIMEOUT_FOR_LONGER_TASK = 10;
 // seconds
	private $force = true;

// 	public static function setUpBeforeClass() {
// 		parent::init();
// 		self::$climate->info('MAILING LIST USER EMAIL: ' . self::ML_EMAIL_EXAMPLE);
// 		$base_url = self::getHost() . '/' . 'mailing-list/';
// 		self::$client = self::factoryClient(self::getHost(), $base_url);
// 	}

	public static function setUpBeforeClass() : void {
		parent::init();
		self::$client = self::factoryClient();
	}

	public function setUp() : void {
		// nothing to do
	}

	/**
	 * Subscribe into the mailing list
	 */
	public function testSubscribe() {
		self::$climate->info('testSubscribe...');
		$array = array(
			'email' => self::ML_EMAIL_EXAMPLE,
			'nome' => self::FIRST_NAME,
			'cognome' => self::SECOND_NAME,
			'professione_id' => self::MAILING_LIST_PROFESSION_ID,
			'list_id' => self::MAILING_LIST_ID,
			'force' => $this->force
		);
		$bench = new \Ubench();
		$bench->start();
		$is_subscribed = $this->isSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID);
		$bench->end();
		self::$climate->debug('UBench for the "' . self::IS_SUBSCRIBED . '" route: ' . $bench->getTime(false, '%d%s'));
		$bench->start();
		$is_unsubscribed = $this->isUnsubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID);
		$bench->end();
		self::$climate->debug('UBench for the "' . self::IS_UNSUBSCRIBED . '" route: ' . $bench->getTime(false, '%d%s'));
		$bench->start();
		$response = $this->sendGetReq(self::SUBSCRIBE, $array, self::TIMEOUT_FOR_LONGER_TASK);
		$bench->end();
		self::$climate->red('UBench for the "' . self::SUBSCRIBE . '" route: ' . $bench->getTime(false, '%d%s'));

		if ($is_subscribed) {
			$this->assertEquals(self::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
			// $body = $response->getBody()->getContents();
			// $data = json_decode($body, true);
		} else
			if (!$this->force && $is_unsubscribed) {
				$this->assertEquals(self::HTTP_BAD_REQUEST, $response->getStatusCode());
			} else {
				$data = $this->checkResponse($response);
			}

		if ($this->force && !$this->isSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID)) {
			$this->fail('The user ' . self::ML_EMAIL_EXAMPLE . ' is subscribed to the list ' . self::MAILING_LIST_ID);
		} else
			if (!$this->force && !$this->wasSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID)) {
				$this->fail('The user ' . self::ML_EMAIL_EXAMPLE . ' was not subscribed to the list ' . self::MAILING_LIST_ID);
			}
	}

	/**
	 * Edit from the mailing list
	 */
	public function testEditSubscription() {
		self::$climate->info('testEditSubscription...');
		if (!$this->force && !$this->wasSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID)) {
			$this->fail('The user ' . self::ML_EMAIL_EXAMPLE . ' is NOT subscribed to the list ' . self::MAILING_LIST_ID);
		}
		// Param 'list_id' cannot be modified
		$array = array(
            'email' => self::ML_EMAIL_EXAMPLE,
            'list_id' => self::MAILING_LIST_ID,
			'nome' => self::FIRST_NAME,
			'cognome' => self::SECOND_NAME,
			'professione_id' => self::MAILING_LIST_PROFESSION_ID
		);
		$url = self::EDIT . '/token/' . self::TOKEN;
		$response = $this->sendGetReq($url, $array);
		$data = $this->checkResponse($response);
	}

	/**
	 * Unsubscribe from the mailing list
	 *
	 * @uses Can't unsubribe twice but only once. Even if you retry to subscribe, you can't.
	 */
	public function testUnsubscribe() {
		self::$climate->info('testUnsubscribe...');
		$array = array(
			'email' => self::ML_EMAIL_EXAMPLE,
			'list_id' => self::MAILING_LIST_ID
		);
		$bench = new \Ubench();
		$bench->start();
		$is_unsub = $this->isUnsubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID);
		$bench->end();
		self::$climate->debug('UBench for the "' . self::IS_SUBSCRIBED . '" route: ' . $bench->getTime(false, '%d%s'));
		$bench->start();
		$response = $this->sendGetReq(self::UNSUBSCRIBE . '/token/' . self::TOKEN, $array, self::TIMEOUT_FOR_LONGER_TASK);
		$bench->end();
		self::$climate->red('UBench for the "' . self::UNSUBSCRIBE . '" route: ' . $bench->getTime(false, '%d%s'));

		if ($is_unsub) {
			self::$climate->red('\$is_unsub is true');
			$this->assertEquals(self::HTTP_BAD_REQUEST, $response->getStatusCode());
			// $body = $response->getBody()->getContents();
			// $data = json_decode($body, true);
		} else {
			self::$climate->red('\$is_unsub is false');
			$data = $this->checkResponse($response);
		}
		// if($this->isSubscribed(self::ML_EMAIL_EXAMPLE, self::MAILING_LIST_ID)){
		// $this->fail('The user ' . self::ML_EMAIL_EXAMPLE . ' is subscribed to the list ' . self::MAILING_LIST_ID);
		// }
	}

	/**
	 * Count mailing list users
	 */
	public function testCount() {
		self::$climate->info('testCount...');
		$array = array();
		$encoded_data = json_encode($array);
		$response = $this->sendGetReq(self::COUNT, $array);

		$body = $this->checkResponse($response);
		$response = $body['data']['count'];
		$this->assertTrue(is_numeric($response));
	}

	private function wasSubscribed($email, $list_id) {
		return $this->getSubscribed(self::WAS_SUBSCRIBED, $email, $list_id);
	}

	private function isSubscribed($email, $list_id) {
		return $this->getSubscribed(self::IS_SUBSCRIBED, $email, $list_id);
	}

	private function isUnsubscribed($email, $list_id) {
		return $this->getSubscribed(self::IS_UNSUBSCRIBED, $email, $list_id);
	}

	private function getSubscribed($route, $email, $list_id) {
		$b = false;
		self::$climate->info('getSubscribed() begin: route is "' . $route . '"...');
		$array = array(
			'email' => self::ML_EMAIL_EXAMPLE,
			'list_id' => self::MAILING_LIST_ID
		);

		$response = $this->sendGetReq($route, $array);

		$this->assertStringContainsString(self::APP_JSON_CT, $response->getHeader(self::CONTENT_TYPE)[0]);
		if ($response->getStatusCode() == self::HTTP_OK) {
			$body = $response->getBody()->getContents();
			$data = json_decode($body, true);
			$result = $data['data']['result'];
			if ($result === true) {
				$b = true;
			} else
				if ($result === false) {
					$b = false;
				} else {
					self::$climate->error('Response body: ' . PHP_EOL . $body);
					$this->fail('Situazione imprevista');
				}
		} else {
			self::$climate->error('Response code is: ' . $response->getStatusCode());
			$this->fail('Situazione imprevista: probabile errore sui tipi');
		}

		// Getting data
		// $data = json_decode($response->getBody()->getContents(), true);

		self::$climate->info('..,isSubscribed() end');
		return $b;
	}
}
