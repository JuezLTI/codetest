<?php
namespace CT;

class CT_DAO {

    private $PDOX;

    public function __construct() {
        global $PDOX;
        global $CFG;
        $this->PDOX = $PDOX;
        $this->p = $CFG->dbprefix;
    }

    public static function getConnection() {
        global $PDOX;
        global $CFG;
        return array('PDOX' => $PDOX, 'p' => $CFG->dbprefix);
    }

    public static function setObjectPropertiesFromArray(&$object, $arrayProperties) {
        if(is_array($arrayProperties)){
            foreach($arrayProperties as $k => $v) {
                if($k== 'keywords'){
                    if(!is_array($v)){
                        $v = preg_split('/\r\n|\r|\n/', $v);
                    }else{
                        $v = $v;
                    }
                }
                $function = array($object, 'set'.preg_replace('/[^\da-z]/i', '', mb_convert_case($k, MB_CASE_TITLE)));
                if($v=='')$v=null;
                if(is_callable($function)) call_user_func_array($function, array($v));
                
            }
        }
    }

    public static function setObjectPropertiesToArray($object) {
        $objArray = array();
        $obj = new \ReflectionObject($object);
        foreach ($obj->getProperties() as $property) {
            $method = new \ReflectionMethod($property->class, 'get'.preg_replace('/[^\da-z]/i', '', mb_convert_case($property->name, MB_CASE_TITLE)));
            $objArray[$property->name] = $method->invoke($object);
        }
        return $objArray;
    }

    public static function createObjectFromArray($class, $array) {
        $arrayObject = array();
        foreach($array as $element) {

                $object = new $class();
                 self::setObjectPropertiesFromArray($object, $element); 
         
            array_push($arrayObject, $object);
        } 
       
        return $arrayObject;
    }
    
   

    public static function getQuery($class, $name)
    {
        $connection = self::getConnection();
        $MainQueries = array(
            'getByCtId' => "SELECT * FROM {$connection['p']}ct_main WHERE ct_id = :ct_id",
            'getMain' => "SELECT ct_id FROM {$connection['p']}ct_main "
                . "WHERE context_id = :context_id AND link_id = :link_id",
            'getQuestions' => "SELECT * FROM {$connection['p']}ct_question "
                . "WHERE ct_id = :ct_id "
                . "order by question_num",
            'getMainsFromContext' => "SELECT main.* FROM {$connection['p']}ct_main main "
                . "JOIN {$connection['p']}lti_context lti USING (context_id) "
                . "WHERE context_key = :context_id "
                . "order by main.modified desc",
            'getResultUser' => "SELECT * FROM {$connection['p']}lti_result "
                . "WHERE user_id = :user_id AND link_id = :link_id",
            'getAnswersByUser' => "SELECT DISTINCT {$connection['p']}a.* "
                . "FROM {$connection['p']}ct_answer a join {$connection['p']}ct_question q USING (question_id) "
                . "WHERE q.ct_id = :ctId AND a.user_id = :userId;",
            'insert' => "INSERT INTO {$connection['p']}ct_main (user_id, context_id, link_id, modified) "
                . "VALUES (:userId, :contextId, :linkId, :currentTime)",
            'update' => "UPDATE {$connection['p']}ct_main set "
                . "`user_id` = :user_id, "
                . "`context_id` = :context_id, "
                . "`link_id` = :link_id, "
                . "`title` = :title, "
                . "`type` = :type, "
                . "`seen_splash` = :seen_splash, "
                . "`shuffle` = :shuffle, "
                . "`points` = :points, "
                . "`modified` = :modified "
                . "WHERE ct_id = :ctId",
            'delete' => "DELETE FROM {$connection['p']}ct_main WHERE ct_id = :mainId AND user_id = :userId",
            'codeQuestionsExport' => "SELECT * FROM {$connection['p']}ct_code_question WHERE `question_id` IN (:questions_in )",
            'sqlQuestionsExport' => "SELECT * FROM {$connection['p']}ct_sql_question WHERE `question_id` IN ( :questions_in )",
        );
        $QuestionQueries = array(
            'insert' => "INSERT INTO {$connection['p']}ct_question  "
                . "( `question_id`, `ct_id`, `question_num` , `type`, `title`, `question_must`, `question_musnt` ) "
                . "VALUES (  :question_id, :ct_id, :question_num, :type, :title, :question_must, :question_musnt)",
            'update' => "UPDATE {$connection['p']}ct_question set "
                . "`ct_id` = :ct_id, "
                . "`question_num` = :question_num, "
                . "`question_id` = :question_id "
                . "WHERE question_id = :question_id AND ct_id = :ct_id",
            'updateNum' => "UPDATE {$connection['p']}ct_question set "
                . "`ct_id` = :ct_id, "
                . "`question_num` = :question_num, "
                . "`question_id` = :question_id, "
                . "WHERE question_id = :question_id AND ct_id = :ct_id",
                    
            'delete' => "DELETE FROM {$connection['p']}ct_question WHERE `question_id` = :question_id AND `ct_id` = :ct_id;",
            'exists' => "SELECT question_id as questionId FROM {$connection['p']}ct_question WHERE "
            . "question_id = :question_id AND ct_id = :ct_id;",
            'getAnswers' => "SELECT * FROM {$connection['p']}ct_answer "
                . "WHERE question_id = :questionId AND ct_id = :ctId",
            'fixUpQuestionNumbers' => "SET @question_num = 0; UPDATE {$connection['p']}ct_question "
                . "set question_num = (@question_num:=@question_num+1) "
                . "WHERE ct_id = :ctId ORDER BY question_num",
            'getNextQuestionNumber' => "SELECT MAX(question_num) as lastNum "
                . "FROM {$connection['p']}ct_question "
                . "WHERE ct_id = :ct_id",
            'findQuestionsForImport' => "SELECT q.*, m.title as tooltitle, c.title as sitetitle "
                . "FROM {$connection['p']}ct_question q "
                . "join {$connection['p']}ct_main m on q.ct_id = m.ct_id "
                . "join {$connection['p']}lti_context c on m.context_id = c.context_id "
                . "WHERE m.user_id = :userId AND m.ct_id != :ct_id",
            'getById' => "SELECT * FROM {$connection['p']}ct_question "
                . "WHERE question_id = :question_id AND ct_id = :ct_id",
        );
        $QuestionCodeQueries = array(
            'insert' => "INSERT INTO {$connection['p']}ct_code_question  "
                . "(`question_id`,  `ct_id`, `question_language`, `question_input_test`, `question_input_grade`, `question_output_test`, `question_output_grade`, `question_solution` ) "
                . "VALUES (:question_id, :ct_id, :question_language, :question_input_test, :question_input_grade, :question_output_test, :question_output_grade, :question_solution )",
            'update' => "UPDATE {$connection['p']}ct_code_question set "
                . "`question_language` = :question_language, "
                . " `ct_id` = :ct_id,"
                . "`question_input_test` = :question_input_test, "
                . "`question_input_grade` = :question_input_grade, "
                . "`question_output_test` = :question_output_test, "
                . "`question_output_grade` = :question_output_grade, "
                . "`question_solution` = :question_solution "
                . "WHERE question_id = :question_id",
            'getById' => "SELECT * FROM {$connection['p']}ct_code_question "
                . "WHERE question_id = :question_id",
        );
        $QuestionSQLQueries = array(
            'insert' => "INSERT INTO {$connection['p']}ct_sql_question  "
                . "(`question_id`,  `ct_id`, `question_dbms`, `question_sql_type`, `question_database`, `question_solution`, `question_probe`, `question_onfly` ) "
                . "VALUES (:question_id, :ct_id, :question_dbms, :question_sql_type, :question_database, :question_solution, :question_probe, :question_onfly )",
            'update' => "UPDATE {$connection['p']}ct_sql_question set "
                . "`question_dbms` = :question_dbms, "
                . "`ct_id` = :ct_id,"
                . "`question_sql_type` = :question_sql_type, "
                . "`question_database` = :question_database, "
                . "`question_solution` = :question_solution, "
                . "`question_probe` = :question_probe, "
                . "`question_onfly` = :question_onfly "   
                . "WHERE question_id = :question_id",
            'getById' => "SELECT * FROM {$connection['p']}ct_sql_question "
                . "WHERE question_id = :question_id",
        );
        $AnswerQueries = array(
            'getByAnswerId' => "SELECT * FROM {$connection['p']}ct_answer WHERE answer_id = :answer_id",
            'getByUserQuestion' => "SELECT * FROM {$connection['p']}ct_answer "
                . "WHERE user_id = :userId AND question_id = :questionId AND ct_id = :ctId",
            'insert' => "INSERT INTO {$connection['p']}ct_answer "
                . "(`user_id`, `question_id`, `ct_id`, `answer_txt`, `answer_success`, `modified`, `answer_language`) "
                . "VALUES (:userId, :questionId, :ctId, :answerTxt, :answerSuccess, :modified, :answerLanguage)",
            'update' => "UPDATE {$connection['p']}ct_answer set "
                . "`user_id` = :userId, "
                . "`question_id` = :questionId, "
                . "`ct_id` = :ctId,"
                . "`answer_txt` = :answerTxt, "
                . "`answer_success` = :answerSuccess, "
                . "`modified` = :modified "
                . "`answer_language` = :answerLanguage "
                . "WHERE answer_id = :answer_id",
            'deleteOne' => "DELETE FROM {$connection['p']}ct_answer WHERE answer_id = :answerId;",
            'deleteFromQuestions' => "DELETE FROM {$connection['p']}ct_answer "
                . "WHERE user_id = :userId AND question_id in (/questionsId/)",
        );
        $UserQueries = array(
            'getByUserId' => "SELECT user_id, deleted, profile_id, displayname, email "
                . "FROM {$connection['p']}lti_user WHERE user_id = :user_id",
            'getUserRoles' => "SELECT role FROM {$connection['p']}lti_membership "
                . "WHERE context_id = :context_id AND user_id = :user_id",
            'findInstructors' => "SELECT {$connection['p']}lti_user.* "
                . "FROM {$connection['p']}lti_membership JOIN {$connection['p']}lti_user USING (user_id)"
                . "WHERE context_id = :context_id AND role = :role",
            'getUsersWithAnswers' => "SELECT DISTINCT {$connection['p']}lti_user.* "
                . "FROM {$connection['p']}ct_answer a join {$connection['p']}ct_question q USING (question_id) "
                . "JOIN {$connection['p']}lti_user USING (user_id)"
                . "WHERE q.ct_id = :ctId and a.ct_id = :ctId",
            'getAnswerForQuestion' => "SELECT * FROM {$connection['p']}ct_answer "
                . "WHERE question_id = :questionId AND user_id = :userId AND ct_id = :ctId",
            'getMostRecentAnswerDate' => "SELECT max(a.modified) as modified "
                . "FROM {$connection['p']}ct_answer a "
                . "join {$connection['p']}ct_question q on a.question_id = q.question_id "
                . "WHERE a.user_id = :userId AND q.ct_id = :ctId",
            'getNumberQuestionsAnswered' => "SELECT count(distinct a.user_id, a.question_id) as num_answered "
                . "FROM {$connection['p']}ct_answer a "
                . "join {$connection['p']}ct_question q on a.question_id = q.question_id "
                . "WHERE a.user_id = :userId AND q.ct_id = :ctId AND a.answer_txt is not null",
            'getGrade' => "SELECT * FROM {$connection['p']}ct_grade "
                . "WHERE ct_id = :ct_id AND user_id = :user_id",
            'getLtiContexts' => "SELECT DISTINCT lti.title as courseName, lti.context_key as ctxId "
                . "FROM {$connection['p']}ct_main m "
                . "JOIN {$connection['p']}lti_context lti on m.context_id = lti.context_id "
                . "WHERE m.user_id in ("
                    . "SELECT user_id "
                    . "FROM {$connection['p']}lti_user "
                    . "WHERE user_key = ("
                        . "SELECT user_key FROM {$connection['p']}lti_user "
                        . "WHERE user_id = :userId))",
        );
        $GradeQueries = array(
            'getByGradeId' => "SELECT * FROM {$connection['p']}ct_grade "
                . "WHERE grade_id = :grade_id",
            'count' => "SELECT COUNT(*) as count FROM {$connection['p']}ct_grade WHERE ct_id = :ctid",
            'gradesCtid' => "SELECT ct_id, grade FROM {$connection['p']}ct_grade WHERE ct_id = :ctid",
            'insert' => "INSERT INTO {$connection['p']}ct_grade "
                . "(ct_id, user_id, grade, modified) "
                . "VALUES (:ctId, :userId, :grade, :modified)",
            'update' => "UPDATE {$connection['p']}ct_grade "
                . "set user_id = :userId, ct_id = :ctId, grade = :grade, modified = :modified "
                . "WHERE grade_id = :gradeId",
        );
        $queries = array(
            'main' => $MainQueries,
            'question' => $QuestionQueries,
            'questionCode' => $QuestionCodeQueries,
            'questionSQL' => $QuestionSQLQueries,
            'answer' => $AnswerQueries,
            'user' => $UserQueries,
            'grade' => $GradeQueries,
        );
        return array(
            'PDOX' => $connection['PDOX'],
            'sentence' => $queries[$class][$name],
        );
    }

    public static function debug($string)
    {
        global $USER, $CFG;
        $displayedName = "";
        if($CFG->CT_log['debug']) {
            $fileLog = $CFG->CT_log['filePath'];
            if(is_object($USER) && isset($USER->id)){
                $user = new CT_User($USER->id);
                $displayedName = $user->getDisplayname();
            }
            error_log("******************" . $displayedName . "******************************************", 3, $fileLog);
            error_log(addslashes($string), 3, $fileLog);
            error_log("--------", 3, $fileLog);
        }

    }

}
