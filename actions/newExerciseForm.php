<?php

require_once('../initTsugi.php');

if ($USER->instructor) {

    $language = $_GET['language'];

    $main = new \CT\CT_Main($_SESSION["ct_id"]);
    echo $twig->render('exercise/newExerciseForm.php.twig', array(
        'main' => $main,
        'type' => $language,
        'CFG' => $CFG_CT,
    ));
} else {
    header('Location: ' . addSession('../student-home.php'));
}

function in_array_r($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}
