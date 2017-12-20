<?php
class myTemplate {
  
  private $templateFileContents;
  private $arrPairs;
  public $translation;
  
  public function formCreate($title) {
    $this->formContent="";
    $this->formFields =  array();
    $this->formAdd("$title", "LBL");
  }  

  public function __construct($templateFile=null, $arrPairs=null){
    $translation = "";
    if($templateFile!==null){
      $this->readTemplate($templateFile);
    }
    if(is_array($arrPairs) && (strlen($this->templateFileContents)>0)){
      $this->arrPairs = $arrPairs;
      $this->translate();
    }
    
  }
  
  public function translate(){
//    print_r($this->templateFileContents);
//    print_r($this->arrPairs);
    $out = strtr($this->templateFileContents, $this->arrPairs);
//    print_r($out);
    $this->translation = $out;
  }
  
  public function readTemplate($templateFile){
    $this->templateFileContents = file_get_contents($templateFile);
  }
  
}