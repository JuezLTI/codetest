<?php


namespace CT;


class CT_Grade
{
    private $grade_id;
    private $ct_id;
    private $user_id;
    private $grade;
    private $modified;

    public function __construct($grade_id = null)
    {
        $context = array();
        if (isset($grade_id)) {
            $query = \CT\CT_DAO::getQuery('grade', 'getByGradeId');
            $arr = array(':exercise_id' => $grade_id);
            $context = $query['PDOX']->rowDie($query['sentence'], $arr);
        }
        \CT\CT_DAO::setObjectPropertiesFromArray($this, $context);
    }

    /**
     * @return mixed
     */
    public function getGradeId()
    {
        return $this->grade_id;
    }

    /**
     * @param mixed $grade_id
     */
    public function setGradeId($grade_id)
    {
        $this->grade_id = $grade_id;
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
    public function getUserId()
    {
        return $this->user_id;
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
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @param mixed $grade
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
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

    public function isNew()
    {
        $grade_id = $this->getGradeId();
        return !(isset($grade_id) && $grade_id > 0);
    }

    public function save() {
        global $CFG_CT;
        $currentTime = new \DateTime('now', new \DateTimeZone($CFG_CT->timezone));
        $currentTime = $currentTime->format("Y-m-d H:i:s");
        if($this->isNew()) {
            $query = \CT\CT_DAO::getQuery('grade','insert');
        } else {
            $query = \CT\CT_DAO::getQuery('grade','update');
        }
        $arr = array(
            ':modified' => $currentTime,
            ':userId' => $this->getUserId(),
            ':ctId' => $this->getCtId(),
            ':grade' => $this->getGrade(),
        );
        if(!$this->isNew()) $arr[':gradeId'] = $this->getGradeId();
        $query['PDOX']->queryDie($query['sentence'], $arr);
        if($this->isNew()) $this->setGradeId($query['PDOX']->lastInsertId());
    }
}

