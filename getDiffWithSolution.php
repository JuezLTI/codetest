<?php
require_once "./initTsugi.php";

if (!$USER->instructor) {
    header('Location: ' . addSession('./student-home.php'));
    exit;
}

$answer = new \CT\CT_Answer($_GET['answerId']);

if ($answer) {
    echo $twig->render('answer/showTestDiffsFromAnswer.php.twig', array(
        'OUTPUT' => $OUTPUT,
        'CONTEXT' => $CONTEXT,
        'help' => $help(),
        'menu' => '',
        'answer' => $answer,
		'tests' => $answer->getTestsOutput(),
    ));
} else {
    echo $twig->render('answer/noAnswers.php.twig', array(
    ));
}
