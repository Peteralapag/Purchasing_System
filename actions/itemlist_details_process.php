<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Purchasing_System/class/Class.functions.php";
$function = new WMSFunctions;

if(isset($_POST['mode']) && $_POST['mode'] != '')
{
	$mode = $_POST['mode'];
} else {
	print_r('
		<script>
			app_alert("Warning"," The Mode you are trying to pass does not exist","warning","Ok","","no");
		</script>
	');
	exit();
}
if(isset($_SESSION['purch_appnameuser']))
{
	$app_user = strtolower($_SESSION['purch_appnameuser']);
	$app_user = ucwords($app_user);
}
$date_now = date("Y-m-d H:i:s");
$time_now = date("H:i:s");
$current_date = date("Y-m-d");
if($mode == 'updatedetails')
{
	$rowid = $_POST['rowid'];
	$item_code = $_POST['item_code'];
	$date_received = $_POST['date_received'];
	$quantity = $_POST['quantity'];
	$brand_name = $_POST['brand_name'];
	$unit_description = $_POST['unit_description'];
	$supplier = $_POST['supplier'];
	$serial_number = $_POST['serial_number'];
	$po_number = $_POST['po_number'];
	$dr_number = $_POST['dr_number'];
	$si_number = $_POST['si_number'];
	$mrs_number = $_POST['mrs_number'];
	$tag_number = $_POST['tag_number'];
	$ptf_number = $_POST['ptf_number'];
	$accountability_number = $_POST['accountability_number'];
	$property_cost = $_POST['property_cost'];
	$depreciate_date = $_POST['depreciate_date'];
	$yearly_depreciation = $_POST['yearly_depreciation'];
	$depreciation_lenght = $_POST['depreciation_lenght'];
	$remarks = $_POST['remarks'];
	$status = $_POST['status'];
	$date_updated = $date_now;
	$updated_by = $app_user;

	$depreciation_amount = ($property_cost / $depreciation_lenght);

	$startDate = new DateTime($depreciate_date);
	$endDate = new DateTime($current_date);
	
	$interval = $startDate->diff($endDate);
	$number_of_years = $interval->y;
	
	$prop_cost = $property_cost;
	$max_years = min($number_of_years, $depreciation_lenght);

	for ($year = 1; $year <= $max_years; $year++) {
	    $prop_cost -= $depreciation_amount;
	    $yearly_amount = $prop_cost;
	}
	if ($number_of_years <= $depreciation_lenght) {
	    $yearly_amount = $prop_cost;
	} else {
	    $yearly_amount = 0;
	}	
	$update = "
		item_code='$item_code',date_received='$date_received',quantity='$quantity',unit_description='$unit_description',supplier='$supplier',serial_number='$serial_number',
		po_number='$po_number',dr_number='$dr_number',si_number='$si_number',mrs_number='$mrs_number',tag_number='$tag_number',ptf_number='$ptf_number',brand_name='$brand_name',
		accountability_number='$accountability_number',property_cost='$property_cost',depreciate_date='$depreciate_date',yearly_depreciation='$yearly_depreciation',
		depreciation_lenght='$depreciation_lenght',depreciation_amount='$depreciation_amount',remarks='$remarks',status='$status',date_updated='$date_updated',updated_by='$updated_by'	
	";
	$queryDataUpdate = "UPDATE pcs_item_records SET $update WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		$activity = "UPDATE::: ".$unit_description." with Item Code ".$item_code. " @ ".$time_now;
		echo $function->DoLogs($current_date,$activity,$app_user,$db);
		echo '
			<script>
				loadDetailsData("'.$item_code.'");
				var item = "'.$unit_description.' has been succesfully updated";
				swal("Successful", "The item " + item, "success");
				openUnitDetails("details","'.$rowid.'","'.$item_code.'","'.$unit_description.'");								
			</script>
		';
	} else {
		print_r('
			<script>
				swal("System Message", "'.$db->error.'", "warning");				
			</script>
		');
	}
}
if($mode == 'savedetails')
{
	$item_code = $_POST['item_code'];
	$date_received = $_POST['date_received'];
	$quantity = $_POST['quantity'];
	$brand_name = $_POST['brand_name'];
	$unit_description = $_POST['unit_description'];
	$supplier = $_POST['supplier'];
	$serial_number = $_POST['serial_number'];
	$po_number = $_POST['po_number'];
	$dr_number = $_POST['dr_number'];
	$si_number = $_POST['si_number'];
	$mrs_number = $_POST['mrs_number'];
	$tag_number = $_POST['tag_number'];
	$ptf_number = null;
	$accountability_number = null;
	$property_cost = $_POST['property_cost'];
	$depreciate_date = $_POST['depreciate_date'];
	$yearly_depreciation = $_POST['yearly_depreciation'];
	$depreciation_lenght = $_POST['depreciation_lenght'];
	$remarks = $_POST['remarks'];
	$status = $_POST['status'];
	$date_created = $date_now;
	$created_by = $app_user;

	if($function->GetCheckRecordsData($item_code,$tag_number,$db) == 1)
	{
		echo '
			<script>
				swal("Duplicate","Unit is already exists with Tag Number '.$tag_number.'", "warning");
			</script>
		';
		exit();
	}
	
	$tagnumber  = $tag_number;
	list($year, $TagNumericPart) = explode('-', $tagnumber);
	$TagNumericPart = str_pad((int)$TagNumericPart + 1, strlen($TagNumericPart), '0', STR_PAD_LEFT);	
	$new_tag_number = $year . '-' . $TagNumericPart;
	
	$ptfnumber  = $ptf_number;
	list($year, $PtfNumericPart) = explode('-', $ptfnumber);
	$PtfNumericPart = str_pad((int)$PtfNumericPart + 1, strlen($PtfNumericPart), '0', STR_PAD_LEFT);		
	$new_ptf_number = $year . '-' . $PtfNumericPart;

	$accountabilitynumber  = $accountability_number;
	list($year, $AcctNumericPart) = explode('-', $accountabilitynumber);
	$AcctNumericPart = str_pad((int)$AcctNumericPart + 1, strlen($AcctNumericPart), '0', STR_PAD_LEFT);		
	$new_accountability_number = $year . '-' .$AcctNumericPart;


	$depreciation_amount = ($property_cost / $depreciation_lenght);

	$startDate = new DateTime($depreciate_date);
	$endDate = new DateTime($current_date);
	
	$interval = $startDate->diff($endDate);
	$number_of_years = $interval->y;

	$prop_cost = $property_cost;
	$max_years = min($number_of_years, $depreciation_lenght);

	for ($year = 1; $year <= $max_years; $year++) {
	    $prop_cost -= $depreciation_amount;
	    $yearly_amount = $prop_cost;
	}
	if ($number_of_years <= $depreciation_lenght) {
	    $yearly_amount = $prop_cost;
	} else {
	    $yearly_amount = 0;
	}	
		echo $new_ptf_number;
//	exit();
	$column = "`item_code`,`date_received`,`quantity`,`unit_description`,`brand_name`,`supplier`,`serial_number`,`po_number`,`dr_number`,`si_number`,`mrs_number`,`tag_number`,`ptf_number`,`accountability_number`,`property_cost`,`depreciate_date`,`yearly_depreciation`,`depreciation_lenght`,`remarks`,`status`,`date_created`,`created_by`";	
	$insert = "'$item_code','$date_received','$quantity','$unit_description','$brand_name','$supplier','$serial_number','$po_number','$dr_number','$si_number','$mrs_number','$tag_number','$ptf_number','$accountability_number','$property_cost','$depreciate_date','$yearly_depreciation','$depreciation_lenght','$remarks','$status','$date_created','$created_by'";
	$queryInsert = "INSERT INTO pcs_item_records ($column) VALUES ($insert)";
	if ($db->query($queryInsert) === TRUE)
	{
		$rowid = $db->insert_id;
		/* ############### SAVING AUTO GENERATE NUMBERS ###################### */
		echo $function->updateNumbering('tag_number',$new_tag_number,$db);
		//echo $function->updateNumbering('ptf_number',$new_ptf_number,$db);
		//echo $function->updateNumbering('accountability_number',$new_accountability_number,$db);
		echo '
			<script>
				loadDetailsData("'.$item_code.'");
				openUnitDetails("edit","'.$rowid.'","'.$item_code.'","'.$unit_description.'");
			</script>
		';
		$activity = "INSERT::: ".$unit_description." with Item Code ".$item_code. " @ ".$time_now;
		echo $function->DoLogs($current_date,$activity,$app_user,$db);
	} else {
		print_r('
			<script>
				swal("System Message", "'.$db->error.'", "warning");
			</script>
		');
	}
}