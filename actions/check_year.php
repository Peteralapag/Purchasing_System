<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Purchasing_System/class/Class.functions.php";
$function = new WMSFunctions;

$id = 1; // Set the value of the parameter
$queryYear = "SELECT curr_year FROM pcs_form_numbering WHERE id=?";
$stmt = $db->prepare($queryYear);
$stmt->bind_param('i', $id); // Assuming 'id' is an integer
$stmt->execute();
$stmt->bind_result($curr_year);
$stmt->fetch();
$stmt->close();		

$get_year = $_POST['getYear'];

if($curr_year != $get_year)
{
	$tag = $get_year."-100001";
	$ptf = $get_year."-200001";
	$acct = $get_year."-300001";	
	$update = "`curr_year`='$get_year',`tag_number`='$tag',`ptf_number`='$ptf',`accountability_number`='$acct'";
	
	$queryDataUpdateNumbering = "UPDATE pcs_form_numbering SET $update WHERE id=1";
	if ($db->query($queryDataUpdateNumbering) === TRUE) { } else { echo $db->error;}
}


/* ################################################### */
/* ITONG PART NATO IS NAG CHE CHECK NG YEAR TAPOS      */
/* I RE RESET NYA ANG YEAR AT MGA NUMBERINGS BACK TO   */
/* ZERO (1) EX; 2025-000001 ########################## */
/* ################################################### */