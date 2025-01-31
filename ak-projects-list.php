<?php
require_once('util/initAuthorkit.php');
include('views/dao/menu.php'); // for -> $menu

global $REST_CLIENT_AUTHOR, $CFG_CT;


try{
    $response = $REST_CLIENT_AUTHOR->getClient()->request('GET', 'projects');
    $projects = $response->toArray();
} catch (\Throwable $th) {
    $REST_CLIENT_AUTHOR->loginAuthor(
        $CFG_CT->apiConfigs['authorkit']['user'],
        $CFG_CT->apiConfigs['authorkit']['pass']
    );
    header('Location: ' . addSession('./ak-projects-list.php'));
}

// var_dump($response);
// var_dump($projects);die;


echo $twig->render('pages/ak-projects-list.php.twig', array(
    'projects' => $projects,
    'OUTPUT' => $OUTPUT,
    'CFG' => $CFG_CT,
    'menu' => $menu,
    'help' => $help(),
));

