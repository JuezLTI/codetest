<?php

// Make this require relative to the parent of the current folder
// http://stackoverflow.com/exercises/24753758


require_once dirname(__DIR__) . "/config.php";
require 'vendor/autoload.php';

$CFG_CT->codetestRootDir = dirname(__FILE__);
$CFG_CT->codetestBasePath = __DIR__;


$CFG_CT->twig = array(
    'viewsPath' => __DIR__ . "/views",
    'debug' => true,
    'cachePath' => __DIR__ . "/tmp",
);

$CFG_CT->CT_log = array(
    'debug' => true,
    'filePath' => __DIR__ . "/tmp/ctLog.log",
);

$CFG_CT->repositoryUrl = "spring-codetest:8080";

$CFG_CT->type = [
    "PHP" => "PHP",
    "MYSQL" => "MYSQL",
    "Python" => "Python",
    "Java" => "Java"
];

$CFG_CT->apiConfigs = [
    "spring-repo" => [
        // MUST HAVE A TRAILING SLASH
        "baseUrl" => "http://{$CFG_CT->repositoryUrl}/",
        "user" => "codetest",
        "pass" => "c0d3te5t",
    ],
    "authorkit" => [
        // MUST HAVE A TRAILING SLASH
        "baseUrl" => 'https://fgpe.dcc.fc.up.pt/api/',
        "user" => "info@juezlti.eu",
        "pass" => "Ju3zLT1.",
    ],
    "xml-validator" => [
        // MUST HAVE A TRAILING SLASH
        "baseUrl" => 'http://xml-validator:3000/',
    ],
];

$CFG_CT->programmingLanguajes = array(
    'PHP',
    'Java',
    'Javascript',
    'Python'
);

$CFG_CT->CT_Types = array(
    'formsPath' => 'exercise/forms/',
    'studentsPath' => 'exercise/students/',
    'types' => array(
        'MYSQL' => array(
            'name' => 'sql',
            'class' => \CT\CT_ExerciseSQL::class,
            'instructorForm' => 'exerciseSQLForm.php.twig',
            'studentView' => 'exerciseSQLStudent.php.twig',
            'sqlTypes' => array('SELECT', 'DML', 'DDL'),
            'dbConnections' => array(
                array(
                    'name' => 'MySQL',
                    'dbDriver' => 'mysql',
                    'dbHostName' => 'tsugi-mysql-db',
                    'dbPort' => 3306,
                    'dbUser' => 'tsugi_root',
                    'dbPassword' => 'tpass',
                    'onFly' => array(
                        'allowed' => true,
                        'userPrefix' => 'JUEZ',
                        'createIsolateUserProcedure' => 'tsugi.CREATEISOLATEUSER',
                        'dropIsolateUserProcedure' => 'tsugi.DROPISOLATEUSER',
                    ),
                ),
                array(
                    'name' => 'Oracle',
                    'dbDriver' => 'oci',
                    'dbHostName' => 'localhost',
                    'dbPort' => 1521,
                    'dbSID' => 'dbSID',
                    'dbUser' => 'oraUser',
                    'dbPassword' => 'oraPass',
                    'onFly' => array(
                        'allowed' => true,
                        'userPrefix' => 'userPrefix',
                        'createIsolateUserProcedure' => 'CREATEISOLATEUSER', // Show definition at the end
                        'dropIsolateUserProcedure' => 'DROPISOLATEUSER', // Show definition at the end
                    ),
                ),
                array(
                    'name' => 'SQLite',
                    'dbDriver' => 'sqlite',
                    'dbFile' => '/path/to/file.sq3 or :memory:',
                    'dbUser' => '',
                    'dbPassword' => '',
                    'onFly' => array(
                        'allowed' => true,
                    ),
                ),
            ),
        ),
        'programming' => array(
            'name' => 'programming',
            'class' => \CT\CT_ExerciseCode::class,
            'instructorForm' => 'exerciseCodeForm.php.twig',
            'studentView' => 'exerciseCodeStudent.php.twig',
            'timeout' => 5,
            'codeLanguages' => array(
                array('name' => 'PHP', 'ext' => 'php', 'command' => 'php -f', 'stdin' => false),
                array('name' => 'Java', 'ext' => 'java', 'command' => 'java -Duser.language=es -Duser.region=ES', 'stdin' => true),
                array('name' => 'Javascript', 'ext' => 'js', 'command' => 'node', 'stdin' => true),
                array('name' => 'Python', 'ext' => 'py', 'command' => 'python', 'stdin' => true),
            ),
        ),
    ),
);

$CFG_CT->difficulty = array(
    "Easy" => "Easy",
    "medium" => "Medium",
    "Hard" => "Hard"
);


/**********************************************************
 ******************* ORACLE *******************************
 **********************************************************
-- ******* Routines to execute on Oracle System as 'dbUser'
--  Create onFly user
CREATE OR REPLACE NONEDITIONABLE PROCEDURE CREATEISOLATEUSER
(
  V_USERNAME IN VARCHAR2,
  V_PASSWORD IN VARCHAR2
)
AUTHID DEFINER
AS
    V_TABLESPACE VARCHAR2(100) DEFAULT 'USERS';
    V_QUOTA VARCHAR2(10) DEFAULT '2M';
    stmtCreateUser VARCHAR2(2000);
    stmtGrantRole VARCHAR2(2000);
BEGIN
  EXECUTE IMMEDIATE 'ALTER SESSION SET "_ORACLE_SCRIPT"=TRUE';
  stmtCreateUser := 'CREATE USER ' || V_USERNAME || ' IDENTIFIED BY ' || V_PASSWORD
    || ' DEFAULT TABLESPACE ' || V_TABLESPACE
    || ' QUOTA ' || V_QUOTA || ' ON ' || V_TABLESPACE
    || ' ACCOUNT UNLOCK'
    ;
  EXECUTE IMMEDIATE stmtCreateUser;
  stmtGrantRole := 'GRANT users_juezlti TO ' || V_USERNAME;
  EXECUTE IMMEDIATE stmtGrantRole;
END CREATEISOLATEUSER;
/
-- Drop onFly user
CREATE OR REPLACE NONEDITIONABLE PROCEDURE DROPISOLATEUSER
(
  V_USERNAME IN VARCHAR2
) AS
    stmtDropUser VARCHAR2(2000);
BEGIN
  EXECUTE IMMEDIATE 'ALTER SESSION SET "_ORACLE_SCRIPT"=TRUE';
  stmtDropUser := 'DROP USER ' || V_USERNAME || ' CASCADE'
    ;
  EXECUTE IMMEDIATE stmtDropUser;
END DROPISOLATEUSER;
/
-- ****** Routines to execute on Oracle System as DBA ******
CREATE ROLE users_juezlti;
GRANT CONNECT TO users_juezlti;
GRANT CREATE PROCEDURE, CREATE SEQUENCE, CREATE TABLE,
CREATE TRIGGER, CREATE TYPE, CREATE VIEW TO users_juezlti;
GRANT users_juezlti TO ***dbUser*** WITH ADMIN OPTION;
GRANT CREATE USER, DROP USER TO ***dbUser*** CONTAINER=ALL;
-- Restricting CREATE and DROP USER to 'dbUser'
create or replace NONEDITIONABLE TRIGGER USERRESTRICT
BEFORE CREATE OR DROP ON DATABASE
BEGIN
    IF (
        ora_login_user = '***dbUser***'
        AND
        ora_dict_obj_type = 'USER'
        AND
        INSTR(ora_dict_obj_name, '***userPrefix***') != 1
        ) THEN
            RAISE_APPLICATION_ERROR(-20001, 'Cannot CREATE/DROP USER');
    END IF;
END;
/
*/

/**********************************************************
 ******************* MySQL ********************************
 **********************************************************
-- ******* Routines to execute on MySQL System as DBA
USE **appDBName**;
DROP PROCEDURE IF EXISTS CREATEISOLATEUSER;
--  Create onFly user
DELIMITER $$
CREATE PROCEDURE CREATEISOLATEUSER
(
  IN V_USERNAME VARCHAR(100),
  IN V_PASSWORD VARCHAR(100)
)
SQL SECURITY DEFINER
BEGIN
  DECLARE dbUserPrefix VARCHAR(20) DEFAULT '**dbPrefix**';
  DECLARE stmtCreateUser VARCHAR(2000);
  DECLARE stmtCreateDatabase VARCHAR(2000);
  DECLARE stmtGrant VARCHAR(2000);
  IF LEFT(V_USERNAME, LENGTH(dbUserPrefix)) = dbUserPrefix THEN
	SET @stmtCreateUserConcat = CONCAT('CREATE USER ''', V_USERNAME, '''@''**webServer | localhost**'' IDENTIFIED BY ''', V_PASSWORD, '''');
	PREPARE stmtCreateUser FROM @stmtCreateUserConcat;
	EXECUTE stmtCreateUser;
	SET @stmtCreateDatabaseConcat = CONCAT('CREATE DATABASE IF NOT EXISTS ', V_USERNAME);
    PREPARE stmtCreateDatabase FROM @stmtCreateDatabaseConcat;
	EXECUTE stmtCreateDatabase;
	SET @stmtGrantConcat = CONCAT('GRANT ALL PRIVILEGES ON ', V_USERNAME, '.* TO ''', V_USERNAME, '''@''**webServer | localhost**''');
    PREPARE stmtGrant FROM @stmtGrantConcat;
	EXECUTE stmtGrant;
  END IF;
END
$$
DELIMITER ;
-- Drop onFly user
DROP PROCEDURE IF EXISTS DROPISOLATEUSER;
DELIMITER $$
CREATE  PROCEDURE DROPISOLATEUSER
(
  IN V_USERNAME VARCHAR(100)
)
SQL SECURITY DEFINER
BEGIN
  DECLARE dbUserPrefix VARCHAR(20) DEFAULT '**dbPrefix**';
  DECLARE stmtDropUser VARCHAR(2000);
  DECLARE stmtDropDatabase VARCHAR(2000);
  IF LEFT(V_USERNAME, LENGTH(dbUserPrefix)) = dbUserPrefix THEN
    SET @stmtDropUserConcat = CONCAT('DROP USER ''', V_USERNAME, '''@''**webServer | localhost**''');
	PREPARE stmtDropUser FROM @stmtDropUserConcat;
	EXECUTE stmtDropUser;
	SET @stmtDropDatabaseConcat = CONCAT('DROP DATABASE ', V_USERNAME);
    PREPARE stmtDropDatabase FROM @stmtDropDatabaseConcat;
	EXECUTE stmtDropDatabase;
  END IF;
END
$$
DELIMITER ;
GRANT EXECUTE ON PROCEDURE **appDBName**.CREATEISOLATEUSER TO 'dbUser'@'**webServer | localhost**';
GRANT EXECUTE ON PROCEDURE **appDBName**.DROPISOLATEUSER TO 'dbUser'@'**webServer | localhost**';
 */
