<?php
chdir(__DIR__);
require_once "config.inc.php";
require_once "include/utils/utils.php";
require_once "includes/Loader.php";
vimport("includes.runtime.EntryPoint");
session_start();
if (empty($_SESSION["authenticated_user_id"])) {
  // bootstrap as admin without full login session for debug only
}
header("Content-Type: text/plain; charset=utf-8");
global $current_user;
$current_user = CRMEntity::getInstance("Users");
$current_user->retrieve_entity_info(1, "Users");
$current_user = Users_Record_Model::getInstanceFromUserObject($current_user);

$req = new Vtiger_Request(array(
  "module"=>"ProductsServices",
  "view"=>"List",
  "app"=>"INVENTORY",
  "viewname"=>"55"
), array());
$view = new ProductsServices_List_View();
$viewer = $view->getViewer($req);
// mimic getInstance with empty then session-style bad headers
$model = Vtiger_ListView_Model::getInstance("ProductsServices", 55, array("item_type","price","wholesale_price","warranty"));
$view->listViewModel = $model;
$model->forceProductNameColumn();
$headers = $model->getListViewHeaders();
echo "OK headers=".implode(",", array_keys($headers))."\n";
echo "nameField=".(isset($headers["productsservicesname"])?"yes":"no")."\n";
@unlink(__FILE__);
