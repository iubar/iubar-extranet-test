<?php

namespace Extranet;

use GuzzleHttp\Psr7\Request;
use Iubar\Tests\RestApi_TestCase;

class ValidazioneCedolinoTest extends RestApi_TestCase {

	const URL = 'http://iubar.it/extranet/api/v1/validazione-cedolino';

	public static function setUpBeforeClass() {
		parent::init();
		self::$client = self::factoryClient();
	}

	public function testValidazioneCedolino() { // Post Json data

		self::$climate->comment('Testing validazione cedolino');
		$json = '{"doc":{"lav":{"cf":"LLAFBA88S25F839T"},"periodo":{"anno":"2017","mese":"06"},"retrib":{"netto":"346.00"},"tit":{"foglio":"3578","cf":"PZZSLD73E25C129P"}},"sig":"5b275219cb6abe4694e408037a087d8d7b5f5386"}';
		self::$climate->info('Request data: ' . $json);
		$response = self::$client->request(self::POST, self::URL, [
			'headers' => [
				'Content-Type' => 'application/json; charset=UTF-8',
				'X-Requested-With' => 'XMLHttpRequest'
			],
			'body' => $json
		]);
		$data = $this->checkResponse($response);
		$this->assertTrue($data['data']['valid']);

	}

}