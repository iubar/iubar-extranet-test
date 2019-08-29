<?php
namespace Extranet;

use League\CLImate\CLImate;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Iubar\Tests\RestApi_TestCase;

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
class OthersTest extends RestApi_TestCase {

	const API_REG_GRATUITA = 'http://www.iubar.it/crm/api/crm/v1/register-client/1';

	const TWITTER_ROUTE = 'twitter';

	const RSS_ROUTE = 'rss';

	const CHECKSUM_ROUTE = 'checksum';

	const IP2GEO_ROUTE = 'ip2geo';

	const GEODECODER_ROUTE = 'geodecode';

	const GEOREVERSEDECODER_ROUTE = 'georeversedecode';

	const BENCHMARK_ROUTE = 'benchmark';

	const ELEM_LIMIT = 3;

	const LENGTH = 100;

	const TIMEOUT_FOR_LONGER_TASK = 8; // seconds

	public static function setUpBeforeClass() : void {
		parent::init();
		self::$client = self::factoryClient();
	}

	public function setUp() {
		// nothing to do
	}

	public function testRegGratuitaApi(){
		self::$climate->info('Testing Registrazione gratuita...');
		$data = [];
		$encoded_data = http_build_query($data, null, '&'); // @see: http://php.net/manual/en/function.http-build-query.php
		self::$climate->info('Request data: ' . $encoded_data);
		$headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
		$request = new Request(self::POST, self::API_REG_GRATUITA, $headers, $encoded_data);
		$response = self::$client->send($request, [
			'timeout' => self::TIMEOUT
		]);

		$this->checkResponse($response, self::HTTP_BAD_REQUEST);
	}

	/**
	 * Test for the ClientTwitter.php class
	 *
	 * @uses Some tweet could be filtered so the ELEM_LIMIT and the number of tweet could be different
	 */
	public function testTwitter() {
		self::$climate->info('Testing Twitter...');
		$array = array(
			'limit' => self::ELEM_LIMIT
		);
		$response = $this->sendGetReq(self::TWITTER_ROUTE, $array);
		$body = $this->checkResponse($response);
		$data = $body['data'];
		$this->assertEquals(self::ELEM_LIMIT, count($data));
		$first_obj = $data[0];
		$this->assertArrayHasKey('short_text', $first_obj);
	}

	/**
	 * Test for the ClientRss.php class
	 */
	public function testRss() {
		self::$climate->info('Testing Rss...');
		$array = array(
			'limit' => self::ELEM_LIMIT,
			'length' => self::LENGTH
		);
		$response = $this->sendGetReq(self::RSS_ROUTE, $array, self::TIMEOUT_FOR_LONGER_TASK);
		$data = $this->checkResponse($response);
		$this->assertEquals(self::ELEM_LIMIT, count($data));
	}

	/**
	 * Test for the Checksum.php class
	 */
	public function testChecksum() {
		self::$climate->info('Testing Checksum...');
		if (self::getHost() == 'http://www.iubar.it/extranet/api') {
			$host = 'iubar.it';
			$file = 'downloads/assistenza/TeamViewerQS_it.exe';
			$remote_file = '/var/www/iubar.it/' . $file;
			$remote_url = 'http://www.' . $host . '/' . $file;
			$exists = $this->remoteFileExists($remote_url);
			if (!$exists) {
				$this->fail('Remote file ' . $file . ' does not exist on host ' . $host);
			}
		} else
			if (self::getHost() == 'http://extranet.local/api') {
				$host = 'extranet.local';
				$file = 'img/iubar_logo_75.png';
				$user_home = getenv('userprofile');
				$project_folder = $user_home . "/workspace_php/php-extranet/www/public";
				$remote_file = $project_folder . DIRECTORY_SEPARATOR . $file;
				$remote_url = 'http://' . $host . '/' . $file;
				$exists = $this->remoteFileExists($remote_url);
				if (!$exists) {
					$this->fail('Remote file ' . $file . ' does not exist on host ' . $host);
				}
			} else {
				$this->fail('Test config error');
			}

		$array = array(
			'file' => $remote_file
		);
		$response = $this->sendGetReq(self::CHECKSUM_ROUTE, $array);
		$data = $this->checkResponse($response);

		$this->assertArrayHasKey('hash', $data['data']);
		$this->assertEquals(40, strlen($data['data']['hash']));
	}

	/**
	 * Test for the Benchmark.php class
	 */
	public function testBenchmark() {
		self::$climate->info('Testing Benchmark(...');
		$bench = new \Ubench();
		$bench->start();
		$sec = 15;
		$timeout = $sec + self::TIMEOUT;
		$array = array(); // no params required
		self::$climate->info('Waiting the long response...');
		$response = $this->sendGetReq(self::BENCHMARK_ROUTE . '/' . $sec, $array, $timeout);
		$data = $this->checkResponse($response);
		$bench->end();
		self::$climate->info('TIME: ' . $bench->getTime());
		self::$climate->info('You should subtract ' . $sec . ' seconds');
		$this->assertEquals($sec, $data['data']['number']);
	}

	public function testGeoDecoder() {
		// e.g.: http://extranet/api/geodecode?address=Via%20Arco%20di%20Augusto%2076,%20Fano%20(PU),%20Italy
		self::$climate->info('Testing testGeoDecoder(...');
		$address = 'Via Arco di Augusto 76, Fano (PU), Italy';
		$array = array(
			'address' => $address
		);
		$response = $this->sendGetReq(self::GEODECODER_ROUTE, $array);
		$data = $this->checkResponse($response);
		self::$climate->dump($data);
				
		// Stop here and mark this test as incomplete.
        	//$this->markTestIncomplete(
          	//	'This test has not been implemented yet.'
        	//);
		
		$findme = 'Via Arco D\'Augusto';
		$regex = '/' . $findme . '/';
		$first_result = $data['data']['results'][0];
		$formatted_address = $first_result['formatted_address'];
		$lat = $first_result['geometry']['location']['lat'];
		$expected = substr('43.844', 0, 6);
		$actual = substr($lat, 0, 6);
		$this->assertEquals($expected, $actual);
		$this->assertRegexp($regex, $formatted_address);
	}

	public function testGeoReverseDecoder() {
		// e.g.: http://extranet/api/georeversedecode?lat=43.8443835&lng=13.0163099
		self::$climate->info('Testing testGeoReverseDecoder(...');
		$array = array(
			'lat' => '43.8446287',
			'lng' => '13.0166416'
		);
		$response = $this->sendGetReq(self::GEOREVERSEDECODER_ROUTE, $array);
		// $data = $this->checkResponse($response);
		$data = json_decode($response->getBody()->getContents(), true);
		$expected1 = 'Piazza Andrea Costa';
		$expected2 = 'Via dè da Carignano';
		$this->assertEquals($expected1, $data['data']['results'][0]['address_components'][1]['long_name']);
	}

	public function testIp2Geo() {
		// e.g.: http://extranet/api/ip2geo?ip=217.133.38.27
		self::$climate->info('Testing testIp2Geo(...');
		$ip = '217.133.38.27';
		$array = array(
			'ip' => $ip
		);
		$response = $this->sendGetReq(self::IP2GEO_ROUTE, $array);
		$data = $this->checkResponse($response);
		// $data = json_decode($response->getBody()->getContents(), true);
		$this->assertArrayHasKey('geoname_id', $data['data']['city']);
		$this->assertEquals('Fano', $data['data']['city']['names']['en']);
	}

	/*
	 * Duplicato del metodo omonimo della classe https://github.com/iubar/iubar-php-common/blob/master/src/Iubar/Web/WebUtil.php
	 */
	private function remoteFileExists($url) {
		$curl = curl_init($url);
		// don't fetch the actual page, you only want to check the connection is ok
		curl_setopt($curl, CURLOPT_NOBODY, true);
		// do request
		$result = curl_exec($curl);
		$ret = false;
		// if request did not fail
		if ($result !== false) {
			// if request was ok, check response code
			$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			if ($statusCode == 200) {
				$ret = true;
			}
		}
		curl_close($curl);
		return $ret;
	}

/**
 * Test for the Stripe.php class
 */
	// public function testStripeGet() {
	// // TODO: vedere con Daniele. La rotta 'stripe' non è ancora implementata
	// }

	// public function testStripePost() {
	// // TODO: vedere con Daniele. La rotta 'stripe' non è ancora implementata
	// }

/**
 * Test for the ValidazioneCedolino.php class
 */
	// public function testValidazioneCedolino() {
	// $route = 'validazione-cedolino';
	// $method = 'POST';
	// }
}
