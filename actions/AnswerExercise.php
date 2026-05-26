<?php
require_once "../initTsugi.php";
require_once('../vendor/erusev/parsedown/Parsedown.php');

global $translator;
use Symfony\Component\HttpClient\HttpClient;

$currentTime = new DateTime('now', new DateTimeZone($CFG->timezone));
$exerciseId = $_POST["exerciseId"];
$answerText = $_POST["answerText"];
$exerciseNum = $_POST["exerciseNum"];
$_SESSION["last_used_language"] = isset($_POST["answer_language"]) ? $_POST["answer_language"] : "";
$user_id = $_SESSION["lti"]["user_id"];
$user = new \CT\CT_User($user_id);
$parsedown = new Parsedown();

// In databases doesn't exists answer_language, so we use -1
$answerLanguage = $_POST["answer_language"] ?? '';

$result = array();

//if the answer is blank
if (!isset($answerText) || trim($answerText) == "") {
    $_SESSION['error'] = $translator->trans('backend-messages.answer.exercise.failed.empty');
    $result["answer_content"] = false;
} else {
    //Search for the exercise on the db and map
    $exercise = \CT\CT_Exercise::withId($exerciseId);
    $main = $exercise->getMain();

    $exercise1 = new \CT\CT_ExerciseCode($exercise->getExerciseId());
    $answerOutput = null;

    try {
        if ($answerLanguage != '') {
            $client = HttpClient::create();

            $response = $client->request("POST", "{$validatorService->getValidatorUrl($answerLanguage)}eval", [
                'json' => [
                    'date' => date("c"),
                    'program' => $answerText,
                    'learningObject' => $exercise1->getAkId(), // $exerciseId,
                    'studentID' => $USER->id,
                    'language' => $answerLanguage
                ]
            ]);
            $responsePearl = $response->toArray();
            $answerOutput = $responsePearl['summary'];
            $testsOutput = $responsePearl['reply']['report']['tests'];
        }

        $array = $exercise1->createAnswer($USER->id, $answerText, $answerLanguage, $answerOutput, $testsOutput);
        $answer = $array['answer'];
        $result["answer_content"] = true;
        $result['exists'] = $array['exists'];
        $result['success'] = $answer->getAnswerSuccess();
        $result['answerOutput'] = $parsedown->text($answerOutput['feedback']);
        $result['studentTestOutputRender'] = $twig->render(
            'exercise/student-solution-output.php.twig',
            array(
                'answer' => $user->getAnswerForExercise($exerciseId, $_SESSION["ct_id"])
            )
        );
        $result['testsOutput'] = $testsOutput;

        // Notify elearning that there is a new answer
        // the message
        $msg = "A new code test was submitted on Learn by " . $USER->displayname . " (" . $USER->email . ").\n
        Exercise: " . $exercise->getTitle() . "\n
        Answer: " . $answer->getAnswerTxt();

        // use wordwrap() if lines are longer than 70 characters
        $msg = wordwrap($msg, 70);

        $headers = "From: LEARN < @gmail.com >\n";

        $_SESSION['success'] = $translator->trans('backend-messages.answer.exercise.saved');

        // echo json_encode(json_decode($answerOutput), JSON_PRETTY_PRINT);die;
        //echo $answerOutput['feedback'];die;
    } catch (Exception $ex) {
        $_SESSION['error'] = $translator->trans('backend-messages.answer.exercise.failed.exception') . $ex->getMessage();
        $result["answer_content"] = false;
        $result["error"] = $ex->getMessage();
        header("HTTP/1.0 500 Internal Server Error");
    }
}

$OUTPUT->buffer = true;
$result["flashmessage"] = $OUTPUT->flashMessages();

header('Content-Type: application/json');

echo json_encode($result, JSON_HEX_QUOT | JSON_HEX_TAG);

exit;
