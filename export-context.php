<?php
require_once('initTsugi.php');
include('views/dao/menu.php'); // for -> $menu
include('util/Functions.php');


$main = new \CT\CT_Main($_SESSION["ct_id"]);
$zipFinalFilename = createZipFileForExportContext($main);

$zipFilename_basename = basename($zipFinalFilename);
$zipFilename_filesize = filesize($zipFinalFilename);

// var_dump("THE END");die;

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="'.$zipFilename_basename.'"');
header('Content-Length: '.$zipFilename_filesize);
header('Expires: 0');
header('Pragma: public');
header('Cache-Control: must-revalidate');
header('Content-Description: File Transfer');

flush();
readfile($zipFinalFilename);
unlink($zipFinalFilename);


