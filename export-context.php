<?php
require_once('initTsugi.php');
include('views/dao/menu.php'); // for -> $menu
global $REST_CLIENT_REPO;

$tempFolder = 'tmp';
$main = new \CT\CT_Main($_SESSION["ct_id"]);
$exercises = $main->getExercises();

// Get exercise subtypes - > > >
$exercise_ids = array_map(function($el){
    return "{$el->getExerciseId()}";
}, $exercises);
if(empty($exercise_ids)){
    echo 'ERROR: No exercises';
    die;
}

$exercisesMetaRequest = $REST_CLIENT_REPO->getClient()->request('POST','api/exercises/getAllExercises', [
    'body' => [
        'exerciseIds' => join(",", $exercise_ids)
    ]
]);

$exercisesMeta = $exercisesMetaRequest->toArray();

$exercisesMetaMap = array_reduce($exercisesMeta,function($acc, $el){
    $acc[$el['id']] = $el;
    return $acc;
},[]);

// Get exercise subtypes - < < <
$clone_main = clone $main;
$clone_main->setUserId(null);
$clone_main->setContextId(null);
$clone_main->setLinkId(null);
$clone_main->setCtId(null);

$toArrayWithMeta = function ($data) use ($exercisesMetaMap){
    $arr = json_decode(json_encode($data), true);
    $resultArr = [];

    foreach($arr as $item){
        $auxObj = $exercisesMetaMap[$item['id']];
        $merge_object = (array) array_merge((array) $item, (array) $auxObj);
        array_push($resultArr, $merge_object);
    }

    $result = json_decode(json_encode($resultArr), true);
    return $result;
};

// Clone exercises
$clone_exercises = array_map(function($el){
    $el->setCtId(null);
    return $el;
} ,$exercises);
$clone_exercises = json_decode(json_encode($clone_exercises), true);

$exercisesMappedWithMeta = $toArrayWithMeta($clone_exercises);
// ---------------------------------------

$mainFilename = "$tempFolder/main.json";
$fileHandler = fopen($mainFilename, 'w');
fwrite($fileHandler, json_encode($clone_main, JSON_PRETTY_PRINT));
fclose($fileHandler);

$exercisesFilename = "$tempFolder/exercises.json";
$fileHandler = fopen($exercisesFilename, 'w');
fwrite($fileHandler, json_encode($exercisesMappedWithMeta, JSON_PRETTY_PRINT));
fclose($fileHandler);

/// -------------------------------------
$timeFormat = new DateTime('now', new DateTimeZone("Europe/Madrid"));
$timeFormat = $timeFormat->format('Ymd_Hi');

$zip = new ZipArchive();
$zipFinalFilename = "$tempFolder/Codetest_export_{$timeFormat}_{$CONTEXT->id}-{$CONTEXT->title}.zip";
$openZipFile = $zip->open($zipFinalFilename, ZipArchive::CREATE);
if(!$openZipFile) {
    exit("cannot open <$zipFinalFilename>\n");
}
$zip->addFile($mainFilename,"main.json");
$zip->addFile($exercisesFilename, "exercises/".basename($exercisesFilename));

$exercises = $main->getExercises();
foreach($exercises as $exercise){
    $compressedExercise = $exercise->findExerciseForExport($exercise->getAkId());
    if($compressedExercise) {
        file_put_contents("$tempFolder/{$exercise->getAkId()}.zip", $compressedExercise);
        $zip->addFile("$tempFolder/{$exercise->getAkId()}.zip", "exercises/{$exercise->getAkId()}.zip");
    }
}
$zip->close();

unlink($mainFilename);
foreach($exercises as $exercise){
    if(!file_exists("$tempFolder/{$exercise->getAkId()}.zip")) {
        continue;
    }
    unlink("$tempFolder/{$exercise->getAkId()}.zip");
}

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


