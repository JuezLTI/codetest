<?php

namespace CT;

use Tsugi\Config\ConfigInfo;

class CT_ConfigInfo extends ConfigInfo{
    public $cfg;
    public $CT_Types;
    public $type;
    public $difficulty;
    public $repositoryUrl;
    public $programmingLanguajes;
    public $codetestRootDir;
    public $codetestBasePath;
    public $twig;
    public $CT_log;
    public $apiConfigs;
    public $ExerciseProperty;
    public $validators;

    // Constructor opcional para inicializar propiedades si es necesario
    public function __construct($dirroot, $wwwroot, $dataroot=false) {
        parent::__construct($dirroot, $wwwroot, $dataroot);
        // Inicializar propiedades si es necesario
    }
}

