<?php
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Filesystem\Filesystem;

require_once('initAuthorkit.php');

function getFilenameFromDisposition($value) {
    $value = trim($value);

    if (strpos($value, ';') === false) {
        return null;
    }

    list($type, $attr_parts) = explode(';', $value, 2);

    $attr_parts = explode(';', $attr_parts);
    $attributes = array();

    foreach ($attr_parts as $part) {
        if (strpos($part, '=') === false) {
            continue;
        }

        list($key, $value) = explode('=', $part, 2);

        $attributes[trim($key)] = trim($value);
    }

    $attrNames = ['filename*' => true, 'filename' => false];
    $filename = null;
    $isUtf8 = false;
    foreach ($attrNames as $attrName => $utf8) {
        if (!empty($attributes[$attrName])) {
            $filename = trim($attributes[$attrName]);
            $isUtf8 = $utf8;
            break;
        }
    }
    if ($filename === null) {
        return null;
    }

    if ($isUtf8 && strpos($filename, "utf-8''") === 0 && $filename = substr($filename, strlen("utf-8''"))) {
        return rawurldecode($filename);
    }
    if (substr($filename, 0, 1) === '"' && substr($filename, -1, 1) === '"') {
        $filename = substr($filename, 1, -1);
    }

    return $filename;
}

function downloadAkExercise($exerciseId) {

    global $REST_CLIENT_AUTHOR, $REST_CLIENT_REPO, $TSUGI_LOCALE;

    try {
        $exerciseFileResponse = $REST_CLIENT_AUTHOR->getClient()->request('GET', "exercises/$exerciseId/export?format=zip", [
            'buffer' => false,
        ]);
    } catch (Exception $e) {
        throw $e;
    }

    if (200 !== $exerciseFileResponse->getStatusCode()) {
        throw new \Exception('Request to AK failed');
    }
    $headers = $exerciseFileResponse->getHeaders();
    $filename = getFilenameFromDisposition($headers['content-disposition'][0]);

    $fileHandler = fopen($filename, 'w');
    foreach ($REST_CLIENT_AUTHOR->getClient()->stream($exerciseFileResponse) as $chunk) {
        fwrite($fileHandler, $chunk->getContent());
    }
    fclose($fileHandler);

    return putExerciseOnRepo($filename);
}

function putExerciseOnRepo($filename) {
    global $REST_CLIENT_REPO, $TSUGI_LOCALE;

    $formFields = [
        'PHPSESSID' => session_id(),
        'exercise' => DataPart::fromPath($filename),
        'sessionLanguage' =>$TSUGI_LOCALE
    ];
    $formData = new FormDataPart($formFields);

    $uploadResponse = $REST_CLIENT_REPO->getClient()->request('POST', 'api/exercises/import-file', [
        'headers' => $formData->getPreparedHeaders()->toArray(),
        'body' => $formData->bodyToIterable(),

    ]);

    $uploadResponseCode = $uploadResponse->getStatusCode();
    $uploadResponseBody = $uploadResponse->getContent();

    unlink($filename);
    return $uploadResponseBody;
}

function createZipFileForExportContext($main) {

    global $REST_CLIENT_REPO, $CONTEXT;

    $tempFolder = 'tmp';
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
    return $zipFinalFilename;
}

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

function importContextFromZipFile($main, $zipFileName) {
    $filesystem = new Filesystem();
    $tempFolder = '/tmp';
    $zip = new ZipArchive;
    $zipExercise = new ZipArchive;

    if ($zip->open($zipFileName) !== TRUE) {
        exit("Invalid zip");
    }

    $mainContent = $zip->getFromName('main.json');
    $mainContentArr = json_decode($mainContent, true);
    $exercisesContent = $zip->getFromName('exercises/exercises.json');
    $exercisesContentArr = json_decode($exercisesContent, true);


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
}
