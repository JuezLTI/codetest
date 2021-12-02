<?php

namespace CT;


class CT_Test implements \JsonSerializable 
{
   private $test_id;
   private $description;
   private $name;
   private $exercises;

    public function __construct($exercise_id = null) {
        $context = array();
        if (isset($exercise_id)) {
            $query = \CT\CT_DAO::getQuery('exercise', 'getById');
            $arr = array(':exercise_id' => $exercise_id);
            $context = $query['PDOX']->rowDie($query['sentence'], $arr);
        }
        \CT\CT_DAO::setObjectPropertiesFromArray($this, $context);
    }
    
     public function jsonSerialize() {
        return [
            'test_id' => $this->getTest_id(),
            'description' => $this->getDescription(),
            'name' => $this->getName(),
            'exercises' => $this->getExercises(),
            
        ];
    }

    static function getToken() {
        global $CFG;
        $url = $CFG->repositoryUrl . "/api/auth/signin";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        curl_close($curl);

        return "Authorization: Bearer " . $result;
    }

    static function MapJsonToTestsArray($json) {
        $response = json_decode($json);

        if ($response) {
            $tests = array();
            foreach ($response as $test) {
                $exercises = array();
                $CTTest = new CT_Test();
                $CTTest->setTest_id($test->id);
                $CTTest->setName($test->name);
                $CTTest->setDescription($test->description);
                $exercises1 = ($test->exercises);
                foreach ($exercises1 as $exercise) {
                    if ($exercise->type == 'MYSQL') {
                        $CTExercise = self::mapObjectToSQLExercise($exercise, $test->id);
                    } else {
                        $CTExercise = self::mapObjectToCodeExercise($exercise, $test->id);
                    }

                    array_push($exercises, $CTExercise);
                }
                $CTTest->setExercises($exercises);
                array_push($tests, $CTTest);
            }
            return $tests;
        }
    }

    static function mapObjectToExercise($exercise, $testId = null) {
        $CTExercise = new CT_Exercise();
        $CTExercise->setExerciseId($exercise->id);
        $CTExercise->setTitle($exercise->title);
        $CTExercise->setDifficulty($exercise->difficulty);
        $CTExercise->setType($exercise->type);
        $CTExercise->setTestId($testId);
        isset($exercise->averageGrade) ? $CTExercise->setAverageGrade($exercise->averageGrade) : false;
        isset($exercise->keywords) ? $CTExercise->setKeywords($exercise->keywords) : false;

        return $CTExercise;
    }

    static function mapObjectToSQLExercise($exercise, $testId = null) {
        $CTExercise = new CT_ExerciseSQL();
        $CTExercise->setExerciseId($exercise->id);
        $CTExercise->setCtId($_SESSION['ct_id']);
        $CTExercise->setTitle($exercise->title);
        $CTExercise->setDifficulty($exercise->difficulty);
        $CTExercise->setType($exercise->type);
        $CTExercise->setTestId($testId);
        isset($exercise->averageGrade) ? $CTExercise->setAverageGrade($exercise->averageGrade) : false;
        isset($exercise->keywords) ? $CTExercise->setKeywords($exercise->keywords) : false;
        (isset($exercise->exercise_dbms) ? $CTExercise->setExerciseDbms($exercise->exercise_dbms) : null );
        (isset($exercise->exercise_sql_type) ? $CTExercise->setExerciseSQLType($exercise->exercise_sql_type) : null );
        (isset($exercise->exercise_database) ? $CTExercise->setExerciseDatabase($exercise->exercise_database) : null );
        (isset($exercise->exercise_solution) ? $CTExercise->setExerciseSolution($exercise->exercise_solution) : null );
        (isset($exercise->exercise_probe) ? $CTExercise->setExerciseProbe($exercise->exercise_probe) : null );
        (isset($exercise->exercise_onfly) ? $CTExercise->setExerciseOnfly($exercise->exercise_onfly) : null );
        (isset($exercise->exercise_must) ? $CTExercise->setExerciseMust($exercise->exercise_must) : null );
        (isset($exercise->exercise_musnt) ? $CTExercise->setExerciseMusnt($exercise->exercise_musnt) : null );

        return $CTExercise;
    }

    static function mapObjectToCodeExercise($exercise, $testId = null) {
        $CTExercise = new CT_ExerciseCode();
        $CTExercise->setExerciseId($exercise->id);
        $CTExercise->setCtId($_SESSION['ct_id']);
        $CTExercise->setTitle($exercise->title);
        $CTExercise->setDifficulty($exercise->difficulty);
        $CTExercise->setType($exercise->type);
        $CTExercise->setTestId($testId);
        isset($exercise->averageGrade) ? $CTExercise->setAverageGrade($exercise->averageGrade) : false;
        isset($exercise->keywords) ? $CTExercise->setKeywords($exercise->keywords) : false;
        $CTExercise->setExerciseLanguage($exercise->exercise_language);
        (isset($exercise->exercise_input_test) ? $CTExercise->setExerciseInputTest($exercise->exercise_input_test) : null );
        (isset($exercise->exercise_input_grade) ? $CTExercise->setExerciseInputGrade($exercise->exercise_input_grade) : null );
        (isset($exercise->exercise_output_test) ? $CTExercise->setExerciseOutputTest($exercise->exercise_output_test) : null );
        (isset($exercise->exercise_output_grade) ? $CTExercise->setExerciseInputGrade($exercise->exercise_output_grade) : null );
        (isset($exercise->exercise_solution) ? $CTExercise->setExerciseSolution($exercise->exercise_solution) : null );
        (isset($exercise->exercise_must) ? $CTExercise->setExerciseMust($exercise->exercise_must) : null );
        (isset($exercise->exercise_musnt) ? $CTExercise->setExerciseMusnt($exercise->exercise_musnt) : null );

        return $CTExercise;
    }

    static function MapJsonToTest($json) {
        $response = json_decode($json);
        $CTTest = new CT_Test();
        $CTTest->setTest_id($response->id);
        $CTTest->setName($response->name);
        $CTTest->setDescription($response->description);
        $exercises1 = ($response->exercises);
        $exercises = array();
        foreach ($exercises1 as $exercise) {
            if ($exercise->type == 'MYSQL') {
                $CTExercise = self::mapObjectToSQLExercise($exercise, $response->id);
            } else {
                $CTExercise = self::mapObjectToCodeExercise($exercise, $response->id);
            }
            array_push($exercises, $CTExercise);
        }
        $CTTest->setExercises($exercises);
        return $CTTest;
    }

    static function checkerAdd($value) {
        global $CFG;
        
        //checks the category of the value and adds it if it is not
        if (in_array($value, $CFG->type)) {
            if (!in_array($value, $_SESSION['tags']['type'])) {
                array_push($_SESSION['tags']['type'], $value);
            }
        } else if (in_array($value, $CFG->difficulty)) {
            if (!in_array($value, $_SESSION['tags']['difficulty'])) {
                array_push($_SESSION['tags']['difficulty'], $value);
            }
        } else if (is_numeric($value) && $value <= 5 && $value > 0 ) {
           
            $_SESSION['tags']['averageGrade'] = Array();
            array_push($_SESSION['tags']['averageGrade'], $value);
        } else if ($value=="delete") {
            if (!empty($_SESSION['tags']['averageGrade'])) {
                unset($_SESSION['tags']['averageGrade'][0]);
            }
        } else {
            if (!in_array($value, $_SESSION['tags']['keywords'])) {
                array_push($_SESSION['tags']['keywords'], $value);
            }
        }
    }

    static function checkerDelete($value) {
        //Search for the value and delete it
        
        if (($key = array_search($value, $_SESSION['tags']['type'])) !== false) {
            unset($_SESSION['tags']['type'][$key]);
            $_SESSION['tags']['type'] = array_values($_SESSION['tags']['type']);
        } else if (($key = array_search($value, $_SESSION['tags']['difficulty'])) !== false) {
            unset($_SESSION['tags']['difficulty'][$key]);
            $_SESSION['tags']['difficulty'] = array_values($_SESSION['tags']['difficulty']);
        } else if (($key = array_search($value, $_SESSION['tags']['averageGrade'])) !== false) {
            unset($_SESSION['tags']['averageGrade'][$key]);
            $_SESSION['tags']['averageGrade'] = array_values($_SESSION['tags']['averageGrade']);
        }else if (($key = array_search($value, $_SESSION['tags']['keywords'])) !== false) {
            unset($_SESSION['tags']['keywords'][$key]);
            $_SESSION['tags']['keywords'] = array_values($_SESSION['tags']['keywords']);
        }
    }

    static function checker($object) {
        global $CFG;
        $arrayTags = Array();

        //Add the categories with values to an array
        foreach ($_SESSION['tags'] as $x => $x_value) {
            if ($_SESSION['tags'][$x]) {
                array_push($arrayTags, $x);
            }
        }
   
        //depending on the amount of values returns a url and the data to post in the call
        if (count($arrayTags) == 4) {

            $array["postData"] = [[$arrayTags[0]], $_SESSION['tags'][$arrayTags[0]], [$arrayTags[1]],
                $_SESSION['tags'][$arrayTags[1]], [$arrayTags[2]], $_SESSION['tags'][$arrayTags[2]],
                [$arrayTags[3]], $_SESSION['tags'][$arrayTags[3]]];
            $array["url"] = $CFG->repositoryUrl . "/api/".$object."/getTestExerciseBy4Values";
        } else if (count($arrayTags) == 3) {

            $array["postData"] = [[$arrayTags[0]], $_SESSION['tags'][$arrayTags[0]], [$arrayTags[1]],
                $_SESSION['tags'][$arrayTags[1]], [$arrayTags[2]], $_SESSION['tags'][$arrayTags[2]]];
            $array["url"] = $CFG->repositoryUrl . "/api/".$object."/getTestExerciseBy3Values";
        } else if (count($arrayTags) == 2) {

            $array["postData"] = [[$arrayTags[0]], $_SESSION['tags'][$arrayTags[0]], [$arrayTags[1]], $_SESSION['tags'][$arrayTags[1]]];
            $array["url"] = $CFG->repositoryUrl . "/api/".$object."/getTestExerciseByValues";
        } else if (count($arrayTags) == 1) {

            $array["postData"] = [$_SESSION['tags'][$arrayTags[0]], [$arrayTags[0]]];
            $array["url"] = $CFG->repositoryUrl . "/api/".$object."/getTestByValue";
        }


        if (isset($array)) {
            return $array;
        }
    }

    
    static function findTestForImportByPage($page) {
        global $CFG;
        $url = $CFG->repositoryUrl . "/api/tests/getAllTest/".$page;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', self::getToken()));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        $decode= json_decode($result);
        $totalPages=$decode[1];
        $tests = json_encode($decode[0]);
        curl_close($curl);

        $tests = self::MapJsonToTestsArray($tests);
        $array = ['tests' =>$tests, 'totalPages'=> $totalPages[0]];
       

        return $array;
    }

    //check the test exercises to leave the ones that match the tags
    static function checkerTests($tests) {
        $tests1 = Array();
        if($tests){
        foreach ($tests as $test1 => $test) {
            $test1 = self::checkerTest($test);
            array_push($tests1, $test1);
        }
        }
        return $tests1;
    }
    
    static function in_array_any($needles, $haystack) {
        return !empty(array_intersect($needles, $haystack));
    }

    //check the test exercises to leave the ones that match the tags
    static function checkerTest($test) {
        foreach ($test->getExercises() as $exercise2 => $exercise) {
            if (!in_array($exercise->getType(), $_SESSION['tags']['type']) && !empty($_SESSION['tags']['type'])) {
                unset($test->exercises[$exercise2]);
            }
            if (!in_array($exercise->getDifficulty(), $_SESSION['tags']['difficulty']) && !empty($_SESSION['tags']['difficulty'])) {
                unset($test->exercises[$exercise2]);
            }
            if ( (empty($exercise->getAverageGrade()) && !empty($_SESSION['tags']['averageGrade'])) || (!empty($exercise->getAverageGrade()) && !empty($_SESSION['tags']['averageGrade']) && $exercise->getAverageGrade() < $_SESSION['tags']['averageGrade'][0] )) {
                unset($test->exercises[$exercise2]);
            }
            if ((empty($exercise->getKeywords()) && !empty($_SESSION['tags']['keywords'])) ||
                    (!empty($exercise->getKeywords()) && !empty($_SESSION['tags']['keywords']) && !self::in_array_any($exercise->getKeywords(), $_SESSION['tags']['keywords']))) {
                unset($test->exercises[$exercise2]);
            }
        }
        return $test;
    }

    
    //Find the Test exercises on the repo by the tags
    static function findTestForImportByValue($value = null, $page = 0) {
        
  //if values is passed check if is already on the array
        if ($value) {
            self::checkerAdd($value);
        }
        $array = self::checker("tests");

        $postData = $array["postData"];
        $url = $array["url"] . "/" . $page;
        
        //if are tags
        if (isset($postData)) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', self::getToken()));
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($curl);
            curl_close($curl);
            $decode = json_decode($result);
            $totalPages = $decode[1];
            $tests = json_encode($decode[0]);

            //decode the exercises from json and maps to Test objects deleting the exercise that do not meet the tags
            $tests = self::MapJsonToTestsArray($tests);
            $tests1 = self::checkerTests($tests);
            $array = ['tests' => $tests1, 'totalPages' => $totalPages[0]];
        } else {
               //if not tags
            
            $array = \CT\CT_Test::findTestForImportByPage($page);
        }
        return $array;
    }

    static function findTestForImportByDeleteValue($value) {
        global $CFG;
//Deletes the value passed
        self::checkerDelete($value);
        
         //Check if there is any value left
        $array = self::checker("tests");
        $postData = $array["postData"];
        $url = $array["url"] . "/0";

        //if there is any value left
        if (isset($postData)) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', self::getToken()));
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($curl);
            curl_close($curl);
            $decode = json_decode($result);
            $totalPages = $decode[1];
            $tests = json_encode($decode[0]);
            $tests = self::MapJsonToTestsArray($tests);
            $array = ['tests' => $tests, 'totalPages' => $totalPages[0]];
        } else {
            //if there is no value left
            
            $array = \CT\CT_Test::findTestForImportByPage(0);
        }
        return $array;
    }

    static function findTestForImportId($id) {
        global $CFG;
        $url = $CFG->repositoryUrl."/api/tests/getTestId/" . $id;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', self::getToken()));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        curl_close($curl);
        $CTTest= self::MapJsonToTest($result);
        $test = self::checkerTest($CTTest);
      
        return $test;
    }

    //Find a exercise by Test_id and exercise_id
    static function findTestForImportExerciseId($exercise_id, $test_id) {
        global $CFG;
        $url = $CFG->repositoryUrl."/api/tests/getTestId/" . $test_id;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', self::getToken()));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($result);
        $exercises = ($response->exercises);
        foreach ($exercises as $exercise) {
            //after import the test search for the exercise with the id passed
            if ($exercise->id == $exercise_id) {
                if( in_array($exercise->type, $CFG->programmingLanguajes)){
               $CTExercise = \CT\CT_Test::mapObjectToCodeExercise($exercise, $test_id);
           }else{
                $CTExercise = \CT\CT_Test::mapObjectToSQLExercise($exercise, $test_id);
           }
            }
        }
        return $CTExercise;
    }

    function createAnswer($user_id, $answer_txt) {
        //Look for answer in the db
        $answer = \CT\CT_Answer::getByUserAndExercise($user_id, $this->getExerciseId(), $this->getCtId());
        $answer->setUserId($user_id);
        $answer->setExerciseId($this->getExerciseId());
        $answer->setAnswerTxt($answer_txt);
        
        //checks if is correct
        if ($this->preGrade($answer)) {
            $this->grade($answer);
        }
        $answer->save();
        $this->answers = $this->getAnswers();
        array_push($this->answers, $answer);
        $main = $this->getMain();
        $main->gradeUser($answer->getUserId());
        return $answer;
    }

    public function getMain()
    {
        return new CT_Main($this->getCtId());
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
    public function getCtId()
    {
        return $this->ct_id;
    }

    /**
     * @param mixed $ct_id
     */
    public function setCtId($ct_id)
    {
        $this->ct_id = $ct_id;
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

    /**
     * @return \CT\CT_Answer[] $answers
     */
   

    /**
     * @return CT_Exercise
     */
    public function getExerciseParent()
    {
        return new CT_Exercise($this->getExerciseId());
    }

    public function setExerciseParentProperties()
    {
        \CT\CT_DAO::setObjectPropertiesFromArray($this, \CT\CT_DAO::setObjectPropertiesToArray($this->getExerciseParent()));
    }

    public function isNew()
    {
        $exercise_id = $this->getExerciseId();
        return !(isset($exercise_id) && $exercise_id > 0);
    }
    
    public function getTest_id() {
        return $this->test_id;
    }

    public function getStatement() {
        return $this->statement;
    }

    public function getExercises() {
        return $this->exercises;
    }

    public function setTest_id($test_id): void {
        $this->test_id = $test_id;
    }

    public function setStatement($statement): void {
        $this->statement = $statement;
    }

    public function setExercises($exercises): void {
        $this->exercises = $exercises;
    }
    
    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description): void {
        $this->description = $description;
    }
    public function getName() {
        return $this->name;
    }

    public function setName($name): void {
        $this->name = $name;
    }

}