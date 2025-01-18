<?php

require_once('../../initTsugi.php');

if ($USER->instructor) {
    $main = new \CT\CT_Main($_SESSION["ct_id"]);
    $exercises = $main->getExercisesForImport();
    $newExercise = new CT\CT_Exercise();

    echo $twig->render('exercise/newExerciseForm.php.twig', array(
        'main' => $main,
        'newExercise' => $newExercise,
        'newExerciseNumber' => $exercises ? count($exercises) + 1 : 1,
        'CFG' => $CFG_CT,
    ));
} else {
    header('Location: ' . addSession('../student-home.php'));
}

