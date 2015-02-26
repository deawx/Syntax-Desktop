<?php
function smarty_function_documents($params, &$smarty) {
  global $db, $synPublicPath, $synAbsolutePath;
  
  if(!isset($_SESSION))
    session_start();

  $cat       = isset($params['cat']) ? $params['cat'] : '';
  $userid    = isset($_COOKIE[COOKIE_NAME]['id'])    ? $_COOKIE[COOKIE_NAME]['id']    : 0;
  $usergroup = isset($_COOKIE[COOKIE_NAME]['group']) ? $_COOKIE[COOKIE_NAME]['group'] : 0;
  $lng       = $_SESSION['synSiteLangInitial'];
  $document_count = 0;

  if($usergroup != "") {
    $usergroup = explode('|', $usergroup);
  } else 
    $usergroup = array();

  $qry = <<<EOQ
   SELECT d.id, d.file, d.date, d.status, d.enabled_groups, d.category_id,
          t1.{$lng} AS title, t2.{$lng} AS category, t3.{$lng} AS description
     FROM documents d
     JOIN categories      c ON d.category_id = c.id
LEFT JOIN aa_translation t1 ON d.title = t1.id
LEFT JOIN aa_translation t2 ON c.category = t2.id
LEFT JOIN aa_translation t3 ON d.description = t3.id
 ORDER BY c.`order`, 
          d.`date` DESC, 
          d.title
EOQ;

  //$pgr = new synPager($db, '', '', '', false, true);
  //$res = $pgr->Execute($qry, 8, "cat=".$cat);
  //$nav = $pgr->renderPagerPublic('', true, true);
  
  $documents = array();
  $res = $db->execute($qry);

  if($arr=$res->FetchRow()) {
    $t = multiTranslateDictionary(array('doc_riservato','doc_no_abilitazione'));
    do {
      $documents[$arr['category_id']] = array('name' => $arr['category'], 'documents' => array());
      do {
        $ext        = $arr['file'];
        $file       = "{$synPublicPath}/mat/documents/documents_file_id{$arr['id']}.{$ext}";
        $size       = byteConvert(@filesize($synAbsolutePath.$file));
        $date       = sql2human($arr['date']);
        $status     = $arr['status'];

        if ($arr['enabled_groups'])
          $owner = explode('|', $arr['enabled_groups']);
        else 
          $owner = array();

        switch(strtolower($ext)) {
          case "xlsx" :
          case "xls" :
            $class= "xls"; break;
          case "pdf" :
            $class= "pdf"; break;
          case "zip" :
            $class= "zip"; break;
          default :
            $class= "pdf"; break;
        }
        
        $intersect = array_intersect($usergroup, $owner);
        
        if (is_array($intersect) && count($intersect) > 0) 
          $have_same_group = true;
        else 
          $have_same_group = false;
        
        if (
            ($status == 'secret' && ($have_same_group && $userid != '') ) ||
            ($status == 'private' && $userid != '' ) ||
            ($status == 'public') ||
            ($status == 'protected')
          ){
          // file pubblico o autorizzato per l'utente o visibile
          if(
            ($status == 'public') ||
            ($status == 'protected' && $userid != '') ||
            (($status == 'private' || $status == 'secret') && ($have_same_group && $userid != '') )
            ){
            // file pubblico o autorizzato per l'utente
            $link  = $file;
          } else {
            // file privato o non autorizzato per l'utente
            $alert = ($userid ? $t['doc_no_abilitazione'] : $t['doc_riservato']);
            $link  = "javascript:alert('{$alert}')";
          }
          $document_count++;
          $documents[$arr['category_id']]['documents'][] = array(
            'link'        => $link, 
            'title'       => $arr['title'], 
            'description' => $arr['description'], 
            'ext'         => $ext, 
            'size'        => $size,
            'date'        => $date,
            'class'       => $class
          ); 
        }
        $next = ($arr=$res->FetchRow());
      } while ($next && $catid==$arr['category_id']);

    } while ($next);
    
  }
  $documents['document_count'] = $document_count;
  $smarty->assign('documents', $documents);
}

// EOF