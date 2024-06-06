<?php
require_once('initTsugi.php');
include('views/dao/menu.php'); // for -> $menu
include('util/Functions.php');

$main = new \CT\CT_Main($_SESSION["ct_id"]);
$tempFolder = 'tmp';

$importFile = $_FILES['import-file'];
$zip = new ZipArchive;

if ($zip->open($importFile['tmp_name']) !== TRUE) {
    exit("Invalid zip");
}

$mainContent = $zip->getFromName('main.json');
$mainContentArr = json_decode($mainContent, true);
$exercisesContent = $zip->getFromName('exercises/exercises.json');
$exercisesContentArr = json_decode($exercisesContent, true);

$currentTime = new DateTime('now', new DateTimeZone($CFG->timezone));
$currentTime = $currentTime->format("Y-m-d H:i:s");
$main = \CT\CT_Main::getMainFromContext($CONTEXT->id, $LINK->id, $USER->id, $currentTime);

// Main update >>

$main->setTitle($mainContentArr['title']);
$main->setSeenSplash($mainContentArr['seen_splash']);
$main->setShuffle($mainContentArr['shuffle']);
$main->setPoints($mainContentArr['points']);
$main->save();

// Main update <<
foreach($exercisesContentArr as $exercise) {

    // if exercise was created with codetest and not in authorkit
    if(isset($exercise['codeExercise']) && ($exercise['codeExercise'] || ($exercise['codeExercise'] == 'true'))){ //codetest
        $filename = "{$exercise['akId']}.zip";
        $exerciseContent = $zip->getFromName("exercises/{$filename}");
        $tmpFilePath = $tempFolder.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($tmpFilePath, $exerciseContent);
        $exerciseId = putExerciseOnRepo($tmpFilePath);
        $exercise = \CT\CT_Exercise::findExerciseForImportId($exerciseId);
        $exerciseCls = new \CT\CT_ExerciseCode();
        $exerciseCls->setFromObject($exercise);
        $exerciseCls->setCtId($_SESSION["ct_id"]);
        $exerciseCls->setCodeExercise(true);
        $exerciseCls->save();
        unlink($tmpFilePath);
    } else { //Authorkit
        downloadAkExercise($exercise['akId']);
        $akExercise = \CT\CT_Exercise::findExerciseForImportAkId($exercise['akId']);
        $akExercise->save();
    }
}

$_SESSION['success'] = "Main actualizado";
header( 'Location: '.addSession('index.php')) ;
