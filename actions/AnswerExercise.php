<?php
require_once "../initTsugi.php";
global $translator, $REST_CLIENT_AUTHOR;
use Symfony\Component\HttpClient\HttpClient;

$currentTime = new DateTime('now', new DateTimeZone($CFG->timezone));
$exerciseId = $_POST["exerciseId"];
$answerText = $_POST["answerText"];
$exerciseNum = $_POST["exerciseNum"];

// In databases doesn't exists answer_language, so we use -1
$answerLanguage = $_POST["answer_language"] ?? -1;

$result = array();

//if the answer is blank
if (!isset($answerText) || trim($answerText) == "") {
    $_SESSION['error'] = $translator->trans('backend-messages.answer.exercise.failed');
    $result["answer_content"] = false;
} else {
    //Search for the exercise on the db and map
    $exercise = \CT\CT_Exercise::withId($exerciseId);
    $main = $exercise->getMain();
    if ($main->getType() == '1') {
        $exercise1 = new \CT\CT_ExerciseCode($exercise->getExerciseId());
    } else {
        $exercise1 = \CT\CT_ExerciseSQL::withId($exercise->getExerciseId());
    }

    $answerOutput = null;
    
    if($answerLanguage == 0) {
        $client = HttpClient::create();
    
        $response = $client->request("POST", "{$CFG->apiConfigs['xml-validator']['baseUrl']}eval", [
            'json' => [
                'date' => date("c"),
                'program' => $answerText,
                'learningObject' => $exercise1->getAkId() //$exerciseId
            ]
        ]);
        $answerOutput = $response->getContent();
    }

    $array = $exercise1->createAnswer($USER->id, $answerText, $answerLanguage, $answerOutput);
    $answer = $array['answer'];

    $result["answer_content"] = true;
    $result['exists'] = $array['exists'];
    $result['success'] = $answer->getAnswerSuccess();

    $result['answerText'] = $answer->getAnswerTxt();

    // Notify elearning that there is a new answer
    // the message
    $msg = "A new code test was submitted on Learn by " . $USER->displayname . " (" . $USER->email . ").\n
    Exercise: " . $exercise->getTitle() . "\n
    Answer: " . $answer->getAnswerTxt();

    // use wordwrap() if lines are longer than 70 characters
    $msg = wordwrap($msg, 70);

    $headers = "From: LEARN < @gmail.com >\n";

    $_SESSION['success'] = $translator->trans('backend-messages.answer.exercise.saved');

    // var_dump($answerOutput);die;
    // echo json_encode(json_decode($answerOutput), JSON_PRETTY_PRINT);die;
    echo $answerOutput;die;
}

$OUTPUT->buffer = true;
$result["flashmessage"] = $OUTPUT->flashMessages();

header('Content-Type: application/json');

echo json_encode($result, JSON_HEX_QUOT | JSON_HEX_TAG);

exit;

