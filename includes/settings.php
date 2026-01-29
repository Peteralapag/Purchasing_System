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


<style>
.smnav-header {
    background-color: #1e40af; /* nice blue */
    color: #fff;
    font-size: 1.25rem;
    font-weight: 600;
    padding: 12px 20px;
    border-radius: 8px 8px 0 0;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    justify-content: space-between; /* space between title and button */
    gap: 10px;
}

.smnav-header span {
    flex: 1;
}

.add-user-btn {
    background-color: #10b981; /* green button */
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 6px 14px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background-color 0.2s;
}

.add-user-btn i {
    font-size: 1rem;
}

.add-user-btn:hover {
    background-color: #059669; /* darker green on hover */
}


</style>



<div class="smnav-header">
    <span>Role &amp; Access Management</span>
    <button id="addUserBtn" class="add-user-btn btn-sm" onclick="addusers()">
        <i class="fa fa-plus"></i> Add User
    </button>
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

    $.post("./Modules/Purchasing_System/includes/settings_data.php", { 
        limit: limit, 
        status: status
        
    }, function(data) {
        $('#smnavdata').html(data);
    });
}


function addusers(){
	
	$('#modaltitle').html("ADD SUPPLIER");
	$.post("./Modules/Purchasing_System/apps/settings_add.php", { },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});


}

</script>
