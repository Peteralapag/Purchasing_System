<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// fetch PR list
$prs = $db->query("
    SELECT pr_number, destination_branch 
    FROM purchase_request 
    WHERE status IN ('canvassing_approved', 'partial_conversion') 
    ORDER BY pr_number DESC
");

$branches = $db->query("SELECT branch FROM tbl_branch");
?>

<style>
.form-wrapper { width: 900px; max-height: 80vh; overflow-y: auto; padding: 15px; }
.form-wrapper .mb-2 { margin-bottom: 10px; }
.form-wrapper label { font-weight: bold; }
.results { font-size: 12px; margin-top: 10px; }
</style>

<div class="form-wrapper">

    <h5><i class="fa fa-file-text"></i> Create Purchase Order</h5>
    <hr>
	
	<!-- PR Remarks -->
	<div class="mb-2" id="prRemarksDiv" style="display:none">
	    <label>PR Remarks</label>
	    <textarea id="pr_remarks" class="form-control" readonly></textarea>
	</div>
	
	<!-- Supplier Remarks -->
	<div class="mb-2" id="supplierRemarksDiv" style="display:none">
	    <label>Supplier Remarks</label>
	    <textarea id="supplier_remarks" class="form-control" readonly></textarea>
	</div>
	
    <div class="mb-2">
        <label>Purchase Request (PR Number)</label>
        <select id="pr_id" name="pr_id" class="form-control">
            <option value="">-- Select PR --</option>
            <?php while($pr = $prs->fetch_assoc()): ?>
                <option 
                    value="<?= $pr['pr_number'] ?>"
                    data-destination="<?= htmlspecialchars($pr['destination_branch']) ?>"
                >
                    <?= $pr['pr_number'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="mb-2">
	    <label>Supplier</label>
	    <select id="supplier_id" name="supplier_id" class="form-control">
	        <option value="">-- Select Supplier --</option>
	    </select>
	</div>
	
	<div class="mb-2">
	    <label>Ship To:</label>
	    <input type="text" id="ship_to_display" class="form-control" readonly>
	    <input type="hidden" name="branch" id="ship_to">
	</div>	
		
    <!-- PO DATE -->
    <div class="mb-2">
        <label>PO Date</label>
        <input type="date" name="po_date" class="form-control" value="<?= date('Y-m-d') ?>">
    </div>

	<!-- EXPECTED DATE -->
    <div class="mb-2">
        <label>Expected Date</label>
        <input type="date" name="expected_date" class="form-control" value="<?= date('Y-m-d') ?>">
    </div>

    <!-- REMARKS -->
    <div class="mb-2">
        <label>Remarks</label>
        <textarea name="remarks" class="form-control"></textarea>
    </div>

    <div style="float:right; margin-top:10px">
        <button type="button" class="btn btn-success" onclick="savePO()">
            <i class="fa fa-save"></i> Create PO
        </button>
    </div>

</div>

<div class="results"></div>

<script>

// ON PR SELECT
$('#pr_id').on('change', function() {

    var pr_no = $(this).val();
    var destination = $('#pr_id option:selected').data('destination') ?? '';

    if(!pr_no){
        $('#prRemarksDiv').hide();
        $('#pr_remarks').val('');
        $('#supplier_id').html('<option value="">-- Select Supplier --</option>');
        $('#supplierRemarksDiv').hide();
        $('#supplier_remarks').val('');
        $('#ship_to_display').val('');
        $('#ship_to').val('');
        return;
    }

    // AUTO FILL SHIP TO
    $('#ship_to_display').val(destination);
    $('#ship_to').val(destination);

    // SHOW PR REMARKS
    $('#prRemarksDiv').show();
    $.getJSON('./Modules/Purchasing_System/actions/get_pr_remarks.php', { pr_no: pr_no }, function(res){
        $('#pr_remarks').val(res.remarks ?? '');
    });

    // GET SUPPLIERS BY PR (NO PO YET)
    $.getJSON('./Modules/Purchasing_System/actions/get_suppliers_by_pr.php', { pr_no: pr_no }, function(data){
        var options = '<option value="">-- Select Supplier --</option>';
        data.forEach(function(s){
            options += `<option value="${s.supplier_id}" data-remarks="${s.remarks ?? ''}">
                            ${s.supplier_name}
                        </option>`;
        });
        $('#supplier_id').html(options);
    });

    $('#supplierRemarksDiv').hide();
    $('#supplier_remarks').val('');
});


// ON SUPPLIER SELECT
$('#supplier_id').on('change', function() {
    var remarks = $(this).find(':selected').data('remarks') ?? '';

    if(remarks){
        $('#supplierRemarksDiv').show();
        $('#supplier_remarks').val(remarks);
    } else {
        $('#supplierRemarksDiv').hide();
        $('#supplier_remarks').val('');
    }
});


// SAVE PO
function savePO() {

    var mode = 'savepo';
    var formData = $('.form-wrapper input, .form-wrapper select, .form-wrapper textarea').serialize();
    formData += '&mode=' + mode;

    $.post("./Modules/Purchasing_System/actions/actions.php", formData, function(data){

        if(data.includes("PO created successfully")) {

            swal("System Message","Purchase Order created successfully!","success");
            $('#formmodal').hide();
            openMenuGranted('purchase_order');

        } else {
            swal("System Message", data, "warning");
        }

    }).fail(function(){
        swal("Error", "Request failed. Please try again.", "error");
    });
}

</script>
