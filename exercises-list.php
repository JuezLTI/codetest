<?php

use CT\CT_ExerciseCode;
use CT\CT_ExerciseSQL;

require_once('initTsugi.php');
include('views/dao/menu.php');

$main = new \CT\CT_Main($_SESSION["ct_id"]);
$exercises = $main->getExercises();
$typedExercises = array();

foreach($exercises as $el){
    array_push($typedExercises, new CT_ExerciseCode($el->getExerciseId()));
}

echo $twig->render('pages/exercises-list.php.twig', array(
    'exercises' => $typedExercises,
    'OUTPUT' => $OUTPUT,
    'CFG' => $CFG_CT,
    'menu' => $menu,
    'help' => $help(),
));
