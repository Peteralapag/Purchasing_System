<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Purchasing_System/class/Class.functions.php";
$function = new WMSFunctions;

if($_POST['asset_holder'] == 'Branch')
{
	echo $function->GetBranch('',$db);
}
else if($_POST['asset_holder'] == 'Department')
{
	echo $function->GetDepartment('',$db);
}
else if($_POST['asset_holder'] == 'Individual')
{
	echo $function->GetEmployee('',$db);
}
?>