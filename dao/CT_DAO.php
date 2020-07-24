<?php
namespace CT\DAO;

class CT_DAO {

    private $PDOX;
    private $p;

    public function __construct($PDOX, $p) {
        $this->PDOX = $PDOX;
        $this->p = $p;
    }

    function getOrCreateMain($user_id, $context_id, $link_id, $current_time) {
        $main_id = $this->getMainID($context_id, $link_id);
        if (!$main_id) {
            return $this->createMain($user_id, $context_id, $link_id, $current_time);
        } else {
            return $main_id;
        }
    }

    function getMainID($context_id, $link_id) {
        $query = "SELECT ct_id FROM {$this->p}ct_main WHERE context_id = :context_id AND link_id = :link_id";
        $arr = array(':context_id' => $context_id, ':link_id' => $link_id);
        $context = $this->PDOX->rowDie($query, $arr);
        return $context["ct_id"];
    }

    function createMain($user_id, $context_id, $link_id, $current_time) {
        $query = "INSERT INTO {$this->p}ct_main (user_id, context_id, link_id, modified) VALUES (:userId, :contextId, :linkId, :currentTime);";
        $arr = array(':userId' => $user_id, ':contextId' => $context_id, ':linkId' => $link_id, ':currentTime' => $current_time);
        $this->PDOX->queryDie($query, $arr);
        return $this->PDOX->lastInsertId();
    }

    function findQuestionsForImport($user_id, $ct_id) {
        $query = "SELECT q.*, m.title as tooltitle, c.title as sitetitle FROM {$this->p}ct_question q join {$this->p}ct_main m on q.ct_id = m.ct_id join {$this->p}lti_context c on m.context_id = c.context_id WHERE m.user_id = :userId AND m.ct_id != :ct_id";
        $arr = array(':userId' => $user_id, ":ct_id" => $ct_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function hasSeenSplash($ct_id) {
        $query = "SELECT seen_splash FROM {$this->p}ct_main WHERE ct_id = :ctId";
        $arr = array(':ctId' => $ct_id);
        return $this->PDOX->rowDie($query, $arr)["seen_splash"];
    }

    function markAsSeen($ct_id) {
        $query = "UPDATE {$this->p}ct_main set seen_splash = 1 WHERE ct_id = :ctId;";
        $arr = array(':ctId' => $ct_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function getMainTitle($ct_id) {
        $query = "SELECT title FROM {$this->p}ct_main WHERE ct_id = :ctId";
        $arr = array(':ctId' => $ct_id);
        return $this->PDOX->rowDie($query, $arr)["title"];
    }

    function getPointsPossible($ct_id) {
        $query = "SELECT points FROM {$this->p}ct_main WHERE ct_id = :ctId";
        $arr = array(':ctId' => $ct_id);
        return $this->PDOX->rowDie($query, $arr)["points"];
    }

    function updateMainTitle($ct_id, $title, $current_time) {
        $query = "UPDATE {$this->p}ct_main set title = :title, modified = :currentTime WHERE ct_id = :ctId;";
        $arr = array(':title' => $title, ':currentTime' => $current_time, ':ctId' => $ct_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function updatePointsPossible($ct_id, $points, $current_time) {
        $query = "UPDATE {$this->p}ct_main set points = :points, modified = :currentTime WHERE ct_id = :ctId;";
        $arr = array(':points' => $points, ':currentTime' => $current_time, ':ctId' => $ct_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function deleteMain($ct_id, $user_id) {
        $query = "DELETE FROM {$this->p}ct_main WHERE ct_id = :mainId AND user_id = :userId";
        $arr = array(':mainId' => $ct_id, ':userId' => $user_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function getQuestions($ct_id) {
        $query = "SELECT * FROM {$this->p}ct_question WHERE ct_id = :ctId order by question_num;";
        $arr = array(':ctId' => $ct_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function getQuestionById($question_id) {
        $query = "SELECT * FROM {$this->p}ct_question WHERE question_id = :questionId;";
        $arr = array(':questionId' => $question_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function createQuestion($ct_id, $question_text, $current_time) {
        $nextNumber = $this->getNextQuestionNumber($ct_id);
        $query = "INSERT INTO {$this->p}ct_question (ct_id, question_num, question_txt, modified) VALUES (:ctId, :questionNum, :questionText, :currentTime);";
        $arr = array(':ctId' => $ct_id, ':questionNum' => $nextNumber, ':questionText' => $question_text, ':currentTime' => $current_time);
        $this->PDOX->queryDie($query, $arr);
        return $this->PDOX->lastInsertId();
    }

    function updateQuestion($question_id, $question_text, $current_time) {
        $query = "UPDATE {$this->p}ct_question set question_txt = :questionText, modified = :currentTime WHERE question_id = :questionId;";
        $arr = array(':questionId' => $question_id, ':questionText' => $question_text, ':currentTime' => $current_time);
        $this->PDOX->queryDie($query, $arr);
    }

    function getNextQuestionNumber($ct_id) {
        $query = "SELECT MAX(question_num) as lastNum FROM {$this->p}ct_question WHERE ct_id = :ctId";
        $arr = array(':ctId' => $ct_id);
        $lastNum = $this->PDOX->rowDie($query, $arr)["lastNum"];
        return $lastNum + 1;
    }

    function countAnswersForQuestion($question_id) {
        $query = "SELECT COUNT(*) as total FROM {$this->p}ct_answer WHERE question_id = :questionId;";
        $arr = array(':questionId' => $question_id);
        return $this->PDOX->rowDie($query, $arr)["total"];
    }

    function deleteQuestion($question_id) {
        $query = "DELETE FROM {$this->p}ct_question WHERE question_id = :questionId;";
        $arr = array(':questionId' => $question_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function fixUpQuestionNumbers($ct_id) {
        $query = "SET @question_num = 0; UPDATE {$this->p}ct_question set question_num = (@question_num:=@question_num+1) WHERE ct_id = :ctId ORDER BY question_num";
        $arr = array(':ctId' => $ct_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function updateQuestionNumber($question_id, $new_number) {
        $query = "UPDATE {$this->p}ct_question set question_num = :questionNumber WHERE question_id = :questionId;";
        $arr = array(':questionId' => $question_id, ':questionNumber' => $new_number);
        $this->PDOX->queryDie($query, $arr);
    }

    function getUsersWithAnswers($ct_id) {
        $query = "SELECT DISTINCT user_id FROM {$this->p}ct_answer a join {$this->p}ct_question q on a.question_id = q.question_id WHERE q.ct_id = :ctId;";
        $arr = array(':ctId' => $ct_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function getStudentAnswerForQuestion($question_id, $user_id) {
        $query = "SELECT * FROM {$this->p}ct_answer WHERE question_id = :questionId AND user_id = :userId; ";
        $arr = array(':questionId' => $question_id, ':userId' => $user_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function getMostRecentAnswerDate($user_id, $ct_id) {
        $query = "SELECT max(a.modified) as modified FROM {$this->p}ct_answer a join {$this->p}ct_question q on a.question_id = q.question_id WHERE a.user_id = :userId AND q.ct_id = :ctId;";
        $arr = array(':userId' => $user_id, ':ctId' => $ct_id);
        $context = $this->PDOX->rowDie($query, $arr);
        return $context['modified'];
    }

    function getNumberQuestionsAnswered($user_id, $ct_id) {
        $query = "SELECT count(*) as num_answered FROM {$this->p}ct_answer a join {$this->p}ct_question q on a.question_id = q.question_id WHERE a.user_id = :userId AND q.ct_id = :ctId AND a.answer_txt is not null;";
        $arr = array(':userId' => $user_id, ':ctId' => $ct_id);
        $context = $this->PDOX->rowDie($query, $arr);
        return $context['num_answered'];
    }

    function createAnswer($user_id, $question_id, $answer_txt, $current_time) {
        $query = "INSERT INTO {$this->p}ct_answer (user_id, question_id, answer_txt, modified) VALUES (:userId, :questionId, :answerTxt, :currentTime);";
        $arr = array(':userId' => $user_id,':questionId' => $question_id, ':answerTxt' => $answer_txt, ':currentTime' => $current_time);
        $this->PDOX->queryDie($query, $arr);
        return $this->PDOX->lastInsertId();
    }

    function updateAnswer($answer_id, $answer_txt, $current_time) {
        $query = "UPDATE {$this->p}ct_answer set answer_txt = :answerTxt, modified = :currentTime where answer_id = :answerId;";
        $arr = array(':answerId' => $answer_id, ':answerTxt' => $answer_txt, ':currentTime' => $current_time);
        $this->PDOX->queryDie($query, $arr);
    }

    function deleteAnswers($questions, $user_id) {
        $questionIds = array();
        foreach($questions as $question) {
            array_push($questionIds, $question["question_id"]);
        }
        $query = "DELETE FROM {$this->p}ct_answer WHERE user_id = :userId AND question_id in (".implode(',', array_map('intval', $questionIds)).");";
        $arr = array(':userId' => $user_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function getAllAnswersToQuestion($question_id) {
        $query = "SELECT * FROM {$this->p}ct_answer WHERE question_id = :questionId;";
        $arr = array(':questionId' => $question_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function getAnswerById($answer_id) {
        $query = "SELECT * FROM {$this->p}ct_answer WHERE answer_id = :answerId;";
        $arr = array(':answerId' => $answer_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function getStudentGrade($ct_id, $user_id) {
        $query = "SELECT grade FROM {$this->p}ct_grade WHERE ct_id = :ct_id AND user_id = :user_id";
        $arr = array(':ct_id' => $ct_id, ':user_id' => $user_id);
        $context = $this->PDOX->rowDie($query, $arr);
        return $context["grade"];
    }

    function createGrade($ct_id, $user_id, $grade, $current_time) {
        $query = "INSERT INTO {$this->p}ct_grade (ct_id, user_id, grade, modified) VALUES (:ct_id, :user_id, :grade, :currentTime);";
        $arr = array(':ct_id' => $ct_id,':user_id' => $user_id, ':grade' => $grade, ':currentTime' => $current_time);
        $this->PDOX->queryDie($query, $arr);
        return $this->PDOX->lastInsertId();
    }

    function updateGrade($ct_id, $user_id, $grade, $current_time) {
        $query = "UPDATE {$this->p}ct_grade set grade = :grade, modified = :currentTime where user_id = :user_id AND ct_id = :ct_id;";
        $arr = array(':grade' => $grade, ':currentTime' => $current_time, ':user_id' => $user_id, ':ct_id' => $ct_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function findEmail($user_id) {
        $query = "SELECT email FROM {$this->p}lti_user WHERE user_id = :user_id;";
        $arr = array(':user_id' => $user_id);
        $context = $this->PDOX->rowDie($query, $arr);
        return $context["email"];
    }

    function findDisplayName($user_id) {
        $query = "SELECT displayname FROM {$this->p}lti_user WHERE user_id = :user_id;";
        $arr = array(':user_id' => $user_id);
        $context = $this->PDOX->rowDie($query, $arr);
        return $context["displayname"];
    }

    function findInstructors($context_id) {
        $query = "SELECT user_id FROM {$this->p}lti_membership WHERE context_id = :context_id AND role = '1000';";
        $arr = array(':context_id' => $context_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function isUserInstructor($context_id, $user_id) {
        $query = "SELECT role FROM {$this->p}lti_membership WHERE context_id = :context_id AND user_id = :user_id;";
        $arr = array(':context_id' => $context_id, ':user_id' => $user_id);
        $role = $this->PDOX->rowDie($query, $arr);
        return $role["role"] == '1000';
    }
}