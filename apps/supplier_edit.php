<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$id = $_POST['params'] ?? 0;
$query = $db->prepare("SELECT * FROM suppliers WHERE id=?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();
$s = $result->fetch_assoc();
?>

<style>
.form-wrapper { width: 900px; max-height: 80vh; overflow-y: auto; padding: 15px; }
.form-wrapper .mb-2 { margin-bottom: 10px; }
.form-wrapper label { font-weight: bold; }
.results { font-size: 12px; margin-top: 10px; }
</style>

<div class="form-wrapper">
    <input type="hidden" name="id" value="<?= $s['id'] ?>">

    <div class="mb-2">
        <label>Supplier Code</label>
        <input type="text" name="supplier_code" class="form-control" value="<?= htmlspecialchars($s['supplier_code']) ?>">
    </div>
    <div class="mb-2">
        <label>Name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($s['name']) ?>">
    </div>
    <div class="mb-2">
        <label>Address</label>
        <textarea name="address" class="form-control"><?= htmlspecialchars($s['address']) ?></textarea>
    </div>
    <div class="mb-2">
        <label>TIN</label>
        <input type="text" name="tin" class="form-control" value="<?= htmlspecialchars($s['tin']) ?>">
    </div>
    <div class="mb-2">
        <label>Payment Terms</label>
        <input type="text" name="payment_terms" class="form-control" value="<?= htmlspecialchars($s['payment_terms']) ?>">
    </div>
    <div class="mb-2">
        <label>Contact Person</label>
        <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($s['contact_person']) ?>">
    </div>
    <div class="mb-2">
        <label>Phone / Mobile</label>
        <input type="text" name="person_contact" class="form-control" value="<?= htmlspecialchars($s['person_contact']) ?>">
    </div>
    <div class="mb-2">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($s['email']) ?>">
    </div>

    <!-- Accounting fields -->
    <div class="mb-2">
        <label>GL Account Code</label>
        <input type="text" name="gl_account_code" class="form-control" value="<?= htmlspecialchars($s['gl_account_code'] ?? '') ?>">
    </div>
    <div class="mb-2">
        <label>Tax Type</label>
        <select name="tax_type" class="form-control">
            <option value="VAT" <?= ($s['tax_type']=='VAT')?'selected':'' ?>>VAT</option>
            <option value="Non-VAT" <?= ($s['tax_type']=='Non-VAT')?'selected':'' ?>>Non-VAT</option>
            <option value="Withholding" <?= ($s['tax_type']=='Withholding')?'selected':'' ?>>Withholding</option>
        </select>
    </div>
    <div class="mb-2">
        <label>Payment Method</label>
        <select name="payment_method" class="form-control">
            <option value="Bank Transfer" <?= ($s['payment_method']=='Bank Transfer')?'selected':'' ?>>Bank Transfer</option>
            <option value="Check" <?= ($s['payment_method']=='Check')?'selected':'' ?>>Check</option>
            <option value="Cash" <?= ($s['payment_method']=='Cash')?'selected':'' ?>>Cash</option>
        </select>
    </div>
    <div class="mb-2">
        <label>Bank Name</label>
        <input type="text" name="bank_name" class="form-control" value="<?= htmlspecialchars($s['bank_name'] ?? '') ?>">
    </div>
    <div class="mb-2">
        <label>Bank Account Number</label>
        <input type="text" name="bank_account_number" class="form-control" value="<?= htmlspecialchars($s['bank_account_number'] ?? '') ?>">
    </div>

    <div class="mb-2">
        <label>Status</label>
        <select name="status" class="form-control">
            <option value="1" <?= $s['status']==1?'selected':'' ?>>Active</option>
            <option value="0" <?= $s['status']==0?'selected':'' ?>>Inactive</option>
        </select>
    </div>
	<div style="float:right; margin-top:5px">
		<button type="button" class="btn btn-success" onclick="saveSupplier()"><i class="fa fa-save"></i> Save Changes</button>
	</div>
    
</div>

<div class="results"></div>

<script>


function saveSupplier() {
    var mode = 'editsupplier';
    var formData = $('.form-wrapper input, .form-wrapper select, .form-wrapper textarea').serialize();
    formData += '&mode=' + mode;

    $.post("./Modules/Purchasing_System/actions/actions.php", formData, function(data) {
        if(data.includes("Supplier updated successfully")) {
            swal("System Message", "Supplier updated successfully!", "success");
            $('#formmodal').hide();
            openMenuGranted('suppliers'); // reload supplier list
        } else {
            swal("System Message", data, "warning");
        }
    }).fail(function() {
        swal("Request Failed", "Could not update supplier. Please try again.", "error");
    });
}

</script>
