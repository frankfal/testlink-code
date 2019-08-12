<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Display list of test projects
 *
 * @package 	  TestLink
 * @author 		  TestLink community
 * @copyright   2007-2019, TestLink community 
 * @filesource  projectView.php
 * @link 		    http://www.testlink.org/
 *
 */


require_once('../../config.inc.php');
require_once("common.php");
testlinkInitPage($db,false,false,"checkRights");

$templateCfg = templateConfiguration();
$args = init_args();
list($gui,$smarty) = initializeGui($db,$args);

$template2launch = $templateCfg->default_template;
if(!is_null($gui->tprojects) || $args->doAction=='list') {  
  if( $gui->itemQty == 0 ) {
    $template2launch = "projectEdit.tpl"; 
    $gui->doAction = "create";
  } 
}

$smarty->assign('gui',$gui);
$smarty->display($templateCfg->template_dir . $template2launch);


/**
 * 
 *
 */
function init_args() {
  $_REQUEST = strings_stripSlashes($_REQUEST);
   
  $args = new stdClass();
 
  //$sk = 'testprojectID';
  //$args->ctx_tproject_id = isset($_SESSION[$sk]) ? $_SESSION[$sk] : 0;
  //$args->ctx_tproject_id = intval($args->ctx_tproject_id);
  $args->tproject_id = isset($_REQUEST['tproject_id']) ? $_REQUEST['tproject_id'] : 0;

  $args->tplan_id = isset($_REQUEST['tplan_id']) ? $_REQUEST['tplan_id'] : 0;

  $args->userID = isset($_SESSION['userID']) ? intval($_SESSION['userID']) : 0;
  $args->user = isset($_SESSION['currentUser']) ? $_SESSION['currentUser'] : null; 


 
  $args->doAction = isset($_REQUEST['doAction']) ? $_REQUEST['doAction'] : 'list' ;
  $args->name = isset($_REQUEST['name']) ? trim($_REQUEST['name']) : null ;
  if(!is_null($args->name)) {
    $args->name = trim($args->name); 
    if(strlen($args->name) == 0) {  
      $args->name = null;
    } else {
      $args->name = substr($args->name,0,100);
    }  
  } 



  return $args;  
}

/**
 * 
 *
 */
function initializeGui(&$dbHandler,&$argsObj) {

  $tplEngine = new TLSmarty();

  $guiObj = new stdClass();
  $guiObj->doAction = $argsObj->doAction;
  $guiObj->canManage = $argsObj->user->hasRight($dbHandler,"mgt_modify_product");
  $guiObj->name = is_null($argsObj->name) ? '' : $argsObj->name;
  $guiObj->feedback = '';
  
  switch($argsObj->doAction) {
    case 'list':
      $filters = null;
    break;

    case 'search':
    default:
      $filters = array('name' => array('op' => 'like', 'value' => $argsObj->name));
      $guiObj->feedback = lang_get('no_records_found');
    break;
  }

  $tproject_mgr = new testproject($dbHandler);
  $opt = array('output' => 'array_of_map', 'order_by' => " ORDER BY name ", 
               'add_issuetracker' => true,
               'add_codetracker' => true, 'add_reqmgrsystem' => true);
  $guiObj->tprojects = $tproject_mgr->get_accessible_for_user($argsObj->userID,$opt,$filters);
  $guiObj->pageTitle = lang_get('title_testproject_management');
  
  $cfg = getWebEditorCfg('testproject');
  $guiObj->editorType = $cfg['type'];

  $guiObj->itemQty = count($guiObj->tprojects);

  if($guiObj->itemQty > 0) {
    $guiObj->pageTitle .= ' ' . sprintf(lang_get('available_test_projects'),$guiObj->itemQty);
 
    initIntegrations($guiObj->tprojects,$guiObj->itemQty,$tplEngine);
  }  


  $guiObj->actions = $tproject_mgr->getViewActions($ctx);
  return array($guiObj,$tplEngine);
}

/**
 *
 */
function initIntegrations(&$tprojSet,$tprojQty,&$tplEngine) {
  $labels = init_labels(array('active_integration' => null, 
                              'inactive_integration' => null));

  $imgSet = $tplEngine->getImages();

  $intk = array('it' => 'issue', 'ct' => 'code');
  for($idx=0; $idx < $tprojQty; $idx++) {  
    foreach( $intk as $short => $item ) {
      $tprojSet[$idx][$short . 'statusImg'] = '';
      if($tprojSet[$idx][$short . 'name'] != '') {
        $ak = ($tprojSet[$idx][$item . '_tracker_enabled']) ? 
              'active' : 'inactive';
        $tprojSet[$idx][$short . 'statusImg'] = 
          ' <img title="' . $labels[$ak . '_integration'] . '" ' .
          ' alt="' . $labels[$ak . '_integration'] . '" ' .
          ' src="' . $imgSet[$ak] . '"/>';
      } 
    }
  }
}  


/**
 *
 */
function checkRights(&$db,&$user) {
	return $user->hasRight($db,'mgt_modify_product');
}
