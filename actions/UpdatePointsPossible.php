<?php

require_once "../initTsugi.php";
global $translator;

if ($USER->instructor) {
    $result = array();

    if (isset($_POST["points_possible"]) && is_numeric($_POST["points_possible"])) {
        $currentTime = new DateTime('now', new DateTimeZone($CFG_CT->timezone));
        $main = new \CT\CT_Main($_SESSION["ct_id"]);
        $main->setModified($currentTime->format("Y-m-d H:i:s"));
        $main->setPoints($_POST["points_possible"]);
        $main->save();

        $_SESSION['success'] = $translator->trans('backend-messages.points.posible.updated');
    } else {
        $_SESSION['error'] = $translator->trans('backend-messages.points.posible.failed');
    }

    header('Location: ' . addSession('../grade.php'));
} else {
    header('Location: ' . addSession('../student-home.php'));
}

