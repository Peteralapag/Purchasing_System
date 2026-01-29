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

$loadStatus = [
    'approved',
    'rejected',
    'for_canvassing',
    'canvassing_reviewed',
    'canvassing_approved',
    'partial_conversion',
    'converted',
    'convert_rejected'
];

?>


<div class="smnav-header">
    <span style="display:flex;gap:10px">
        <div class="search-shell">
            <input id="search" type="text" class="form-control form-control-sm" placeholder="Search purchase request">    
            <i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
            <i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
        </div>
    </span>

    <span style="margin-left:10px;margin-top:4px;">Status</span>
    <select id="statusFilter" class="form-control form-control-sm" onchange="load_data()">
        <option value="">-- Select PR Status --</option>
        <?php foreach ($loadStatus as $status): ?>
	        <option value="<?php echo $status; ?>">
	            <?php echo ucwords(str_replace('_', ' ', $status)); ?>
	        </option>
	    <?php endforeach; ?>
        
    </select>

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
    // Live search filter
    $('#search').keyup(function() {
        let filter = this.value.toLowerCase();
        $('#purchaserequesttable tbody tr').each(function() {
            let text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(filter));
        });
    });

    load_data();
});

function load_status(params){

}

function clearSearch() {
    $('#search').val('');
    load_data();
}

function load_data() {
    var limit = $('#limit').val();
    var status = $('#statusFilter').val();

    $.post("./Modules/Purchasing_System/includes/purchase_request_data.php", { 
        limit: limit, 
        status: status
    }, function(data) {
        $('#smnavdata').html(data);
    });
}
</script>
