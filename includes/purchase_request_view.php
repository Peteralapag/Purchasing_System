<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    
require $_SERVER['DOCUMENT_ROOT']."/Modules/Purchasing_System/class/Class.functions.php";
$function = new WMSFunctions;

$prnumber = $_POST['prnumber'] ?? '';
$status  = $_POST['status'] ?? '';
?>

<style>
.pr-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.pr-header h4 {
    margin: 0;
}
.smnav-header input[type=text] {width:100%;padding-left: 25px;padding-right:27px;}
.reload-data {display: flex;gap: 15px;margin-left: auto;right:0;}
.tableFixHead {margin-top:15px;background:#fff;}
.tableFixHead { overflow: auto; height: calc(100vh - 250px); width:100%; }
.tableFixHead thead th { position: sticky; top: 0; z-index: 1; background:#0cccae; color:#fff; }
.tableFixHead table  { border-collapse: collapse; }
.tableFixHead th, .tableFixHead td { font-size:14px; white-space:nowrap; }
</style>

<div class="pr-header">
    <div>
        <h4><i class="fa fa-clipboard"></i> Purchase Request: <strong><?= htmlspecialchars($prnumber) ?></strong></h4>
    </div>
    <div>
    
	<?php
		if($status === 'approved'){
	?>
	
        <button class="btn btn-warning btn-sm" onclick="returnpr('<?= $prnumber?>')">
            <i class="fa fa-arrow-turn-up"></i> Return PR
        </button>
        
		<button class="btn btn-secondary btn-sm" onclick="canvassing('<?= $prnumber?>')">
            <i class="fa fa-paper-plane"></i> Push to Canvassing
        </button>

	<?php
		} else {
	?>
		<span class="text-center text-muted">
            <i class="fa fa-info-circle"></i> Not available
        </span>

	<?php
		}
	?>
		<button class="btn btn-primary btn-sm" onclick="bactomain()">
            <i class="fa fa-arrow-left"></i> Back to Main
        </button>

    </div>
</div>

<div class="smnav-header mb-2">
    <div class="search-shell" style="width:300px;">
        <input id="search" type="text" class="form-control form-control-sm" placeholder="Search Item">    
        <i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
        <i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
    </div>
</div>

<div class="tableFixHead" id="smnavdata">Loading... <i class="fa fa-spinner fa-spin"></i></div>

<script>


function returnpr(prnumber){
    swal({
        title: 'Return Purchase Request',
        text: 'Please provide a reason for returning this PR:',
        content: {
            element: "input",
            attributes: {
                placeholder: "Enter reason...",
                type: "text",
            },
        },
        buttons: ["Cancel", "Return"],
        dangerMode: true
    }).then((remarks) => {
        if(!remarks) return; // cancelled or empty

        $.post(
            "./Modules/Purchasing_System/actions/actions.php",
            {
                mode: "returntopr",
                prnumber: prnumber,
                remarks: remarks
            },
            function(resp){
                try{
                    let data = JSON.parse(resp);
                    if(data.status === 'success'){
                        swal("Success", data.message, "success");
                        vieviapr(prnumber,'');
                    } else {
                        swal("Error", data.message, "error");
                    }
                } catch(e){
                    console.error(e);
                    swal("Error", "Unexpected response", "error");
                }
            }
        );
    });
}


function canvassing(prnumber){

    swal({
        title: 'Push to Canvassing?',
        text: 'This Purchase Request will be forwarded.',
        icon: 'warning',
        buttons: [
            'No',
            'Yes'
        ],
        dangerMode: true
    }).then(function(isConfirm){

        if(isConfirm){

            $.post(
                "./Modules/Purchasing_System/actions/actions.php",
                {
                    mode: "savecanvassing",
                    prnumber: prnumber
                },
                function(data){

                    $('#smnavdata').html(data);
                    swal("Success!", "Purchase Request pushed to Canvassing.", "success");
                    vieviapr(prnumber,'');
                }
            );
        }
    });
}


function bactomain(){
    $('#contents').load('./Modules/Purchasing_System/includes/purchase_request.php');
}

$(function() {
    $('#search').keyup(function() {
        let filter = this.value.toLowerCase();
        $('#itemsTable tbody tr').each(function() {
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
    var prnumber = '<?= $prnumber ?>';
    var status = '<?= $status ?>';
    $.post("./Modules/Purchasing_System/apps/purchase_request_view_form.php", { prnumber: prnumber, status: status },
    function(data) {
        $('#smnavdata').html(data);
    });
}
</script>
