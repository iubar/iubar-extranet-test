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
class IpaTest extends RestApi_TestCase {

    const ROUTE_BASE = "http://www.indicepa.gov.it/public-ws/";
    
    const AUTH_ID = 'JYMAINOL';
    
    const INDICE_PA_ROUTE = 'indice-pa';
    
    const INDICE_PA_DESC_ROUTE = 'indice-pa-desc';
    const INDICE_PA_FULLTEXT_ROUTE = 'indice-pa-fulltext';
    const INDICE_PA_CODUNIOU_REMOTE_ROUTE = 'indice-pa-coduniou-remote';
    const INDICE_PA_CODUNIOU_LOCAL_ROUTE = 'indice-pa-coduniou-local';
    
    const TIMEOUT_FOR_LONGER_TASK = 6;
    
    private static $test_cod_uni_ou1 = ['L21DA2' => 'Tribunale (Giudice Unico di Primo Grado) di Pesaro'];
    private static $test_cod_uni_ou2 = ['4FIWYW' => 'Tribunale (Giudice Unico di Primo Grado) di Trani - SPESE DI GIUSTIZIA'];
    private static $test_cod_uni_ou3 = ['Y783HY' => 'Tribunale (Giudice Unico di Primo Grado) di Pesaro - SPESE DI GIUSTIZIA'];
        
    // INDICE DEI SERVIZI
    //
    // COD ENDPOINT         INPUT                   OUTPUT dati registrati sull IPA di:
    // 1 WS01_SFE_CF.php    CF                      Uffici destinatari di Fatturazione Elettronica e dati SFE
    // 2 WS02_AOO.php       COD_AMM, COD_AOO        Una o tutte le Aree Organizzative Omogenee di un Ente
    // 3 WS03_OU.php        COD_AMM                 Unita Organizzative
    // 4 WS04_SFE.php       COD_AMM                 Uffici destinatari di Fatturazione Elettronica e dati SFE
    // 5 WS05_AMM.php       COD_AMM                 Dati dell’Ente
    // 6 WS06_OU_CODUNI.php COD_UNI_OU 
    // 7 WS07_EMAIL.php     EMAIL                   Entità presenti nell’IPA che contengono la EMAIL
    
    const WS1 = "WS01_SFE_CF.php";		// Ricerca per Codice Fiscale di Uffici destinatari di Fatturazione Elettronica
    const WS2 = "WS02_AOO.php"; 		// Lista delle Aree Organizzative Omogenee di un Ente (AOO)
    const WS3 = "WS03_OU.php"; 			// Lista delle Unita' Organizzative di un Ente (UO)
    const WS4 = "WS04_SFE.php"; 		// Lista dei Servizi di Fatturazione Elettronica di un Ente !!!
    const WS5 = "WS05_AMM.php";			// Dati dell'Ente
    const WS6 = "WS06_OU_CODUNI.php";	// Dati dell'Unita' Organizzativa e Fatturazione Elettronica per Codice Univoco Ufficio
    const WS7 = "WS07_EMAIL.php"; 		// Lista delle entita' associate ad uno specifico indirizzo email in IPA
        
    public static function setUpBeforeClass() {
        parent::init();
        self::$client = self::factoryClient();
    }    

    public function setUp() {
        // nothing to do
    }

    private function requestIpaApi($route, $data) {
        
        $response = null;
        try {        
            
            if(true){
                // 1) SOLUZIONE che utilizza Psr7\Request: OK
                $encoded_data = http_build_query($data, null, '&'); // @see: http://php.net/manual/en/function.http-build-query.php
                self::$climate->info('Request data: ' . $encoded_data);                
                $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
                $request = new Request(self::POST, self::ROUTE_BASE . $route, $headers, $encoded_data);
                $response = self::$client->send($request, [
                    'timeout' => self::TIMEOUT
                ]);
            }
            
            if(false){
                // 2) SOLUZIONE con JSON: NON VA ! ERROR 403 FORBIDDEN
                // Probabilmente perchè qui Guzzle non utilizza la codifica "x-www-form-urlencoded"
                $encoded_data = json_encode($data);
                self::$climate->info('Request data: ' . $encoded_data);
                $response = self::$client->request(self::POST, self::ROUTE_BASE . $route, ['body' => $encoded_data]);
            }
            
            if(false){
                // 3) SOLUZIONE classica: OK
                $encoded_data = json_encode($data);
                self::$climate->info('Request data: ' . $encoded_data);
                $response = self::$client->request(self::POST, self::ROUTE_BASE . $route, [
                   'form_params' => $data,
 				   'connect_timeout' => self::TIMEOUT, 	// the number of seconds to wait while trying to connect to a server
 				   'timeout' => self::TIMEOUT 			// the timeout of the request in seconds
     			]);                                
            }                      
            
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        
        $data = $this->checkResponse($response);
        $json = json_encode($data, JSON_PRETTY_PRINT);
        return $json;
    }
    
    public function testIpaApi() {
        
        // From Guzzle's doc:
        // Use 'form_params' for application/x-www-form-urlencoded requests, 
        // and 'multipart' for multipart/form-data requests.
        //
        // @see: 'form_params' http://guzzle.readthedocs.io/en/latest/request-options.html?highlight=getconfig#form-params
        // @see: 'multipart' http://guzzle.readthedocs.io/en/latest/request-options.html?highlight=getconfig#multipart               
        //
        // As it is explained in W3, the content type "multipart/form-data" should be used for submitting forms that contain files, non-ASCII data, and binary data.
        //
        // POST requests in Guzzle are sent with an application/x-www-form-urlencoded Content-Type header if POST fields are present but no files are being sent in the POST. If files are specified in the POST request, then the Content-Type header will become multipart/form-data.
        
        
        self::$climate->comment('Testing testIndicePaRoot...');
        
        $array1 = array('AUTH_ID' => self::AUTH_ID,  'CF' => '83003310725');    // Cod. fisc. servizio di F.E
        $array2 = array('AUTH_ID' => self::AUTH_ID,  'COD_AMM' => 'm_dg', 'COD_AOO' => '04104402106');  // '04104402106' è "Procura della Repubblica presso il Tribunale (Giudice Unico di Primo Grado) di PESARO"
        $array3 = array('AUTH_ID' => self::AUTH_ID,  'COD_AMM' => 'm_dg');
        $array4 = array('AUTH_ID' => self::AUTH_ID,  'COD_AMM' => 'm_dg');
        $array5 = array('AUTH_ID' => self::AUTH_ID,  'COD_AMM' => 'c_d488'); // Comune di Fano
        $array6 = array('AUTH_ID' => self::AUTH_ID,  'COD_UNI_OU' => key(self::$test_cod_uni_ou2));
        $array7 = array('AUTH_ID' => self::AUTH_ID,  'EMAIL' => 'filippo.bortone@giustizia.it');
        // COD ENDPOINT         INPUT                   OUTPUT dati registrati sull IPA di:
        // 1 WS01_SFE_CF.php    COD_FISC                Uffici destinatari di Fatturazione Elettronica e dati SFE
        // 2 WS02_AOO.php       COD_AMM, COD_AOO        Una o tutte le Aree Organizzative Omogenee di un Ente
        // 3 WS03_OU.php        COD_AMM                 Unita Organizzative
        // 4 WS04_SFE.php       COD_AMM                 Uffici destinatari di Fatturazione Elettronica e dati SFE
        // 5 WS05_AMM.php       COD_AMM                 Dati dell’Ente
        // 6 WS06_OU_CODUNI.php COD_UNI_OU
        // 7 WS07_EMAIL.php     EMAIL                   Entità presenti nell’IPA che contengono la EMAIL
                              
        $body1 = $this->requestIpaApi(self::WS1, $array1);                      
        $this->assertBodyContains($body1, key(self::$test_cod_uni_ou2));
        
        $body2 = $this->requestIpaApi(self::WS2, $array2);      
        $this->assertBodyContains($body2, 'PESARO');

        $body3 = $this->requestIpaApi(self::WS3, $array3);
        $this->assertBodyContains($body3, 'Giudice di Pace di Ivrea');
        
        $body4 = $this->requestIpaApi(self::WS4, $array4);      
        $this->assertBodyContains($body4, 'Giudice di Pace di Ivrea');
        
        $body5 = $this->requestIpaApi(self::WS5, $array5);      
        $this->assertBodyContains($body5, 'Fano');
        
        $body6 = $this->requestIpaApi(self::WS6, $array6);      
        $this->assertBodyContains($body6, key(self::$test_cod_uni_ou2));
        
        $body7 = $this->requestIpaApi(self::WS7, $array7);
        $this->assertBodyContains($body7, key(self::$test_cod_uni_ou2));
    }
    
    
    public function testIndicePa1() {
        self::$climate->comment('Testing IndicePaByCodiceUnivoco...');
        $array = array(
            'codice_univoco' => key(self::$test_cod_uni_ou2)
        );
 
        $response = $this->sendGetReq(self::INDICE_PA_ROUTE, $array, self::TIMEOUT);
        $data = $this->checkResponse($response);
        // TODO: $this->assert...;
    
    }
    
    /**
     * Test for the IndicePa.php class
     */
    public function testIndicePa2() {
        self::$climate->comment('Testing IndicePaByDenominazione...');
        $array = array(
            'denominazione' => current(self::$test_cod_uni_ou2)
        );
  
        $response = $this->sendGetReq(self::INDICE_PA_ROUTE, $array, self::TIMEOUT);
        $data = $this->checkResponse($response);
        // TODO: $this->assert...;
    
    }
        
    
    /**
     * Test for the IndicePa.php class
     */
    public function testWhichIsBetter1() {
        self::$climate->comment('Testing IndicePaByCodiceUnivoco...');
        $array = array(
            'codice_univoco' => key(self::$test_cod_uni_ou2)
        );

        $bench = new \Ubench();
        
        $bench->start();
        $response = $this->sendGetReq(self::INDICE_PA_CODUNIOU_LOCAL_ROUTE, $array, self::TIMEOUT);
        $bench->end();
        $elapsed1 = $bench->getTime(true);
        self::$climate->shout('Elapsed time: ' . $bench->getTime());        
        $data = $this->checkResponse($response);
        // TODO: $this->assert...;
        
        $bench->start();
        $response = $this->sendGetReq(self::INDICE_PA_CODUNIOU_REMOTE_ROUTE, $array, self::TIMEOUT);
        $bench->end();
        $elapsed2 = $bench->getTime(true);
        self::$climate->shout('Elapsed time: ' . $bench->getTime());        
        $data = $this->checkResponse($response);
        // TODO: $this->assert...;
                
        // TODO: assert both results are the same...
        
        $msg = self::INDICE_PA_CODUNIOU_LOCAL_ROUTE;
        if($elapsed1<=$elapsed2){
            $msg = self::INDICE_PA_CODUNIOU_REMOTE_ROUTE;
        }
        self::$climate->shout('La rotta più performante tra ' . self::INDICE_PA_CODUNIOU_LOCAL_ROUTE . ' e ' . self::INDICE_PA_CODUNIOU_REMOTE_ROUTE . ' è ' . $msg);
        self::$climate->shout('Punteggio ' . $this->calcPerc($elapsed1, $elapsed2) . '%');
                               
    }
        
    /**
     * Test for the IndicePa.php class
     */    
    public function testWhichIsBetter2() {
        self::$climate->comment('Testing IndicePaByDenominazione...');
        $array = array(
            'denominazione' => current(self::$test_cod_uni_ou2)
        );
        
        $bench = new \Ubench();
        
        $bench->start();
        $response = $this->sendGetReq(self::INDICE_PA_FULLTEXT_ROUTE, $array, self::TIMEOUT_FOR_LONGER_TASK);
        $bench->end();
        $elapsed1 = $bench->getTime(true);
        self::$climate->shout('Elapsed time: ' . $bench->getTime());
        $data = $this->checkResponse($response);
        // TODO: $this->assert...;                
        
        $bench->start();
        $response = $this->sendGetReq(self::INDICE_PA_DESC_ROUTE, $array, self::TIMEOUT_FOR_LONGER_TASK);
        $bench->end();
        $elapsed2 = $bench->getTime(true);
        self::$climate->shout('Elapsed time: ' . $bench->getTime());
        $data = $this->checkResponse($response);
        // TODO: $this->assert...;
        
        // TODO: assert both results are the same...
        
        $msg = self::INDICE_PA_FULLTEXT_ROUTE;
        if($elapsed1<=$elapsed2){
            $msg = self::INDICE_PA_DESC_ROUTE;
        }
        self::$climate->shout('La rotta più performante tra ' . self::INDICE_PA_FULLTEXT_ROUTE . ' e ' . self::INDICE_PA_DESC_ROUTE . ' è ' . $msg);
        self::$climate->shout('Punteggio ' . $this->calcPerc($elapsed1, $elapsed2) . '%');        
        
    }    
        
    public function testLocalDescSearch() {
        self::$climate->comment('Testing testLocalDescSearch...');
        $array = array(
            'denominazione' => current(self::$test_cod_uni_ou2)
        );
        
        $response = $this->sendGetReq(self::INDICE_PA_DESC_ROUTE, $array, self::TIMEOUT_FOR_LONGER_TASK);
        $data = $this->checkResponse($response);
        // TODO: $this->assert...;
    
    }
    
    public function testFullTextSearch() {
        self::$climate->comment('Testing testFullTextSearch...');
        $array = array(
            'denominazione' => 'Tribunale di Pesaro'
        );
    
        $response = $this->sendGetReq(self::INDICE_PA_FULLTEXT_ROUTE, $array, self::TIMEOUT_FOR_LONGER_TASK);
        $data = $this->checkResponse($response);
        // TODO: $this->assert...;
    
    }    
        
    
    public function testByCodiceUnivoco() {
        self::$climate->comment('Testing testByCodiceUnivoco...');
        $array = array(
            'codice_univoco' => key(self::$test_cod_uni_ou2)
        );
    
        $response = $this->sendGetReq(self::INDICE_PA_CODUNIOU_LOCAL_ROUTE, $array, self::TIMEOUT);
        $data = $this->checkResponse($response);
        // TODO: $this->assert...;
         
    }
        
    public function testRemoteCodUniOu() {
        self::$climate->comment('Testing testRemoteCodUniOu...');
        $array = array(
            'codice_univoco' => key(self::$test_cod_uni_ou2)
        );
 
        $response = $this->sendGetReq(self::INDICE_PA_CODUNIOU_REMOTE_ROUTE, $array, self::TIMEOUT);
        $data = $this->checkResponse($response);
        // TODO: $this->assert...;
          
    }
    
        
    private function assertBodyContains($body, $txt){
        $this->assertRegexp('/' . $txt . '/', $body);
       //  $this->assertContains($txt, $body);
    }
    
    private function calcPerc($a, $b){
        $perc = min($a, $b) / max($a, $b);
        $perc = round($perc, 4, PHP_ROUND_HALF_UP);
        $perc = $perc * 100;
        return $perc;
    }
    
  
}


// NOTA

// Esempio:
//
// Tribunale (Giudice Unico di Primo Grado) di Trani
// cod_amm: m_dg
// cod_ou: 07204502206
// cod_uni_ou: BFB0AI
// cf: 83003310725
//
// Tribunale (Giudice Unico di Primo Grado) di Trani - SPESE DI GIUSTIZIA
// cod_amm: m_dg
// cod_ou: SG07204502206
// cod_uni_ou: 4FIWYW
// cf: 83003310725
//
// che i due cf siano uguali, è solo una coincidenza che non si verifica per tutti i tribunali



// DOCS
//
// http://www.indicepa.gov.it/documentale/n-consulta-dati.php
// http://www.indicepa.gov.it/public-services/docs-read-service.php?dstype=FS&filename=WS00_INDICE_DEI_SERVIZI.pdf

// FILE
//
// http://www.indicepa.gov.it/documentale/n-opendata.php


// Tabella dei codici di errore
//
// cod_err desc_err
// 0 Nessun errore
// 1 Parametro CF mancante
// 2 Parametro CF non valorizzato
// 3 Parametro CF valorizzato erroneamente
// 10 Parametro EMAIL mancante
// 11 Parametro EMAIL non valorizzato
// 12 Parametro EMAIL valorizzato erroneamente
// 20 Parametro COD_AMM mancante
// 21 Parametro COD_AMM non valorizzato
// 22 Parametro COD_AMM valorizzato erroneamente
// 23 Valore COD_AMM non presente in archivio
// 30 Parametro COD_UNI_OU mancante
// 31 Parametro COD_UNI_OU non valorizzato
// 32 Parametro COD_UNI_OU valorizzato erroneamente
// 40 Parametro COD_AOO mancante
// 41 Parametro COD_AOO non valorizzato
// 42 Parametro COD_AOO valorizzato erroneamente
// 900 Parametro AUTH_ID mancante
// 901 Parametro AUTH_ID non valorizzato


// GUZZLE ASYNC REQUEST (http://docs.guzzlephp.org/en/latest/faq.html#can-guzzle-send-asynchronous-requests)

// $promise = $client->requestAsync(self::POST, $ws1, ['form_params' => $array1]);
// $promise->then(
// 		function (ResponseInterface $res) {
// 			echo "Status code: " . $res->getStatusCode() . PHP_EOL;
// 		},
// 		function (RequestException $e) {
// 			echo "Message: " . $e->getMessage() . PHP_EOL;
// 			echo "Method: " . $e->getRequest()->getMethod() . PHP_EOL;
// 		}
// 		);
// Force the pool of requests to complete.
// $promise->wait();

