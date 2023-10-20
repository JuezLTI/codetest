<?php
require_once "../initTsugi.php";
global $translator;

if ($USER->instructor) {
    $result = array();
    $exercisePost = $_POST["exercise"];
    $libraries = array();
    $counter = 0;
    $temporalFile = array();

    do {
        if (isset($_POST["radiosel_" . $counter])) {

            switch ($_POST["radiosel_" . $counter]) {
                case "library_text":

                    $textBody = trim($_POST["library_textbody_" . $counter], ' ');
                    $textName = trim($_POST["library_texttitle_" . $counter], ' ');

                    if (!empty($textBody) && !empty($textName)) {
                        $temporalFile[$counter] = tmpfile();
                        fwrite($temporalFile[$counter], $_POST["library_textbody_" . $counter]);
                        fseek($temporalFile[$counter], 0);
                        $metaData = stream_get_meta_data($temporalFile[$counter]);
                        $filepath = $metaData['uri'];

                        $data = $array = [
                            "name" => $_POST["library_texttitle_" . $counter],
                            "path" => $filepath,
                        ];

                        array_push($libraries, $data);
                    }
                    break;

                case "library_file":

                    if (isset($_FILES["library_file_" . $counter]["name"])) {
                        $data = $array = [
                            "name" => $_FILES["library_file_" . $counter]["name"],
                            "path" => $_FILES["library_file_" . $counter]["tmp_name"],
                        ];

                        array_push($libraries, $data);
                    }
                    break;
            }
        }
        $counter++;

    } while (isset($_POST["radiosel_" . $counter]));

    if (isset($exercisePost['title']) && trim($exercisePost['title']) != '' && isset($exercisePost['exercise_language']) && trim($exercisePost['exercise_language']) != '') {
        $exercisePost['author'] = $_SESSION["lti"]["user_displayname"];
        $exercisePost['owner'] = $_SESSION["lti"]["link_title"];
        $exercisePost['sessionLanguage'] = isset($_SESSION["lti"]["user_locale"]) ? $_SESSION["lti"]["user_locale"] : "en";
        $main = new \CT\CT_Main($_SESSION["ct_id"]);

        // Change to Boolean Values
        $exercisePost['visibleTest'] = array_map(function($value) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
          }, $exercisePost['visibleTest']);

        $exercise = $main->createExercise($exercisePost, strtolower($exercisePost['exercise_language']), $exercisePost["difficulty"], $libraries, $exercisePost['visibleTest']);
        $exercises = array();

        array_push($exercises, $exercise);

        //save the exercise on the repository
        $main->saveExercises($exercises);


        foreach ($temporalFile as $key => $value) {
            fclose($value);
        }

        $_SESSION['success'] = $translator->trans('backend-messages.add.exercise.success');
        header('Location: ' . addSession('../exercises-list.php'));
    } else {

        $_SESSION['error'] = $translator->trans('backend-messages.add.exercise.failed');
        header('Location: ' . addSession('../create-exercise.php'));
    }
    $OUTPUT->buffer = true;

    exit;
} else {
    header('Location: ' . addSession('../student-home.php'));
}