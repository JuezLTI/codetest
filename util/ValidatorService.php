<?php
class ValidatorService{
 
    private  $validators;
    private $codeLanguages;

    public function __construct(){
        global $CFG_CT;
        
        $this->codeLanguages = null;
        $this->validators = $CFG_CT->validators;
       
    }

    public function getCodeLanguages(){

        if(!$this->codeLanguages){
            foreach($this->validators as $validator){

                $this->codeLanguages[] = $validator["name"];

            }
        }

        return $this->codeLanguages;
    }

    public function getValidatorUrl($codeLanguage){

        if(isset($this->validators[$codeLanguage])){

            $validatorUrl = $this->validators[$codeLanguage]["baseUrl"];
          
        }else{
            
            $validatorUrl = "Validator DOES NOT EXIST";
        }

        return $validatorUrl;

    }
}

