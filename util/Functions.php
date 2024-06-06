<?php
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

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

    $exerciseFileResponse = $REST_CLIENT_AUTHOR->getClient()->request('GET', "exercises/$exerciseId/export?format=zip", [
        'buffer' => false,
    ]);

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
