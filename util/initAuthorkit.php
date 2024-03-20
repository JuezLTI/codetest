<?php
require_once('initTsugi.php');

if(!empty($jsonData['authorkit']['token'])){
    $restClientAuthorkit = new RestClient($CFG->apiConfigs['authorkit']['baseUrl'], $jsonData['authorkit']['token']['accessToken']);
}else{
    $restClientAuthorkit = new RestClient($CFG->apiConfigs['authorkit']['baseUrl'], null);
    $restClientAuthorkit->loginAuthor(
        $CFG->apiConfigs['authorkit']['user'],
        $CFG->apiConfigs['authorkit']['pass']
    );
}
$restClientAuthorkit->checkAuthorkitIsOnline();
$GLOBALS['REST_CLIENT_AUTHOR'] = $restClientAuthorkit;
