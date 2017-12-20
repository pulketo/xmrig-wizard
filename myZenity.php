<?php
define("DEFAULT_W", 500);
define("DEFAULT_H", 200);
class myZenity {
  private $width;
  private $height;
  private $title;
  private $debug=false;
  private $timeout = 0;
  private $formContent = "";
  private $formFields=array();
  
  public function formCreate($title) {
    $this->formContent="";
    $this->formFields =  array();
    $this->formAdd("$title", "LBL");
  }  
  
  public function formAdd($varlabel, $type="RO", $opt=""){
    $varlab = explode("|", $varlabel);
    switch(sizeOf($varlab)){
      case 2:
        list($var, $label) = $varlab;
        break;
      case 1:
        list($label) = $varlab;
        break;
      default:
        die("err varlabel");
        break;
    }
    $this->formContent = $this->formContent. " --field=\"$label\":$type \"$opt\"";
    if ( $type == "NUM" || $type == "CHK" || $type == "RO" ){
      $this->formFields[] = $var;
    }
  }
  
  public function formShow() {
    $args = "--form $this->formContent --separator=\"|\"";
    $res = ($this->execute($args));   
    /* form devuelve 2 resultados, el contenido de la forma y el boton que se oprimió*/
    $tmp = explode("\n", $res);
    if(sizeOf($tmp)==2)
      list($ans, $button) = $tmp;
    else{
      list($button) = $tmp;
      die("cancel button");
    }
    
    $ans = explode("|", trim($ans, "| "));
    $ans = array_combine($this->formFields, $ans);
    return(array("response"=>$ans, "button"=>$button));
  }  

  public function question($text="", $ok="OK", $nok="Cancel", $opts=null) {
    $args = "--question --text='$text' --ok-label '$ok' --cancel-label '$nok' $opts";
    return ($this->execute($args));
  }

  public function info($text="", $opts=null) {
    $args = "--info --text='$text' $opts";
    return ($this->execute($args));
  }

  public function scale($text="", $min=0, $max=100, $default=1, $step=1, $opts=null) {
    $args = "--scale --text='$text' --min-value='$min' --max-value='$max' --value='$default' --step '$step' $opts";
    return ($this->execute($args));
  }
  
  public function entry($text="", $defaultText="", $opts=null) {
    $args = "--entry --text='$text' --entry-text='$defaultText' $opts";
    return ($this->execute($args));
  }

  public function radio($text="", $header="", $vars="", $items=array(), $selected = 0, $opts=null ) {
    // item0 will always be default
    // $i[]=("Amazing");
    // $i[]=("Average");
    $i = 0;
    $cols = "";
    $itemOpts = "";

    if (sizeOf($header) < 1)
      return false;

    if(!is_array($header)){
      $header = explode("|", $header);   
    }
    foreach($header as $k=>$v){
      $cols .= "--column '$v' ";
    }
    
    foreach($items as $k=>$itemRow){
      $arrItem = explode("|", $itemRow);
      foreach ($arrItem as $k=>$v){
        $arrItem[$k]="\"$v\"";
      }
      if($selected == $i)
        $itemOpts.="TRUE ".implode(" ", $arrItem)." ";
     else
       $itemOpts.="FALSE ".implode(" ", $arrItem)." ";
      $i++;
    }          
  
    $args = "--list --text='$text' --radiolist  $cols $itemOpts $opts";
    $res = $this->execute($args);
    $tmp = explode("\n", $res);
    if(sizeOf($tmp)==2)
      list($ans, $button) = $tmp;
    else{
      list($button) = $tmp;
      die("cancel button");
    }
    
    $ans = explode("|", trim($ans, "| "));
    $ans = array_combine(explode("|", $vars), $ans);
    return(array("response"=>$ans, "button"=>$button));
  }


  function __construct($title=__FILE__, $w=DEFAULT_W, $h=DEFAULT_H) {
    $this->width = $w;
    $this->height = $h;
    $this->title = $title;
    $this->center = true;
  }
  
  public function debug($status="true"){
    switch(strtolower($status)){
      case "enable":
      case "true":
      case "on":
      case "1":
        $this->debug=true;
        break;
     default:
        $this->debug=false;
        break;       
    }
  }
  
  public function setWSize($size) {
    list($w, $h) = explode("x", $size);
    $this->width = $w;
    $this->height = $h;
  }
  
  public function resetWSize() {
    $this->width = DEFAULT_W;
    $this->height = DEFAULT_H;
  }
  
  public function setTimeout($t) {
    $this->timeout=$t;
  }

  public function resetTimeout($t) {
    $this->timeout=0;
  }
  
  private function execute($args){
    $center = ($this->center === true)?" --center ":"";
      
    if ($this->width>0 && $this->height>0)
      $defargs = "$center --width $this->width --height $this->height --title '$this->title'";
    else
      $defargs = "$center --timeout $this->timeout --title '$this->title'";
    $cmd = trim("yad $args $defargs 2>/dev/null;echo $?");
    $exe = trim(`$cmd`);
    if ($this->debug == true){
      echo $cmd.PHP_EOL;
      echo $exe.PHP_EOL;
    }
    return $exe;
  }
    
} 
?>