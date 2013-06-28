<?php
require_once('config.php');

$rootDir = dirname(__FILE__);
set_include_path($rootDir . '/library' . PATH_SEPARATOR . get_include_path());

require_once('library/Zend/Loader/Autoloader.php');
Zend_Loader_Autoloader::getInstance();


function login($username, $password) {
	$client = new Zend_Http_Client('https://dealmatch.dominicks.com/LoginRequired', array(
	    'maxredirects' => 10,
	    'timeout'      => 30));
	$client->setCookieJar();

	$response = $client->request();
	//echo $response->getBody();

	$pattern = '/__VIEWSTATE" value="(.*)"/';
	$matches = array();
	preg_match($pattern,$response->getBody(),$matches);
	$VIEWSTATE = $matches[1];

	$pattern = '/__EVENTVALIDATION" value="(.*)"/';
	preg_match($pattern,$response->getBody(),$matches);
	$EVENTVALIDATION = $matches[1];


	$client->setParameterPost('__EVENTTARGET', 'ctl00$C$btnSignIn');
	$client->setParameterPost('__EVENTARGUMENT', '');
	$client->setParameterPost('__VIEWSTATE', $VIEWSTATE);
	$client->setParameterPost('__EVENTVALIDATION', $EVENTVALIDATION);

	$response = $client->request('POST');
	//echo $response->getBody();

	$client->setUri('https://auth.dominicks.com/opensso/UI/Login');
	$client->setParameterPost('goto', 'aHR0cHM6Ly9kZWFsbWF0Y2guZG9taW5pY2tzLmNvbQ==');
	$client->setParameterPost('SunQueryParamsString', '');
	$client->setParameterPost('encoded', 'true');
	$client->setParameterPost('arg', 'newsession');
	$client->setParameterPost('IDToken1', $username);
	$client->setParameterPost('fakePassword', '8-12 characters');
	$client->setParameterPost('IDToken2', $password);
	$response = $client->request('POST');

	//echo $response->getBody();i
	return $client;
}

$client = login($username, $password);
//jewel dealmatch
$client->setUri('https://dealmatch.dominicks.com/Offers/AddAllToDealList');
$dealmatch = json_encode(array('CompetitorID' => 5, 'CategoryID' => 0,'SwySLPreference' => 'CO'));
$response = $client->setRawData($dealmatch, 'application/json')->request('POST');

//target dealmatch
$dealmatch = json_encode(array('CompetitorID' => 4, 'CategoryID' => 0,'SwySLPreference' => 'CO'));
$response = $client->setRawData($dealmatch, 'application/json')->request('POST');

function justFourRequest($clip, $client) {
	$clips = array('clips' => $clip);
	$just4u = json_encode($clips);
	$just4u = str_replace(array('":{"','"}}'), array('":[{"','"}]}'), $just4u);
	//echo $just4u , "\n\n";
	$client->setUri('http://www.dominicks.com/Clipping1/services/clip/offers');
	$response = $client->setRawData($just4u, 'application/json')->request('POST');
}

function justFourU($client, $url) {
	//just 4 you
	$client->setUri($url);
	$response = $client->request('GET');

	//echo $response->getBody();

	$dealMatchArr = json_decode($response->getBody(), true);
	//var_dump($dealMatchArr['offers']);
	$clips = array();
	foreach ($dealMatchArr['offers'] as $offer) {

		if ($offer['clipStatus'] == 'U') {
	//		var_dump($offer);
			$clip = array();
			$clip['offerId'] = $offer['offerId'];
			$clip['offerPgm'] = $offer['offerPgm'];
/*			$client->setUri('http://www.dominicks.com/J4UProgram1/services/offer/'.$offer['offerId'].'/definition/');
			$response = $client->request();
			$offer = json_decode($response->getBody(), true);
			if (isset($offer['vndrBannerCd'])) {
				$clip['vndrBannerCd'] = $offer['vndrBannerCd'];
			}*/
			//echo $clip['offerId'] . "\n";
			justFourRequest($clip, $client);
			$clips[] = $clip;
		}
	}
	if (count($clips) > 0) {
		$clips = array('clips' => $clips);
//		$just4u = json_encode($clips);
//		echo $just4u;
//		$client->setUri('http://www.dominicks.com/Clipping1/services/clip/offers');
//		$response = $client->setRawData($just4u, 'application/json')->request('POST');
		echo 'added: ' . count($clips['clips']) . "\n";
	} else {
		echo 'No deals for: ' . $url;
	}
}
justFourU($client, 'http://www.dominicks.com/J4UProgram1/services/program/CC/offer/allocations');
justFourU($client, 'http://www.dominicks.com/J4UProgram1/services/program/PD/offer/allocations');
