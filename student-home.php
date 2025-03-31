<?php
require_once('initTsugi.php');
include('views/dao/menu.php');
include('util/Functions.php');

require_once('vendor/erusev/parsedown/Parsedown.php');

$SetID = $_SESSION["ct_id"];
$main = new \CT\CT_Main($_SESSION["ct_id"]);
$toolTitle = $main->getTitle() ? $main->getTitle() : "Code Test";
$exercises = $main->getExercises();
$totalExercises = count($exercises);
$currentExerciseNumber = isset($_GET['exerciseNum']) ? $_GET['exerciseNum'] : 1;
$student_language = $_SESSION["lti"]["user_locale"];
$user_id = $_SESSION["lti"]["user_id"];
$user = new \CT\CT_User($user_id);

$Parsedown = new Parsedown();

if ($totalExercises > 0) {
    $firstExerciseAkId = $exercises[$currentExerciseNumber - 1]->getAkId();
    $firstExerciseId = $exercises[$currentExerciseNumber - 1]->getExerciseId();
    $isAK_exercise = !$exercises[$currentExerciseNumber - 1]->getCodeExercise();

    if ( $USER->instructor && $isAK_exercise) {
        try {
            $renewed = downloadAkExercise($firstExerciseAkId);
        } catch (Exception $e) {
            error_log(print_r($e, true));
        }
    }
    $exerciseTestsResponse = $REST_CLIENT_REPO->getClient()->request('GET', "api/exercises/$firstExerciseAkId/tests");
    $exerciseStatementsResponse = $REST_CLIENT_REPO->getClient()->request('GET', "api/exercises/$firstExerciseAkId/statements/$student_language");
    $statements_list = $exerciseStatementsResponse->toArray();
    $testsList = $exerciseTestsResponse->toArray();

    if (count(array_filter($statements_list, 'is_null')) == count($statements_list)) {
        $exerciseStatementsResponse = $REST_CLIENT_REPO->getClient()->request('GET', "api/exercises/$firstExerciseAkId/statements/en");
        $statements_list = $exerciseStatementsResponse->toArray();
    }

    if (count(array_filter($statements_list, 'is_null')) == count($statements_list)) {
        $exerciseStatementsResponse = $REST_CLIENT_REPO->getClient()->request('GET', "api/exercises/$firstExerciseAkId/statements");
        $statements_list = $exerciseStatementsResponse->toArray();
    }
    foreach ($statements_list as $statement) {
        if ($statement) {
            $statement_value = $statement['statementValue'];
        }
    }

    $code_languages = $validatorService->getCodeLanguages();
    $last_used_language = isset($_SESSION["last_used_language"]) ? $_SESSION["last_used_language"] : "";

    foreach ($code_languages as $indice => $language) {
        if ($last_used_language == $language) {
            unset($code_languages[$indice]);
            array_unshift($code_languages, $last_used_language);
        }
    }
}

echo $twig->render('pages/student-view.php.twig', array(
    'OUTPUT' => $OUTPUT,
    'help' => $help(),
    'menu' => $menu,
    'user' => $user,
    // Return true when have a correct usage if not returns false
    // 'correctUsage' => $firstExerciseId ? $user->getHaveCorrectUsage($firstExerciseId, $user_id) : false,
    // TODO When we want ask the students about exercises, uncomment the previous line and delete the next
    'correctUsage' => true,
    // All codelanguages but ordened by last used language
    'codeLanguagesOrdened' => isset($code_languages) ? $code_languages : null,
    'exercises' => $exercises,
    'statementValue' => isset($statement_value) ? $statement_value : null,
    'testsList' => isset($testsList) ? $testsList : null,
    'totalExercises' => $totalExercises,
    'currentExerciseNumber' => $currentExerciseNumber,
    'exerciseNum' => $currentExerciseNumber,
    'main' => $main,
    'validatorService' => $validatorService,
    'CFG' => $CFG_CT,
    'mdConverter' => $Parsedown
)
);
