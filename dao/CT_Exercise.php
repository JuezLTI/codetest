<?php

namespace CT;

use Exception;

class CT_Exercise implements \JsonSerializable {

    protected $exercise_id;
    protected $ct_id;
    protected $akId;
    protected $exercise_num;
    protected $testId;
    protected $title;
    protected $difficulty;
    protected $statement;
    protected $hint;
    protected $answers;
    protected $averageGradeUnderstability;
    protected $averageGradeDifficulty;
    protected $averageGradeTime;
    protected $averageGrade;
    protected $numberVotes;
    protected $keywords;
    protected $owner;
    protected $author;
    protected $sessionLanguage;
    protected $libraries;

    protected $codeExercise;

    //get the exercise from de db
    static function withId($exercise_id = null) {

        $query = \CT\CT_DAO::getQuery('exercise', 'getById');
        $arr = array(
            ':exercise_id' => $exercise_id,
            ':ct_id' => $_SESSION['ct_id'],
        );
        $exercise = new CT_Exercise();
        $exercises = $query['PDOX']->rowDie($query['sentence'], $arr);
        \CT\CT_DAO::setObjectPropertiesFromArray($exercise, $exercises);
        return $exercise;
    }

    public function __construct($exercise_id = null, $test_id = null) {
        if (isset($exercise_id)) {
            $this->testId = $test_id;
            $exercise = \CT\CT_Test::findTestForImportExerciseId($exercise_id, $test_id);
            $this->exercise_id = $exercise_id;
            $this->ct_id = $_SESSION["ct_id"];
            $this->title = $exercise->getTitle();
            $this->akId = $exercise->getAkId();
            $this->statement = $exercise->getStatement();
            $this->hint = $exercise->getHint();
            $this->difficulty = $exercise->getDifficulty();
            $this->averageGradeUnderstability = $exercise->getAverageGradeUnderstability();
            $this->averageGradeDifficulty = $exercise->getAverageGradeDifficulty();
            $this->averageGradeTime = $exercise->getAverageGradeTime();
            $this->averageGrade = $exercise->getAverageGrade();
            $this->numberVotes = $exercise->getNumberVotes();
            $this->keywords = $exercise->getKeywords();
            $this->owner = $_SESSION["lti"]["link_title"];
            $this->author = $_SESSION["lti"]["user_displayname"];
            $this->sessionLanguage = $_SESSION["lti"]["user_locale"];
            $this->libraries = $exercise->getLibraries();
        }
    }

    //necessary to use json_encode with exercise objects
    public function jsonSerialize() {
        return [
            'id' => $this->getExerciseId(),
            'ct_id' => $this->getCtId(),
            'exercise_num' => $this->getExerciseNum(),
            'title' => $this->getTitle(),
            'statement' => $this->getStatement(),
            'hint' => $this->getHint(),
            'akId' => $this->getAkId(),
            'difficulty' => $this->getDifficulty(),
            'averageGradeUnderstability' => $this->getAverageGradeUnderstability(),
            'averageGradeDifficulty' => $this->getAverageGradeDifficulty(),
            'averageGradeTime' => $this->getAverageGradeTime(),
            'averageGrade' => $this->getAverageGrade(),
            'numberVotes' => $this->getNumberVotes(),
            'keywords' => $this->getKeywords(),
            'owner' => $this->getOwner(),
            'author' => $this->getAuthor(),
            'sessionLanguage' => $this->getSessionLanguage(),
            'libraries' => $this->getLibraries(),
        ];
    }

    public function setFromObject($data) {
        foreach ($data as $key => $value){
            if(property_exists($this, $key)){
                $this->{$key} = $value;
            }
        }
        if(is_object($data) && isset($data->id)){
            $this->setExerciseId($data->id);
        }
        if(is_array($data) && isset($data['id'])){
            $this->setExerciseId($data['id']);
        }
        $this->setCtId($_SESSION["ct_id"]);
    }

    //returns the test of the exercises
    static function findExercises($exercises) {
        global $translator, $REST_CLIENT_REPO;
        $response = array();

        foreach ($exercises as $exercise) {
            $url = "api/exercises/getTestId/{$exercise['test_id']}";
            $reqResponse = $REST_CLIENT_REPO->getClient()->request('GET', $url);
            $result = $reqResponse->toArray();
            if (isset($result)) {
                foreach ($result->exercises as $exercise1) {
                    if ($exercise1->id == $exercise['exercise_id']) {
                        $exercise1->exercise_num = $exercise['exercise_num'];
                        $exercise1->test_id = $exercise['test_id'];
                        $exercise1->ct_id = $exercise['ct_id'];

                        array_push($response, $exercise1);
                    }
                }
            } else {
                $_SESSION['error'] = $translator->trans('backend-messages.connection.failed');
            }
        }

        return $response;
    }


    function createAnswer($user_id, $answer_txt, $answer_language = null, $answer_output = null, $tests_output = null) {
        $answer = \CT\CT_Answer::getByUserAndExercise($user_id, $this->getExerciseId(), $this->getCtId());
        if ($answer->getAnswerId() !== null) {
            $exists = true;
        } else {
            $exists = false;
        }
        $array = Array();

        //fill the answer
        $answer->setUserId($user_id);
        $answer->setExerciseId($this->getExerciseId());
        $answer->setAnswerTxt($answer_txt);
        $answer->setAnswerLanguage($answer_language);
        if(isset($answer_output)){
            $answer->setAnswerOutput($answer_output['feedback']);
        }
        if(isset($tests_output)){
            $answer->setTestsOutput($tests_output);
        }
        $answer->setAnswerSuccess(str_starts_with(strtolower($answer_output['classify']), strtolower('Accepted')));
        $answer->setCtId($this->getCtId());

        //save the answer
        $answer->save();
        $this->answers = $this->getAnswers();
        array_push($this->answers, $answer);
        $main = $this->getMain();
        $main->gradeUser($answer->getUserId());
        $array['answer'] = $answer;
        $array['exists'] = $exists;
        return $array;
    }


    static function findExerciseForImportByPage($page) {
        global $REST_CLIENT_REPO;
        $url = "api/exercises/getAllExercises/$page";
        $response = $REST_CLIENT_REPO->getClient()->request('GET', $url);

        $decode = $response->toArray();
        $totalPages = $decode[1];
        $exercises = json_encode($decode[0]);
        $exercises = self::MapJsonToExercisesArray($exercises);
        $array = ['exercises' => $exercises, 'totalPages' => $totalPages[0]];

        return $array;
    }

    //Find the exercises on the repo by the tags
    static function findExerciseForImportByValue($value = null, $page = 0) {
        global $REST_CLIENT_REPO;

        //if values is passed check if is already on the array
        if ($value) {
            CT_Test::checkerAdd($value);
        }
        $array = CT_Test::checker("exercises");
        $postData = $array["postData"];

        $url = $array["url"] . "/" . $page;

        //if are tags
        if (isset($postData)) {
            $response = $REST_CLIENT_REPO->getClient()->request('GET', $url, [
                'json' => $postData
            ]);

            $decode = $response->toArray();
            $totalPages = $decode[1];

            //decode the exercises from json and maps to Exercise objects
            $exercises = json_encode($decode[0]);
            $exercises = self::MapJsonToExercisesArray($exercises);

            $array = ['exercises' => $exercises, 'totalPages' => $totalPages[0]];
        } else {
            //if not tags

            $array = \CT\CT_Exercise::findExerciseForImportByPage($page);
        }
        return $array;
    }

    static function findExercisesForImportByDeleteValue($value) {
        global $REST_CLIENT_REPO;
        //Deletes the value passed
        CT_Test::checkerDelete($value);

        //Check if there is any value left
        $array = CT_Test::checker("exercises");
        $postData = $array["postData"];
        $url = $array["url"] . "/0";

        //if there is any value left
        if (isset($postData)) {
            $response = $REST_CLIENT_REPO->getClient()->request('GET', $url, [
                'json' => $postData
            ]);

            $decode = $response->toArray();
            $totalPages = $decode[1];
            $exercises = json_encode($decode[0]);
            $exercises = self::MapJsonToExercisesArray($exercises);
            $array = ['exercises' => $exercises, 'totalPages' => $totalPages[0]];
        } else {
            //if there is no value left

            $array = \CT\CT_Exercise::findExerciseForImportByPage(0);
        }
        return $array;
    }

    //Find exercise by id
    static function findExerciseForImportId($id) {
        global $REST_CLIENT_REPO;
        $url = "api/exercises/getExercise/$id";
        $response = $REST_CLIENT_REPO->getClient()->request('GET', $url);
        $exerciseArray = $response->toArray();
        $object = json_decode(json_encode($exerciseArray));
        $CTExercise = new CT_ExerciseCode();
        $CTExercise->setFromObject($object);
        if($exerciseOriginal = self::withId($id)){
            $CTExercise->setExerciseNum($exerciseOriginal->getExerciseNum());
            $CTExercise->setCodeExercise($exerciseOriginal->getCodeExercise());
        }

        return $CTExercise;
    }

    //Find exercise by akId
    static function findExerciseForImportAkId($id) {
        global $REST_CLIENT_REPO;
        $url = "api/exercises/getExercise/id/$id";
        $response = $REST_CLIENT_REPO->getClient()->request('GET', $url);
        $respBody = $response->getContent();
        if(strlen($respBody) == 0){
            return null;
        }

        $exerciseArray = $response->toArray();
        $object = json_decode(json_encode($exerciseArray));

        $CTExercise = new CT_ExerciseCode();
        $CTExercise->setFromObject($object);

        return $CTExercise;
    }

    //Find exercise for export
    static function findExerciseForExport($id) {
        global $REST_CLIENT_REPO;
        $fileContent = null;
        $url = "api/exercises/$id/export";
        $response = $REST_CLIENT_REPO->getClient()->request('GET', $url);
        if ($response->getStatusCode() == 200) {
            $fileContent = $response->getContent();
        }
        return $fileContent;
    }

    //Find libaries by exercise id
    static function findLibrariesForExerciseId($id) {
        global $REST_CLIENT_REPO;
        $url = "api/exercises/$id/libraries";
        $main = new \CT\CT_Main($_SESSION["ct_id"]);
        $response = $REST_CLIENT_REPO->getClient()->request('GET', $url);
        $respBody = $response->getContent();
        if(strlen($respBody) == 0){
            return null;
        }
        $librariesArray = $response->toArray();
        $libraries = json_decode(json_encode($librariesArray));

        return $libraries;
    }

    static function MapJsonToExercisesArray($json) {
        $response = json_decode($json);
        $exercises = array();
        $main = new \CT\CT_Main($_SESSION["ct_id"]);


        if ($response) {
            foreach ($response as $exercise) {

                $CTExercise = new CT_ExerciseCode();
                $CTExercise->setFromObject($exercise);

                array_push($exercises, $CTExercise);
            }
            return $exercises;
        }
    }

    function getNextExerciseNumber() {
        $query = \CT\CT_DAO::getQuery('exercise', 'getNextExerciseNumber');
        $arr = array(':ct_id' => $this->getCtId());
        $lastNum = $query['PDOX']->rowDie($query['sentence'], $arr)["lastNum"];
        return $lastNum + 1;
    }

    static function fixUpExerciseNumbers($ct_id) {
        $query = \CT\CT_DAO::getQuery('exercise', 'fixUpExerciseNumbers');
        $arr = array(':ctId' => $ct_id);
        $query['PDOX']->queryDie($query['sentence'], $arr);
    }

    /**
     * @return \CT\CT_Answer[] $answers
     */
    public function getAnswers($successful = false) {
        if (!is_array($this->answers)) {

            $this->answers = array();
            $query = \CT\CT_DAO::getQuery('exercise', 'getAnswers');
            $arr = array(
                ':exerciseId' => $this->getExerciseId(),
                ':ctId' => $this->getCtId(),
                ':answer_success' => $successful ? 1 : 0 // if $successful is true only get successful answers
            );
            $answers = $query['PDOX']->allRowsDie($query['sentence'], $arr);
            $this->answers = \CT\CT_DAO::createObjectFromArray(\CT\CT_Answer::class, $answers);
        }

        return $this->answers;
    }

    public function getNumberAnswers($successful = false) {
        return count($this->getAnswers($successful));
    }

    public function hasAnyAnswer($successful = false) {
        return $this->getNumberAnswers($successful) > 0;
    }

    public function getExerciseCode()
    {
        global $CFG;
        $class = $this->getMain()->getProperty('class');

        return new $class($this->getExerciseId());


        }

    /**
     * @return CT_Exercise
     */
    public function getExerciseParent() {
        return CT_Exercise::withId($this->getExerciseId());
    }

    public function setExerciseParentProperties() {
        \CT\CT_DAO::setObjectPropertiesFromArray($this, \CT\CT_DAO::setObjectPropertiesToArray($this->getExerciseParent()));
    }

    public function isNew() {
        $query = \CT\CT_DAO::getQuery('exercise', 'exists');

        $arr = array(
            ':exercise_id' => $this->getExerciseId(),
            ':ct_id' => $this->getCtId(),
        );
        $exerciseId = $query['PDOX']->rowDie($query['sentence'], $arr)["exerciseId"];
        return !(isset($exerciseId) && $exerciseId > 0);
    }

    public function save() {
        global $CFG;
        $currentTime = new \DateTime('now', new \DateTimeZone($CFG->timezone));
        $currentTime = $currentTime->format("Y-m-d H:i:s");
        $isNew = $this->isNew();
        $this->setExerciseNum($this->getNextExerciseNumber());
        $query = \CT\CT_DAO::getQuery('exercise', $isNew ? 'insert' : 'updateAll');

        $arr = array(
            ':exercise_id' => $this->getExerciseId(),
            ':ct_id' => $this->getCtId(),
            ':akId' => $this->getAkId(),
            ':title' => $this->getTitle(),
            ':statement' => $this->getStatement(),
            ':hint' => $this->getHint(),
            ':codeExercise' => (int)$this->getCodeExercise(),
        );
        ($isNew) ? ($arr[':exercise_num'] = $this->getExerciseNum()) : "";
        $query['PDOX']->queryDie($query['sentence'], $arr);

    }

    public function update() {

        $query = \CT\CT_DAO::getQuery('exercise', 'update');

        $arr = array(
            ':exercise_id' => $this->getExerciseId(),
            ':ct_id' => $this->getCtId(),
            ':exercise_num' => $this->getExerciseNum()
        );
        $query['PDOX']->queryDie($query['sentence'], $arr);
    }

    function delete() {
        $query = \CT\CT_DAO::getQuery('exercise', 'delete');
        $arr = array(
            ':exercise_id' => $this->getExerciseId(),
            ':ct_id' => $this->getCtId()
        );
        $query['PDOX']->queryDie($query['sentence'], $arr);
    }

    public function getMain() {
        return new CT_Main($this->getCtId());
    }

    public function getLibraries() {
        return $this->libraries;
    }

    /**
     * @param mixed $libraries
     */
    public function setLibraries($libraries) {
        $this->libraries = $libraries;
    }

    public function getSessionLanguage() {
        return $this->sessionLanguage;
    }

    /**
     * @param mixed $sessionLanguage
     */
    public function setSessionLanguage($sessionLanguage) {
        $this->sessionLanguage = $sessionLanguage;
    }

    public function getAuthor() {
        return $this->author;
    }

    /**
     * @param mixed $author
     */
    public function setAuthor($author) {
        $this->author = $author;
    }

    /**
     * @return mixed
     */
    public function getExerciseId() {
        return $this->exercise_id;
    }

    /**
     * @param mixed $exercise_id
     */
    public function setExerciseId($exercise_id) {
        $this->exercise_id = $exercise_id;
    }

    /**
     * @return mixed
     */
    public function getCtId() {
        return $this->ct_id;
    }

    /**
     * @param mixed $ct_id
     */
    public function setCtId($ct_id) {
        $this->ct_id = $ct_id;
    }


    /**
     * @param mixed $owner
     */
    public function setOwner($owner) {
        $this->owner = $owner;
    }

    public function setAkId($akId) {
        $this->akId = $akId;
    }

    public function getAkId() {
        return $this->akId;
    }

    /**
     * @return mixed
     */
    public function getExerciseNum() {
        return $this->exercise_num;
    }

    /**
     * @param mixed $exercise_num
     */
    public function setExerciseNum($exercise_num) {
        $this->exercise_num = $exercise_num;
    }

    public function getHint() {
        return $this->hint;
    }

    public function setHint($hint): void {
        $this->hint = $hint;
    }

    public function getStatement() {
        return $this->statement;
    }
    public function setStatement($statement): void {
        $this->statement = $statement;
    }

    public function getDifficulty() {
        return $this->difficulty;
    }

    public function setDifficulty($difficulty): void {
        $this->difficulty = $difficulty;
    }

    public function getTestId() {
        return $this->testId;
    }

    public function setTestId($testId): void {
        $this->testId = $testId;
    }

    public function getAverageGradeDifficulty() {
        return $this->averageGradeDifficulty;
    }

    public function getAverageGradeUnderstability() {
        return $this->averageGradeUnderstability;
    }

    public function getAverageGradeTime() {
        return $this->averageGradeTime;
    }

    public function getNumberVotes() {
        return $this->numberVotes;
    }

    public function setAverageGradeDifficulty($averageGradeDifficulty): void {
        $this->averageGradeDifficulty = $averageGradeDifficulty;
    }

    public function setAverageGradeUnderstability($averageGradeUnderstability): void {
        $this->averageGradeUnderstability = $averageGradeUnderstability;
    }

    public function setAverageGradeTime($averageGradeTime): void {
        $this->averageGradeTime = $averageGradeTime;
    }

    public function setNumberVotes($numberVotes): void {
        $this->numberVotes = $numberVotes;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title): void {
        $this->title = $title;
    }

    public function getAverageGrade() {
        return $this->averageGrade;
    }

    public function setAverageGrade($averageGrade): void {
        $this->averageGrade = $averageGrade;
    }

    public function getKeywords() {
        return $this->keywords;
    }

    public function getOwner() {
        return $this->owner;
    }

    public function setKeywords($keywords): void {
        $this->keywords = $keywords;
    }

    public function getCodeExercise() {
        return $this->codeExercise;
    }

    public function setCodeExercise($codeExercise) {
        $this->codeExercise = $codeExercise;
    }
}