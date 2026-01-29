<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Purchasing_System/class/Class.functions.php";
$function = new WMSFunctions;
$date_from = date("Y-m-01");
$function = new WMSFunctions;
$category = '';

if(isset($_SESSION['PURCH_SHOW_LIMIT']))
{
	$show_limit = $_SESSION['PURCH_SHOW_LIMIT'];
} else {
	$show_limit = '50';
}
?>


<div class="smnav-header">
    <span style="display:flex;gap:10px">
        <div class="search-shell">
            <input id="search" type="text" class="form-control form-control-sm" placeholder="Search suppliers">    
            <i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
            <i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
        </div>
    </span>
   	<button type="button" class="btn btn-success btn-sm" onclick="addsupplier()"><i class="fas fa-plus"></i> Add Supplier</button>
   	
   	
    <span class="reload-data">
        <span style="margin-left:20px;margin-top:4px;">Show</span>
        <select id="limit" style="width:70px" class="form-control form-control-sm" onchange="load_data()">
            <?php echo $function->GetRowLimit($show_limit); ?>
        </select>
    </span>
</div>

<div class="tableFixHead" id="smnavdata">Loading... <i class="fa fa-spinner fa-spin"></i></div>

<script>
$(function() {
    $('#search').keyup(function() {
        let filter = this.value.toLowerCase();
        $('#suppliertable tbody tr').each(function() {
            let text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(filter));
        });
    });

    load_data();
});

function clearSearch() {
    $('#search').val('');
    load_data();
}

function load_data() {
    var limit = $('#limit').val();
    var status = $('#statusFilter').val();
    var source = $('#sourceFilter').val(); // new source filter

    $.post("./Modules/Purchasing_System/includes/supplier_data.php", { 
        limit: limit, 
        status: status,
        source: source
    }, function(data) {
        $('#smnavdata').html(data);
    });
}
function addsupplier(){
	
	$('#modaltitle').html("ADD SUPPLIER");
	$.post("./Modules/Purchasing_System/apps/supplier_add.php", { },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});


}
</script>
