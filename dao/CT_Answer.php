<?php


namespace CT;


class CT_Answer
{
    private $answer_id;
    private $user_id;
    private $exercise_id;
    private $ct_id;
    private $answer_language;
    private $answer_txt;
    private $answer_success;
    private $modified;
    private $answer_output;
    private $tests_output;

    const MAX_TESTOUTPUT_SIZE = 16000;

    public function __construct($answer_id = null)
    {
        $context = array();
        if (isset($answer_id)) {
            $query = \CT\CT_DAO::getQuery('answer','getByAnswerId');
            $arr = array(':answer_id' => $answer_id);
            $context = $query['PDOX']->rowDie($query['sentence'], $arr);
        }
        \CT\CT_DAO::setObjectPropertiesFromArray($this, $context);
    }

    public static function getByUserAndExercise($userId, $exerciseId, $ctId)
    {
        $answer = new self();
        $query = \CT\CT_DAO::getQuery('answer','getByUserExercise');
        $arr = array(':userId' => $userId, ':exerciseId' => $exerciseId,  ':ctId' => $ctId);
        $context = $query['PDOX']->rowDie($query['sentence'], $arr);
        \CT\CT_DAO::setObjectPropertiesFromArray($answer, $context);
        return $answer;
    }

    /**
     * @return mixed
     */
    public function getAnswerId()
    {
        return $this->answer_id;
    }

    /**
     * @param mixed $answer_id
     */
    public function setAnswerId($answer_id)
    {
        $this->answer_id = $answer_id;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return CT_User
     */
    public function getUser()
    {
        return new CT_User($this->user_id);
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * @return mixed
     */
    public function getExerciseId()
    {
        return $this->exercise_id;
    }

    /**
     * @param mixed $exercise_id
     */
    public function setExerciseId($exercise_id)
    {
        $this->exercise_id = $exercise_id;
    }

    /**
     * @return mixed
     */
    public function getAnswerLanguage()
    {
        return $this->answer_language;
    }

    /**
     * @param mixed $answer_language
     */
    public function setAnswerLanguage($answer_language)
    {
        $this->answer_language = $answer_language;
    }

    /**
     * @return mixed
     */
    public function getAnswerOutput()
    {
        return $this->answer_output;
    }

    /**
     * @param mixed $answer_output
     */
    public function setAnswerOutput($answer_output)
    {
        $this->answer_output = $answer_output;
    }

    /**
     * @return mixed
     */
    public function getAnswerTxt()
    {
        return $this->answer_txt;
    }

    /**
     * @param mixed $answer_txt
     */
    public function setAnswerTxt($answer_txt)
    {
        $this->answer_txt = $answer_txt;
    }

    /**
     * @return mixed
     */
    public function getAnswerSuccess() : float
    {
        return $this->answer_success ?? 0.0;
    }

    /**
     * @param mixed $answer_success
     */
    public function setAnswerSuccess($grade = null)
    {
        if(!isset($grade)){
            $tests_output = $this->getTestsOutput();
            $points_obtained = 0;
            $total_points = 0;

            foreach ($tests_output as $test_output) {
                $mark = 1.0;
                $weight = $test_output['mark'];
                if(isset($weight) && $weight > 0) {
                    // weight could come in percentage or in perone
                    $mark = ($weight <= 1) ? $weight : ($weight / 100);
                }
                $total_points += $mark;

                if (str_starts_with(strtolower($test_output['classify']), strtolower('Accepted'))) {
                    $points_obtained += $mark;
                }
            }
            $grade = round(($total_points > 0 ? $points_obtained / $total_points : 0), 1);
        }
        $this->answer_success = $grade;
    }

    /**
     * @return mixed
     */
    public function getModified()
    {
        return $this->modified;
    }
     
    /**
     * @param mixed $modified
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    }
    
    public function getTestId() {
        return $this->test_id;
    }

    public function setTestId($test_id): void {
        $this->test_id = $test_id;
    }

    public function getCtId() {
        return $this->ct_id;
    }

    public function setCtId($ct_id): void {
        $this->ct_id = $ct_id;
    }

    /**
     * @return mixed
     */
    public function getTestsOutput()
    {
        return $this->tests_output;
    }

    /**
     * @param mixed $answer_output
     */
    public function setTestsOutput($tests_output)
    {
        $this->tests_output = array();
        if(!is_array($tests_output) && is_string($tests_output)){
            $tests_output = json_decode($tests_output);
        }

        foreach($tests_output as $testOutput) {
            if(strlen(json_encode($testOutput)) > self::MAX_TESTOUTPUT_SIZE){
                if(is_object($testOutput)) {
                    $testOutput->obtainedOutput = 'Test too big to be recorded. Please, reduce the output solution.';
                    $testOutput->outputDifferences = 'Test too big to be recorded. Please, reduce the output solution.';
                } else {
                    $testOutput['obtainedOutput'] = 'Test too big to be recorded. Please, reduce the output solution.';
                    $testOutput['outputDifferences'] = 'Test too big to be recorded. Please, reduce the output solution.';
                }
            }
            array_push($this->tests_output, $testOutput);
        }
    }

        
    public function isNew()
    {
        $answer_id = $this->getAnswerId();
        return !(isset($answer_id) && $answer_id > 0);
    }

    public function save() {
        global $CFG;
        $currentTime = new \DateTime('now', new \DateTimeZone($CFG->timezone));
        $currentTime = $currentTime->format("Y-m-d H:i:s");
        if($this->isNew()) { 
            $query = \CT\CT_DAO::getQuery('answer','insert');
        } else {
            $query = \CT\CT_DAO::getQuery('answer','update');
        }
        
        $arr = array(
            ':modified' => $currentTime,
            ':userId' => $this->getUserId(),
            ':ctId' => $this->getCtId(),
            ':exerciseId' => $this->getExerciseId(),
            ':modified' => $currentTime,
            ':answerTxt' => $this->getAnswerTxt(),
            ':answerSuccess' => $this->getAnswerSuccess(),
            ':answerLanguage' => $this->getAnswerLanguage(),
            ':answerOutput' => $this->getAnswerOutput(),
            ':testsOutput' => json_encode($this->getTestsOutput()),
        );
        if(!$this->isNew()) $arr[':answer_id'] = $this->getAnswerId();
        $query['PDOX']->queryDie($query['sentence'], $arr);
        if($this->isNew()) $this->setAnswerId($query['PDOX']->lastInsertId());
    }

    function delete() {
        $query = \CT\CT_DAO::getQuery('answer','deleteOne');
        $arr = array(':answerId' => $this->getAnswerId());
        $query['PDOX']->queryDie($query['sentence'], $arr);
    }

    static function deleteAnswers($exercises, $user_id) {
        $exerciseIds = array();
        foreach($exercises as $exercise) {
            array_push($exerciseIds, '"'.$exercise->getExerciseId().'"');
        }
        $query = \CT\CT_DAO::getQuery('answer','deleteFromExercises');
        $query['sentence'] = str_replace("/exercisesId/", implode(',', $exerciseIds), $query['sentence']);
        $arr = array(':userId' => $user_id);
        $query['PDOX']->queryDie($query['sentence'], $arr);
    }

    static function deleteInstructorAnswers($exercises, $ct_id)
    {
        $instructors = \CT\CT_User::findInstructors($ct_id);
        foreach($instructors as $instructor) {
            self::deleteAnswers($exercises, $instructor->getUserId());
        }
    }

    public function getDiffWithSolution($outputAnswer, $solution)
        {
        global $CFG;
    /*     // include the Diff class
        require_once $CFG->codetestRootDir . '/util/class.Diff.php';
        // compare two strings line by line
        return \Diff::toString(\Diff::compare($solution, $outputAnswer)); */
        // include the Diff class
        require_once $CFG->codetestRootDir . '/util/HTML_Diff.class.php';
        // compare two strings line by line
        return \HTML_Diff::htmlDiff($solution, $outputAnswer);
    }

}
