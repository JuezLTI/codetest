<?php
require_once('initTsugi.php');
include('views/dao/menu.php'); // for -> $menu
include('util/Functions.php');
use Symfony\Component\Filesystem\Filesystem;

function getLibrariesOnExercise($librariesPath) {
    $libraries = array();
    $directories = glob($librariesPath . '/*', GLOB_ONLYDIR);
    foreach ($directories as $dir) {
        $metadataPath = $dir . '/metadata.json';
        if (file_exists($metadataPath)) {
            $metadataContent = file_get_contents($metadataPath);
            $metadata = json_decode($metadataContent, true);
            if (isset($metadata['pathname']) && isset($metadata['id'])) {
                $libraries[] = array(
                    'name' => $metadata['pathname'],
                    'path' => $librariesPath.DIRECTORY_SEPARATOR.$metadata['id'].DIRECTORY_SEPARATOR.$metadata['pathname'],
                );
            }
        }
    }
    return $libraries;
}

$main = new \CT\CT_Main($_SESSION["ct_id"]);
$filesystem = new Filesystem();
$tempFolder = '/tmp';

$importFile = $_FILES['import-file'];
$zip = new ZipArchive;
$zipExercise = new ZipArchive;

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
        $exerciseContent = $zip->getFromName("exercises/{$exercise['akId']}.zip");
        if ($exerciseContent) {
            $tmpFilePath = $tempFolder.DIRECTORY_SEPARATOR.$exercise['akId'];
            file_put_contents($tmpFilePath . ".zip", $exerciseContent);
            if ($zipExercise->open($tmpFilePath . ".zip") === TRUE) {
                $zipExercise->extractTo($tmpFilePath);
                $zipExercise->close();
                $librariesPath = $tmpFilePath.DIRECTORY_SEPARATOR."libraries";
                $librariesNames = getLibrariesOnExercise($librariesPath);
                $exerciseMain = $main->createExercise($exercise, strtolower($exercise['exercise_language']), $exercise["difficulty"], $librariesNames, $exercise['visibleTest']);
                $main->saveExercises(array($exerciseMain), $updateExercises = false);
                unlink($tmpFilePath . ".zip");
                $filesystem->remove($tmpFilePath);
            }
        }
    } else { //Authorkit
        downloadAkExercise($exercise['akId']);
        $akExercise = \CT\CT_Exercise::findExerciseForImportAkId($exercise['akId']);
        $akExercise->save();
    }
}

$zip->close();

$_SESSION['success'] = "Main actualizado";
header( 'Location: '.addSession('index.php')) ;
