<?php
require_once('initTsugi.php');
include('views/dao/menu.php'); // for -> $menu


$user = new \CT\CT_User($USER->id);
$_SESSION['tags'] = ["difficulty" => [], "type" => [], "keywords" => [], "averageGrade" => []];
$page = $_GET['page'];

if ($USER->instructor) {

    $array = \CT\CT_Test::findTestForImportByPage($page);
    $testForImport = $array['tests'];
    $totalPages = $array['totalPages'];

    echo $twig->render('pages/exercise-import.php.twig', array(
        'page' => $page,
        'OUTPUT' => $OUTPUT,
        'CFG' => $CFG_CT,
        'menu' => $menu,
        'help' => $help(),
        'testForImport' => $testForImport,
        'totalPages' => $totalPages,
    ));
} else {
    header('Location: ' . addSession('../../student-home.php'));
}
