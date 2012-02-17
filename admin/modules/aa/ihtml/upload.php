<?php
session_id($_GET["session_id"]);
include_once ("../../../config/cfg.php");
global $synAbsolutePath, $synPublicPath, $mat;

/*
 * Attenzione: cablato su servizio photos.
 * pensare una soluzione per renderlo dinamico!
 * Marco 2010-12-30
 */

$bSuccess = true;
$save_path = $synAbsolutePath.$synPublicPath.$mat.'/';

$key = intval($_GET["key"]);

$description_column_name = (trim(addslashes($_GET["description"]))!="" ? trim(addslashes($_GET["description"])) : "title");
$order_column_name = (trim(addslashes($_GET["order"]))!="" ? trim(addslashes($_GET["order"])) : "ordine");
$table = (trim(addslashes($_GET["table"]))!="" ? trim(addslashes($_GET["table"])) : "photos");
$field = (trim(addslashes($_GET["field"]))!="" ? trim(addslashes($_GET["field"])) : "photo");
$linkfield = (trim(addslashes($_GET["linkfield"]))!="" ? trim(addslashes($_GET["linkfield"])) : "album");

$qo = "SELECT `{$order_column_name}` FROM `{$table}` WHERE `{$linkfield}`='{$key}' ORDER BY `{$order_column_name}` DESC LIMIT 0,1";
$ro = $db->execute($qo);
$ao = $ro->fetchrow();
$ordine = $ao['{$order_column_name}'];
//$_SESSION['juvar.files'] = array(); // per after_upload.php

foreach ($_FILES as $file => $fileArray) {
	echo("File key: $file\n");

  $ordine += 10;

	$name = explode('/',$fileArray['name']);
  $filename = $name[count($name)-1];
  $ext = strtolower(substr(strrchr($filename, '.'), 1));
  $qry = "INSERT INTO {$table} (`{$description_column_name}`,`{$field}`,`{$linkfield}`,`{$order_column_name}`) VALUES ('".substr($filename,0,-4)."','$ext',".$key.",'$ordine')";
  $res = $db->Execute($qry);
  $id = $db->Insert_ID();
  $newFilename = "{$table}_{$field}_id".$id.".".$ext;

  try {
    move_uploaded_file($fileArray['tmp_name'], $save_path.$newFilename);
    $bSuccess += true;
/*
    # variabili di sessione per riepilogo in after_upload.php
    $_SESSION['juvar.files'][$file] = array(
      'name' => $fileArray['name'],
      'new name' => $newFilename,
      'size' => $fileArray['size'],
      'type' => $fileArray['type']
    );
*/

  } catch (Exception $e) {
    $error .= $e->getMessage();
    $bSuccess += false;
  }
}

//Let's say to the applet that it's a success or a failure:
echo("\n");
if ($bSuccess) {
	echo "SUCCESS\n";
} else {
	echo "ERROR: $error\n";
}
echo "<br>End of upload.php script\n";
?>
