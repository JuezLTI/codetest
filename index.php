<?php
require_once('initTsugi.php');
require_once('util/Functions.php');

$currentTime = new DateTime('now', new DateTimeZone($CFG_CT->timezone));
$currentTime = $currentTime->format("Y-m-d H:i:s");

$main = \CT\CT_Main::getMainFromContext($CONTEXT->id, $LINK->id, $USER->id, $currentTime);
$_SESSION["ct_id"] = $main->getCtId();
$exercises = $main->getExercises();

// If lti_link is a copy, we will try to import questions.
// TODO dealing with more than one link.id.history.
$post = $_SESSION['lti_post'];
if(array_key_exists('custom_link_id_history',$post)
    && ($linkOriginal = \CT\CT_Link::withLinkKey($post['custom_link_id_history']))
    && count($exercises) == 0
) {
    $linkCopy = new \CT\CT_Link($LINK->id);
    $mainOriginal = $linkOriginal->getCtMain();

    if($mainOriginal && !$main->getSeenSplash()) {
        $linkCopy->import($mainOriginal, $main);
        $main->setUserId($mainOriginal->getUserId());
        $main->save();
        $main = \CT\CT_Main::getMainFromContext($CONTEXT->id, $LINK->id, $USER->id, $currentTime);
        $_SESSION["ct_id"] = $main->getCtId();
        $exercises = $main->getExercises();
    }
}

if ( $USER->instructor ) {

    if ($main->getSeenSplash()) {
        // Instructor has already setup this instance
        header( 'Location: '.addSession('instructor-home.php') ) ;
    } else {
        header('Location: '.addSession('splash.php'));
    }
} else { // student
    if (!$exercises || count($exercises) == 0) {
        header('Location: '.addSession('splash.php'));
    } else {
        header( 'Location: '.addSession('student-home.php')) ;
    }
}
