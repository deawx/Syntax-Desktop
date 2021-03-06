<?php
/*************************************
* class USERCREATE                   *
* puts a signature of the user who   *
* created the record.                *
* (it cannot be modified!)           *
**************************************/
class synUserCreate extends synElement {

  //constructor(name, value, label, size, help)
  function synUserCreate($n="", $v=null , $l=null, $s=255, $h="") {
    if ($n=="") $n =  "text".date("his");
    if ($l=="") $l =  ucfirst($n);

    $this->type = "text";
    $this->name  = $n;
    if ($v==null) {
      global $$n; 
      if(isset($_REQUEST[$n])) $this->value = $_REQUEST[$n]; 
    } else $this->value = $v;
    $this->label = $l;
    $this->size  = $s;
    $this->help  = $h;
    $this->db    = " VARCHAR(".$this->size.") NOT NULL";

    $this->configuration();
  }

  //private function
  function _html() {
    $disabled = ($this->value!="" ? " disabled=\"disabled\"" : "");
    // if empty, get the name of the actual user
    if(!isset($_REQUEST["synPrimaryKey"]) or $_REQUEST["synPrimaryKey"]=="") {
      $value = getSynUser();
    } else{
       $value = $this->value;
    }
      //$value = ($this->value!="" ? $this->value : getSynUser());
    if ($value) return "<input type='hidden' name='".$this->name."' maxsize='".$this->size."' value='".$value."'".$disabled."/> <strong>".username($value)." (".groupname($value).")</strong>\n";
  }

  //return the sql statement (i.e. `name`='gigi')
  function getSQL() {
    $ret="";
    //if primaryKey is empty, then it's a new record: put the creator's id
    if($_REQUEST['synPrimaryKey']=="") {
      if ($this->getValue()!="") {
        $ret=$this->getSQLname()."=".fixEncoding($this->getSQLValue());
      }
    }
    return $ret;
  }

  function getCell() {
    return "<span>".username($this->value)."</span>";
  }

  //function for the auto-configuration
  function configuration($i="",$k=99) {
    global $synElmName,$synElmType,$synElmLabel,$synElmSize,$synElmHelp;
    global $synElmSize;
    $synHtml = new synHtml();
    //parent::configuration();
    if (!isset($synElmSize[$i]) or $synElmSize[$i]=="") $synElmSize[$i]=$this->size;
    $this->configuration[4]="Dimensione: ".$synHtml->text(" name=\"synElmSize[$i]\" value=\"$synElmSize[$i]\"");

    //enable or disable the 3 check at the last configuration step
    global $synChkKey, $synChkVisible, $synChkEditable,$synChkMultilang;
    $_SESSION["synChkKey"][$i]=0;
    $_SESSION["synChkVisible"][$i]=1;
    $_SESSION["synChkEditable"][$i]=0;
    $_SESSION["synChkMultilang"][$i]=0;

    if ($k==99) return $this->configuration;
    else return $this->configuration[$k];
  }


} //end of class text

?>
