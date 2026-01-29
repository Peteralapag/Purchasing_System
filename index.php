<?php
session_start();
define("MODULE_NAME", "Purchasing System");
if(!isset($_SESSION['purch_username'])) { 
?>
<script>
$(function()
{
	$('#modaltitle').html("Property Custodian System Login");
	$('#modalicon').html('<i class="fa-solid fa-user color-dodger"></i>');	
	$.post("../Modules/Purchasing_System/apps/login.php", { },
	function(data) {
		$('#formmodal_page').html(data);		
		$('#formmodal').show();		
	});
});
</script>	
<?php exit(); } ?>
<link rel="stylesheet" href="../Modules/Purchasing_System/styles/styles.css">
<script src="../Modules/Purchasing_System/scripts/script.js"></script>
<!-- @@@@@@@@@@ ################### @@@@@@@@@@@ -->
<div class="sidebar">
	<div class="logo-title" id="logotitle"></div>
	<div class="navigation" id="navigation"></div>
</div>
<div class="content-wrapper">
	<div class="contents" id="contents"></div>
</div>
<div id="getyearresults"></div>
<script>
$(function()
{
	var module = '<?php echo MODULE_NAME; ?>';
	$('#logotitle').load('../Modules/Purchasing_System/apps/logo_title.php');
	$('#navigation').load("../Modules/Purchasing_System/pages/sidebar_navigation.php");

	const currDate = new Date();
	const getYear = currDate.getFullYear();
	$.post("./Modules/Purchasing_System/actions/check_year.php", { getYear: getYear },
	function(data) {		
		$('#getyearresults').html(data);
	});

});
</script>
