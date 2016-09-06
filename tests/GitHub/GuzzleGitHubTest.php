<?php

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

class GuzzleGitHubTest extends PHPUnit_Framework_TestCase {
	
	protected $client = null;

	protected function setUp(){		
		echo "Class " . get_class($this) . PHP_EOL;		
		$this->client = new GuzzleHttp\Client([
				// Base URI is used with relative requests
				'base_uri' => 'https://api.github.com/user'
		]);
	}

	public function testAuth_Github() {

		$res = null;
		
		$github_user = getenv('GITHUB_USER');
		$github_password = getenv('GITHUB_PASSWORD');
		
		try {
		    
			$res = $this->client->request('GET', '/user', [
					'auth' => [$github_user, $github_password]
					// oppure 'query' => ['bookId' => 'hitchhikers-guide-to-the-galaxy']
			]);
		} catch (ClientException $e) {
			echo 'Uh oh oh ! ' . $e->getMessage() . PHP_EOL;
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

		$this->assertNotNull($res);
		$this->assertEquals(200, $res->getStatusCode());
		$body = $res->getBody();		
		$obj = json_decode($body);
		$id = $obj->{'id'};	 				
		$this->assertEquals($id, '7045594');
	
	}


}