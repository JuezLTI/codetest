<?php

require_once "../initTsugi.php";
global $translator;

if ($USER->instructor) {

    $result = array();
    if (isset($_POST["toolTitle"])) {
        $currentTime = new DateTime('now', new DateTimeZone($CFG_CT->timezone));
        $main = new \CT\CT_Main($_SESSION["ct_id"]);
        $main->setModified($currentTime->format("Y-m-d H:i:s"));
        $main->setTitle($_POST["toolTitle"]);
        // $main->setType($_POST["mainType"]);
        $main->save();

        $_SESSION['success'] = $translator->trans('backend-messages.title.saved.success');
    } else {
        $_SESSION['error'] = $translator->trans('backend-messages.title.saved.failed');
    }

    $OUTPUT->buffer = true;
    $result["flashmessage"] = $OUTPUT->flashMessages();

    header('Content-Type: application/json');

    echo json_encode($result, JSON_HEX_QUOT | JSON_HEX_TAG);

    exit;
} else {
    header('Location: ' . addSession('../student-home.php'));
}

