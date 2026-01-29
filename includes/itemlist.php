<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Purchasing_System/class/Class.functions.php";
$function = new WMSFunctions;
$currentMonthDays = date('t');
$date_from = date("Y-m-01");
$date_to = date("Y-m-".$currentMonthDays);
$function = new WMSFunctions;
$category = '';
if(isset($_SESSION['PURCH_SHOW_LIMIT']))
{
	$show_limit = $_SESSION['PURCH_SHOW_LIMIT'];
} else {
	$show_limit = '';
}
if(isset($_SESSION['PCS_ITEM_CATEGORY']) && $_SESSION['PCS_ITEM_CATEGORY'] != '')
{
	$category = $_SESSION['PCS_ITEM_CATEGORY'];
}
?>
<div class="smnav-header">
	<div class="search-shell">
		<input id="search" type="text" class="form-control form-control-sm" placeholder="Search Property" autocomplete="nope">	
		<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
		<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
	</div>
	<div class="search-shell">
		<select id="category" class="form-control form-control-sm">
			<?php echo $function->GetItemCategory($category,$db)?>
		</select>
	</div>
	<div class="search-shell" style="margin-left:10px">
		<button class="btn btn-primary btn-sm" onclick="addNewItem('new')"><i class="fa-solid fa-plus"></i> &nbsp;Add New Item</button>
	</div>
	<span class="reload-data">
		<span style="margin-left:20px;margin-top:4px;">Show</span>
		<select id="limit" style="width:70px" class="form-control form-control-sm" onchange="load_data()">
			<?php echo $function->GetRowLimit($show_limit); ?>
		</select>
	</span>
</div>
<div class="DatatableFixHead " id="smnavdata">Loading... <i class="fa fa-spinner fa-spin"></i></div>
<script>
function addNewItem(task)
{
	$('#modaltitle').html("Add New Item");
	$.post("./Modules/Purchasing_System/apps/form_itemlist.php", { task: task },
	function(data) {
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
$(document).ready(function()
{
	$('#category').change(function()
	{
		var limit = $('#limit').val();
		var category = $('#category').val();
		var search = $('#search').val();
		rms_reloaderOn();
		$.post("./Modules/Purchasing_System/includes/itemlist_data.php", { limit: limit, search: search, category: category },
		function(data) {
			$('#smnavdata').html(data);
			rms_reloaderOff();
		});
	});
	$('#search').keyup(function()
	{
		if($('#category').val() != '')
		{
			var limit = '';
			var search = $('#search').val();
			var category = $('#category').val();
		} else {
			var limit = '';
			var search = $('#search').val();
			var category = '';
		}
		$.post("./Modules/Purchasing_System/includes/itemlist_data.php", { limit: limit, search: search, category: category },
		function(data) {
			$('#smnavdata').html(data);
		});

	});
	load_data();
});
function load_data()
{
	var limit = $('#limit').val();
	var search = $('#search').val();
	var category = $('#category').val();
	rms_reloaderOn();
	$.post("./Modules/Purchasing_System/includes/itemlist_data.php", { limit: limit, search: search, category: category },
	function(data) {
		$('#smnavdata').html(data);
		rms_reloaderOff();
	});
}
function clearSearch()
{
	$('#search').val('');
	reload_data();
}
</script>