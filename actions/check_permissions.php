<?php
session_start();
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$username = $_SESSION['purch_username'];
$user_level = $_SESSION['purch_userlevel'];
$permission = $_POST['permission'];
$module = $_POST['module'];

checkPolicy($username,$module,$permission,$user_level,$db);

function checkPolicy($username,$module,$permission,$user_level,$db)
{
	if($user_level >= 80)
	{
		echo 1;
	} 
	else
	{
		$checkPolicy = "SELECT * FROM tbl_system_permission WHERE username='$username' AND modules='$module' AND $permission=1";
		$pRes = mysqli_query($db, $checkPolicy);    
	    if ( $pRes->num_rows > 0 ) 
	    {
	    	echo 1;
		} else {
			echo 0;
		}
	}
}
?>