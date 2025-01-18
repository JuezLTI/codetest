<?php
require_once('initTsugi.php');

try {
    if(!empty($jsonData['authorkit']['token'])){
        $restClientAuthorkit = new RestClient($CFG_CT->apiConfigs['authorkit']['baseUrl'], $jsonData['authorkit']['token']['accessToken']);
    }else{
        $restClientAuthorkit = new RestClient($CFG_CT->apiConfigs['authorkit']['baseUrl'], null);
        $restClientAuthorkit->loginAuthor(
            $CFG_CT->apiConfigs['authorkit']['user'],
            $CFG_CT->apiConfigs['authorkit']['pass']
	);
    }
} catch(Exception $e) {
	error_log($e->getMessage());
}

$restClientAuthorkit->checkAuthorkitIsOnline();
$GLOBALS['REST_CLIENT_AUTHOR'] = $restClientAuthorkit;
