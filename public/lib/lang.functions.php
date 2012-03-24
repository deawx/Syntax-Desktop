<?php
/******************************************************************************
***                                  LANG FUNCTIONS
*******************************************************************************/

//set the current language
function setLang($id) {
  global $db;
  $lang=intval($id);
  if ($lang=="") return false;

  //get the current lang
  $qry="SELECT initial FROM aa_lang WHERE id='".$lang."'";
  $res=$db->Execute($qry);
  if ($res->RecordCount()>0) {
    $arr=$res->FetchRow();
    $currlang=$arr[0];

    $_SESSION["aa_CurrentLang"]=$lang;
    $_SESSION["synSiteLang"]=$lang;
    $_SESSION["aa_CurrentLangInitial"]=$currlang;
    $_SESSION["synSiteLangInitial"]=$currlang;
    setlocale(LC_ALL, strtolower($currlang)."_".strtoupper($currlang));
    return true;
  } else {
    return false;
  }
}


//DEPRECATED - return the language list (i.e. en,it,es)
function getLangList() {
  global $db;
  $res=$db->Execute("SELECT initial FROM aa_lang");
  while (list($lang)=$res->FetchRow()) $ret.=$lang.", ";
  return substr($ret,0,-2);
}

//return languages array
function getLangArr() {
  global $db;
  $ret = array();
  $res = $db->Execute("SELECT initial FROM aa_lang");
  while(list($l)=$res->FetchRow()) $ret[] = $l;
  return $ret;
}

//translate an element for the desktop. If err==true display the error message
function translateSite($id,$err=false) {
  global $db;
    if (isset($_GET["synSiteLang"])) setLang($_GET["synSiteLang"]);
    if ($_SESSION["synSiteLang"]=="" or !isset($_SESSION["synSiteLang"])){
      $res=$db->Execute("SELECT id,initial FROM aa_lang ORDER BY id");
      if($res!=false){
        $arr=$res->FetchRow();
        $_SESSION["synSiteLang"]=$arr["id"];
        $_SESSION["synSiteLangInitial"]=$arr["initial"];
        setlocale(LC_ALL, strtolower($arr["initial"])."_".strtoupper($arr["initial"]));
      }
    }
  //if ($this->multilang==1 and $id!="") {
    $qry="SELECT * FROM aa_translation WHERE id='".addslashes($id)."'";
    $res=$db->Execute($qry);
    if ($res->RecordCount()==0) {  //umm... the field is multilang but there isn't a row in the translation table...
      $ret=$id;
    } else {
      $arr=$res->FetchRow();
      $ret=$arr[$_SESSION["synSiteLangInitial"]];

      if ($ret=="" and $err===true) {
        foreach ($arr as $mylang=>$mytrans) if (!is_numeric($mylang) and $mylang!="id" and $mytrans!="") $alt.="\n$mylang: ".substr(strip_tags($mytrans),0,10);
        $ret="<span style='color: gray' title=\"".htmlentities("Other Translations:".$alt)."\">[no translation]</span>";
      }
    }
  //} else $ret=$id;
  return $ret;
}


function updateLang() {
  global $db, $synSiteLang;
  if (isset($_GET["synSiteLang"])) setLang($_GET["synSiteLang"]);

  //check if exist a language id that match the session variable
  if ($_SESSION["synSiteLang"]!="") {
    $res=$db->Execute("SELECT id FROM aa_lang WHERE id=".$_SESSION["synSiteLang"]);
  }

  if ($_SESSION["synSiteLang"]=="" or $res->RecordCount()==0) {
    //$prefLang=getenv("HTTP_ACCEPT_LANGUAGE");
    $get_lang = get_languages();
    $prefLang = $get_lang[0][1];

    $res=$db->Execute("SELECT id FROM aa_lang WHERE `active`=1 AND initial='$prefLang'");
    if ($res->RecordCount()>0) list($_SESSION["synSiteLang"])=$res->FetchRow();

    else {
      $res=$db->Execute("SELECT id FROM aa_lang WHERE `active`=1 ORDER BY `order` LIMIT 0,1");
      if ($res->RecordCount()>0) list($_SESSION["synSiteLang"])=$res->FetchRow();
    }
  }

  //get the current lang
  $qry="SELECT initial FROM aa_lang WHERE id=".$_SESSION["synSiteLang"];
  $res=$db->Execute($qry);
  $arr=$res->FetchRow();
  $currlang=$arr[0];

  $_SESSION["aa_CurrentLangInitial"]=$currlang;
  $_SESSION["synSiteLangInitial"]=$currlang;
  setlocale(LC_ALL, strtolower($currlang)."_".strtoupper($currlang));
}



// if not already exists, insert a row in the translation table and return the 
// translation for the current selected lang
function l($value,$replace="") {
  global $db;
  //$lang=getLang(true);

  //get the list of languages
  $res=$db->Execute("SELECT initial FROM aa_lang");
  while (list($lang)=$res->FetchRow()) {
    $languagelist.=$lang.", ";
    $valuelist.="'".addslashes($value)."', ";
    $select.=$lang."= '".addslashes($value)."' OR ";
  }
  $languagelist=substr($languagelist,0,-2);
  $valuelist=substr($valuelist,0,-2);
  $select=substr($select,0,-3);
  //search for the string if already into database
  $qry="SELECT * FROM aa_translation WHERE $select";
  $res=$db->Execute($qry);
  $count=$res->RecordCount();
  // already exists
  if ($count>0) {
    $arr=$res->FetchRow();
    $id=$arr["id"];
  } else {
  //insert the row in each language
    $qry="INSERT INTO aa_translation ($languagelist) VALUES ($valuelist)";
    $res=$db->Execute($qry);
    $id=$db->Insert_ID();
  }
  $ret=translateSite($id);
  if ($replace!="") $ret=str_replace("###",$replace,$ret);
  
  return $ret;
}  


function translateDictionary($label){
  # traduzione singola
  # es. translateDictionary("home_title_realizzazioni")
  global $db;
  session_start();
  $lng = $_SESSION['synSiteLangInitial'];
  $qry = "SELECT t.$lng AS value FROM dictionary v JOIN aa_translation t ON v.value=t.id WHERE v.label='".$label."'";
  $res = $db->Execute($qry);
  if($res->RecordCount()==0){
    $ret = $label;
  } else {
    $arr=$res->FetchRow();
    $ret = $arr["value"];
  }    
  return $ret;
}


function multiTranslateDictionary($label=array()){
  # traduzione multipla, ritorna un array
  global $db;
  session_start();
  $lng = $_SESSION["synSiteLangInitial"];
  $ret = array();
  $qry = "SELECT v.label, t.$lng AS value FROM dictionary v JOIN aa_translation t ON v.value=t.id WHERE v.label='".implode("' OR v.label='", $label)."'";
  $res = $db->Execute($qry);
  $ret = array();
  while($arr = $res->FetchRow()) {
    $ret[$arr['label']] = $arr['value'];
  }
  return $ret;
}

/*
  Script Name: Full Operating system language detection
  Author: Harald Hope, Website: http://techpatterns.com/
  Script Source URI: http://techpatterns.com/downloads/php_language_detection.php
  Version 0.3.6
  Copyright (C) 8 December 2008

  This program is free software; you can redistribute it and/or modify it under
  the terms of the GNU General Public License as published by the Free Software
  Foundation; either version 3 of the License, or (at your option) any later version.
*/
function get_languages(){
	$a_languages = languages();
	$index = '';
	$complete = '';
	$found = false;// set to default value
	$user_languages = array();

	//check to see if language is set
	if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])){
		$languages = strtolower( $_SERVER["HTTP_ACCEPT_LANGUAGE"] );
		// $languages = ' fr-ch;q=0.3, da, en-us;q=0.8, en;q=0.5, fr;q=0.3';
		// need to remove spaces from strings to avoid error
		$languages = str_replace( ' ', '', $languages );
		$languages = explode( ",", $languages );
		//$languages = explode( ",", $test);// this is for testing purposes only

		foreach ( $languages as $language_list ){
			// pull out the language, place languages into array of full and primary
			// string structure:
			$temp_array = array();
			// slice out the part before ; on first step, the part before - on second, place into array
			$temp_array[0] = substr( $language_list, 0, strcspn( $language_list, ';' ) );//full language
			$temp_array[1] = substr( $language_list, 0, 2 );// cut out primary language
			//place this array into main $user_languages language array
			$user_languages[] = $temp_array;
		}

		//start going through each one
		for ( $i = 0; $i < count( $user_languages ); $i++ ){
			foreach ( $a_languages as $index => $complete ){
				if ( $index == $user_languages[$i][0] ){
					// complete language, like english (canada)
					$user_languages[$i][2] = $complete;
					// extract working language, like english
					$user_languages[$i][3] = substr( $complete, 0, strcspn( $complete, ' (' ) );
				}
			}
		}
	}	else {// if no languages found
		$user_languages[0] = array( '','','','' ); //return blank array.
	}

	//print_r($user_languages);
  return $user_languages;
}


function languages(){
  // pack abbreviation/language array
  // important note: you must have the default language as the last item in each major language, after all the
  // en-ca type entries, so en would be last in that case
	$a_languages = array(
  	'af' => 'Afrikaans',
  	'sq' => 'Albanian',
  	'ar-dz' => 'Arabic (Algeria)',
  	'ar-bh' => 'Arabic (Bahrain)',
  	'ar-eg' => 'Arabic (Egypt)',
  	'ar-iq' => 'Arabic (Iraq)',
  	'ar-jo' => 'Arabic (Jordan)',
  	'ar-kw' => 'Arabic (Kuwait)',
  	'ar-lb' => 'Arabic (Lebanon)',
  	'ar-ly' => 'Arabic (libya)',
  	'ar-ma' => 'Arabic (Morocco)',
  	'ar-om' => 'Arabic (Oman)',
  	'ar-qa' => 'Arabic (Qatar)',
  	'ar-sa' => 'Arabic (Saudi Arabia)',
  	'ar-sy' => 'Arabic (Syria)',
  	'ar-tn' => 'Arabic (Tunisia)',
  	'ar-ae' => 'Arabic (U.A.E.)',
  	'ar-ye' => 'Arabic (Yemen)',
  	'ar' => 'Arabic',
  	'hy' => 'Armenian',
  	'as' => 'Assamese',
  	'az' => 'Azeri',
  	'eu' => 'Basque',
  	'be' => 'Belarusian',
  	'bn' => 'Bengali',
  	'bg' => 'Bulgarian',
  	'ca' => 'Catalan',
  	'zh-cn' => 'Chinese (China)',
  	'zh-hk' => 'Chinese (Hong Kong SAR)',
  	'zh-mo' => 'Chinese (Macau SAR)',
  	'zh-sg' => 'Chinese (Singapore)',
  	'zh-tw' => 'Chinese (Taiwan)',
  	'zh' => 'Chinese',
  	'hr' => 'Croatian',
  	'cs' => 'Czech',
  	'da' => 'Danish',
  	'div' => 'Divehi',
  	'nl-be' => 'Dutch (Belgium)',
  	'nl' => 'Dutch (Netherlands)',
  	'en-au' => 'English (Australia)',
  	'en-bz' => 'English (Belize)',
  	'en-ca' => 'English (Canada)',
  	'en-ie' => 'English (Ireland)',
  	'en-jm' => 'English (Jamaica)',
  	'en-nz' => 'English (New Zealand)',
  	'en-ph' => 'English (Philippines)',
  	'en-za' => 'English (South Africa)',
  	'en-tt' => 'English (Trinidad)',
  	'en-gb' => 'English (United Kingdom)',
  	'en-us' => 'English (United States)',
  	'en-zw' => 'English (Zimbabwe)',
  	'en' => 'English',
  	'us' => 'English (United States)',
  	'et' => 'Estonian',
  	'fo' => 'Faeroese',
  	'fa' => 'Farsi',
  	'fi' => 'Finnish',
  	'fr-be' => 'French (Belgium)',
  	'fr-ca' => 'French (Canada)',
  	'fr-lu' => 'French (Luxembourg)',
  	'fr-mc' => 'French (Monaco)',
  	'fr-ch' => 'French (Switzerland)',
  	'fr' => 'French (France)',
  	'mk' => 'FYRO Macedonian',
  	'gd' => 'Gaelic',
  	'ka' => 'Georgian',
  	'de-at' => 'German (Austria)',
  	'de-li' => 'German (Liechtenstein)',
  	'de-lu' => 'German (Luxembourg)',
  	'de-ch' => 'German (Switzerland)',
  	'de' => 'German (Germany)',
  	'el' => 'Greek',
  	'gu' => 'Gujarati',
  	'he' => 'Hebrew',
  	'hi' => 'Hindi',
  	'hu' => 'Hungarian',
  	'is' => 'Icelandic',
  	'id' => 'Indonesian',
  	'it-ch' => 'Italian (Switzerland)',
  	'it' => 'Italian (Italy)',
  	'ja' => 'Japanese',
  	'kn' => 'Kannada',
  	'kk' => 'Kazakh',
  	'kok' => 'Konkani',
  	'ko' => 'Korean',
  	'kz' => 'Kyrgyz',
  	'lv' => 'Latvian',
  	'lt' => 'Lithuanian',
  	'ms' => 'Malay',
  	'ml' => 'Malayalam',
  	'mt' => 'Maltese',
  	'mr' => 'Marathi',
  	'mn' => 'Mongolian (Cyrillic)',
  	'ne' => 'Nepali (India)',
  	'nb-no' => 'Norwegian (Bokmal)',
  	'nn-no' => 'Norwegian (Nynorsk)',
  	'no' => 'Norwegian (Bokmal)',
  	'or' => 'Oriya',
  	'pl' => 'Polish',
  	'pt-br' => 'Portuguese (Brazil)',
  	'pt' => 'Portuguese (Portugal)',
  	'pa' => 'Punjabi',
  	'rm' => 'Rhaeto-Romanic',
  	'ro-md' => 'Romanian (Moldova)',
  	'ro' => 'Romanian',
  	'ru-md' => 'Russian (Moldova)',
  	'ru' => 'Russian',
  	'sa' => 'Sanskrit',
  	'sr' => 'Serbian',
  	'sk' => 'Slovak',
  	'ls' => 'Slovenian',
  	'sb' => 'Sorbian',
  	'es-ar' => 'Spanish (Argentina)',
  	'es-bo' => 'Spanish (Bolivia)',
  	'es-cl' => 'Spanish (Chile)',
  	'es-co' => 'Spanish (Colombia)',
  	'es-cr' => 'Spanish (Costa Rica)',
  	'es-do' => 'Spanish (Dominican Republic)',
  	'es-ec' => 'Spanish (Ecuador)',
  	'es-sv' => 'Spanish (El Salvador)',
  	'es-gt' => 'Spanish (Guatemala)',
  	'es-hn' => 'Spanish (Honduras)',
  	'es-mx' => 'Spanish (Mexico)',
  	'es-ni' => 'Spanish (Nicaragua)',
  	'es-pa' => 'Spanish (Panama)',
  	'es-py' => 'Spanish (Paraguay)',
  	'es-pe' => 'Spanish (Peru)',
  	'es-pr' => 'Spanish (Puerto Rico)',
  	'es-us' => 'Spanish (United States)',
  	'es-uy' => 'Spanish (Uruguay)',
  	'es-ve' => 'Spanish (Venezuela)',
  	'es' => 'Spanish (Traditional Sort)',
  	'sx' => 'Sutu',
  	'sw' => 'Swahili',
  	'sv-fi' => 'Swedish (Finland)',
  	'sv' => 'Swedish',
  	'syr' => 'Syriac',
  	'ta' => 'Tamil',
  	'tt' => 'Tatar',
  	'te' => 'Telugu',
  	'th' => 'Thai',
  	'ts' => 'Tsonga',
  	'tn' => 'Tswana',
  	'tr' => 'Turkish',
  	'uk' => 'Ukrainian',
  	'ur' => 'Urdu',
  	'uz' => 'Uzbek',
  	'vi' => 'Vietnamese',
  	'xh' => 'Xhosa',
  	'yi' => 'Yiddish',
  	'zu' => 'Zulu'
  );
 	return $a_languages;
}
?>
