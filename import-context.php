<?php
require_once('initTsugi.php');
include('views/dao/menu.php'); // for -> $menu
include('util/Functions.php');

$currentTime = new DateTime('now', new DateTimeZone($CFG->timezone));
$currentTime = $currentTime->format("Y-m-d H:i:s");
$main = \CT\CT_Main::getMainFromContext($CONTEXT->id, $LINK->id, $USER->id, $currentTime);
$importFile = $_FILES['import-file'];

importContextFromZipFile($main, $importFile['tmp_name']);

$_SESSION['success'] = "Main actualizado";
header( 'Location: '.addSession('index.php')) ;
