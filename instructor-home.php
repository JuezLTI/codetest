<?php
require_once('initTsugi.php');

include('views/dao/menu.php');

$main = new \CT\CT_Main($_SESSION["ct_id"]);

if (!$main->getTitle()) {
    $main->setTitle("Code Test");
    $main->save();
}

$exercises = $main->getExercises();
$tittleName = $CFG->ExerciseProperty['name'];

// Clear any preview responses if there are exercises
if ($exercises) \CT\CT_Answer::deleteInstructorAnswers($exercises, $CONTEXT->id);

$usageCount = 0;
if($REST_CLIENT_REPO->getIsOnline()){
    try {
        $usagesCountRequest = $REST_CLIENT_REPO->
                                    getClient()->
                                    request('GET','api/usage/usagesCount', [
                                        'query' => [
                                            'ctid' => $main->getCtId()
                                        ]
                                    ]);
        $usageCount = $usagesCountRequest->getContent();
    } catch (Exception $ex) {
        $errorMessage = "Couldn't fetch usages";
        logg($ex->getMessage());
        logg($errorMessage);
        $_SESSION["error"] = $errorMessage;
    }
}

$grades = $main->getGradesCtId();
$gradesCount = 0;
try {
    $grades = $main->getGradesCtId();
    $gradesCount = count($grades);
} catch (Exception $ex) {
    $errorMessage = "Couldn't fetch grades";
    logg($ex->getMessage());
    logg($errorMessage);
    $_SESSION["error"] = $errorMessage;
}

$gradesMap = array_reduce($grades,function($acc, $el){
    $acc['min'] = min(
            $el->getGrade(),
            array_key_exists("min", $acc) ? $acc['min'] : $el->getGrade()
    );
    $acc['max'] = max(
        $el->getGrade(),
        array_key_exists("max", $acc) ? $acc['max'] : $el->getGrade()
    );
    $acc['avg'] = (array_key_exists("avg", $acc) ? $acc['avg'] : 0) + $el->getGrade();
    return $acc;
},[]);

if(array_key_exists('avg',$gradesMap)){
    $gradesMap['avg'] = $gradesMap['avg'] / $gradesCount;
}

require_once($CFG->codetestBasePath."/util/preloadExercises.php");
echo $twig->render('pages/instructor-home.php.twig', array(
    'main' => $main,
    'link_id_history' => $_SESSION['lti_post']['resource_link_id'],
    'tittleName' => $tittleName,
    'exercises' => $exercises,
    'usagesCount' => $usageCount,
    'gradesCount' => $gradesCount,
    'gradesMap' => $gradesMap,
    'OUTPUT' => $OUTPUT,
    'CFG' => $CFG,
    'menu' => $menu,
    'help' => $help(),
));

