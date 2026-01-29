<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
?>

<style>
.form-wrapper { width: 900px; max-height: 80vh; overflow-y: auto; padding: 15px; }
.form-wrapper .mb-2 { margin-bottom: 10px; }
.form-wrapper label { font-weight: bold; }
.results { font-size: 12px; margin-top: 10px; }
</style>

<div class="form-wrapper">

    <div class="mb-2">
        <label>Supplier Code</label>
        <input type="text" name="supplier_code" class="form-control" autocomplete="off">
    </div>
    <div class="mb-2">
        <label>Name</label>
        <input type="text" name="name" class="form-control" autocomplete="off">
    </div>
    <div class="mb-2">
        <label>Address</label>
        <textarea name="address" class="form-control"></textarea>
    </div>
    <div class="mb-2">
        <label>TIN</label>
        <input type="text" name="tin" class="form-control" autocomplete="off">
    </div>
    <div class="mb-2">
        <label>Payment Terms</label>
        <input type="text" name="payment_terms" class="form-control" autocomplete="off">
    </div>
    <div class="mb-2">
        <label>Contact Person</label>
        <input type="text" name="contact_person" class="form-control" autocomplete="off">
    </div>
    <div class="mb-2">
        <label>Phone / Mobile</label>
        <input type="text" name="person_contact" class="form-control" autocomplete="off">
    </div>
    <div class="mb-2">
        <label>Email</label>
        <input type="email" name="email" class="form-control" autocomplete="off">
    </div>

    <!-- Optional accounting fields -->
    <div class="mb-2">
        <label>GL Account Code</label>
        <input type="text" name="gl_account_code" class="form-control">
    </div>
    <div class="mb-2">
        <label>Tax Type</label>
        <select name="tax_type" class="form-control">
            <option value="VAT">VAT</option>
            <option value="Non-VAT">Non-VAT</option>
            <option value="Withholding">Withholding</option>
        </select>
    </div>
    <div class="mb-2">
        <label>Payment Method</label>
        <select name="payment_method" class="form-control">
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Check">Check</option>
            <option value="Cash">Cash</option>
        </select>
    </div>
    <div class="mb-2">
        <label>Bank Name</label>
        <input type="text" name="bank_name" class="form-control" autocomplete="off">
    </div>
    <div class="mb-2">
        <label>Bank Account Number</label>
        <input type="text" name="bank_account_number" class="form-control" autocomplete="off">
    </div>

    <div class="mb-2">
        <label>Status</label>
        <select name="status" class="form-control">
            <option value="1" selected>Active</option>
            <option value="0">Inactive</option>
        </select>
    </div>
	
	<div style="float:right; margin-top:5px">
		<button type="button" class="btn btn-success" onclick="saveSupplier()"><i class="fa fa-save"></i> Add Supplier</button>
	</div>

    
</div>

<div class="results"></div>

<script>

function saveSupplier() {
    var mode = 'savesupplier';
    var formData = $('.form-wrapper input, .form-wrapper select, .form-wrapper textarea').serialize();
    formData += '&mode=' + mode;


    $.post("./Modules/Purchasing_System/actions/actions.php", formData, function(data) {

        if(data.includes("Supplier added successfully") || data.includes("Supplier updated successfully")) {
            swal("System Message", "Supplier saved successfully!", "success");
            $('#formmodal').hide();
            openMenuGranted('suppliers');

        } else {
            swal("System Message", data, "warning");
        }
    }).fail(function() {
        swal("Request Failed", "Could not save supplier. Please try again.", "error");
    });
}


</script>
