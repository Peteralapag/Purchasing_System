<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$po_id = $_POST['po_id'] ?? 0;
if (!$po_id) {
    echo 'Error: PO ID is required.';
    exit;
}

// Fetch PO info
$stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id=?");
$stmt->bind_param("i", $po_id);
$stmt->execute();
$po_result = $stmt->get_result();
$po = $po_result->fetch_assoc();
$stmt->close();

// Fetch PR list (approved only)
$prs_result = $db->query("SELECT pr_number FROM purchase_request WHERE status='approved' ORDER BY pr_number DESC");
$prs = $prs_result ? $prs_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch suppliers (active only)
$sup_result = $db->query("SELECT id, name FROM suppliers WHERE status = 1 ORDER BY name ASC");
$suppliers = $sup_result ? $sup_result->fetch_all(MYSQLI_ASSOC) : [];

// Check if PO is still Pending
$is_pending = strtoupper($po['status']) === 'PENDING';
?>

<style>
.form-wrapper { width: 900px; max-height: 80vh; overflow-y: auto; padding: 15px; }
.form-wrapper .mb-2 { margin-bottom: 10px; }
.form-wrapper label { font-weight: bold; }
.results { font-size: 12px; margin-top: 10px; }
</style>

<div class="form-wrapper">

    <h5><i class="fa fa-file-text"></i> Edit Purchase Order</h5>
    <hr>

    <input type="hidden" name="po_id" value="<?= $po['id'] ?>">

    <div class="mb-2">
        <label>Purchase Request (PR Number)</label>
        
		<select id="pr_id" name="pr_id" class="form-control" <?= !$is_pending ? 'disabled' : '' ?>>
		    <option value="">-- Select PR --</option>
		    <?php foreach($prs as $pr): ?>
		        <option value="<?= $pr['pr_number'] ?>" <?= $pr['pr_number'] == $po['pr_number'] ? 'selected' : '' ?>>
		            <?= $pr['pr_number'] ?>
		        </option>
		    <?php endforeach; ?>
		</select>
        

    </div>

    <div class="mb-2">
        <label>Supplier</label>
        <select id="supplier_id" name="supplier_id" class="form-control" <?= !$is_pending ? 'disabled' : '' ?>>
            <option value="">-- Select Supplier --</option>
            <?php foreach($suppliers as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $s['id'] == $po['supplier_id'] ? 'selected' : '' ?>>
                    <?= $s['name'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-2">
        <label>PO Date</label>
        <input type="date" name="po_date" class="form-control" value="<?= $po['order_date'] ?>" <?= !$is_pending ? 'readonly' : '' ?>>
    </div>

    <div class="mb-2">
        <label>Remarks</label>
        <textarea name="remarks" class="form-control"><?= htmlspecialchars($po['remarks'] ?? '') ?></textarea>
    </div>

    <div style="float:right; margin-top:10px">
        <button type="button" class="btn btn-primary" onclick="updatePO()">
            <i class="fa fa-save"></i> Update PO
        </button>
    </div>

</div>

<div class="results"></div>

<script>

function updatePO() {
    var mode = 'editpo';
    var formData = $('.form-wrapper input, .form-wrapper select, .form-wrapper textarea').serialize();
    formData += '&mode=' + mode;

    $.post("./Modules/Purchasing_System/actions/actions.php", formData, function(data){
        if(data.includes("PO updated successfully")) {
            swal("System Message","Purchase Order updated successfully!","success");
            $('#formmodal').hide();
            openMenuGranted('purchase_order'); // reload table
        } else {
            swal("System Message", data, "warning");
        }
    }).fail(function(){
        swal("Error", "Request failed. Please try again.", "error");
    });
}


</script>
