<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$canvass_no = $_POST['canvasnumber'] ?? '';
$prnumber   = $_POST['prnumber'] ?? '';
$status     = $_POST['status'] ?? '';

if(empty($canvass_no)){
    echo '<div class="text-danger">Invalid canvass number</div>';
    exit;
}

/* ================= HEADER ================= */
$h = $db->prepare("SELECT canvass_no, pr_no, requested_by, prepared_by, created_at, reviewed_by, approved_by, remarks FROM purchase_canvassing WHERE canvass_no = ?");
$h->bind_param("s", $canvass_no);
$h->execute();
$header = $h->get_result()->fetch_assoc();

if(!$header){
    echo '<div class="text-danger">Canvass not found</div>';
    exit;
}

/* ================= ITEMS ================= */
$item_stmt = $db->prepare("
    SELECT id, item_code, item_description, quantity, unit
    FROM purchase_canvassing_items
    WHERE canvass_no = ?
");
$item_stmt->bind_param("s", $canvass_no);
$item_stmt->execute();
$items = $item_stmt->get_result();

// Disable actions if reviewed or approved

$isReviewed = !empty($header['reviewed_by']);
$isApproved = !empty($header['approved_by']);

$lockEdit = ($isReviewed || $isApproved); // para suppliers & radios
$disableActions = $lockEdit ? 'disabled' : '';


$username = $_SESSION['purch_username'];
$applications = 'Purchasing System';

$userpreveledgereview = checkPolicy($username, $applications, 'Purchase Canvassing', 'p_review', $db);
$userpreveledgeapprover = checkPolicy($username, $applications, 'Purchase Canvassing', 'p_approver', $db);


					
?>

<style>

@media print {

    body {
        font-family: Arial, sans-serif;
        font-size: 11px;
        color: #000;
        background: #fff;
    }

    /* Tago tanan buttons, inputs, radios */
    button,
    .btn,
    .no-print,
    input,
    select,
    textarea,
    .form-check,
    .edit-btn,
    .save-btn,
    .delete-btn {
        display: none !important;
    }

    /* Table borders limpyo */
    table {
        border-collapse: collapse;
        width: 100%;
    }

    table th,
    table td {
        border: none !important;
        padding: 4px 6px;
    }

    /* Remove outer container border */
    div[style*="border"] {
        border: none !important;
    }

    /* Remove background colors */
    * {
        background: transparent !important;
        box-shadow: none !important;
    }

    h4 {
        margin-bottom: 10px;
        letter-spacing: 1px;
    }

    @page {
        size: A4;
        margin: 10mm;
    }
}


</style>

<link href="Modules/Purchasing_System/assets/css/select2.min.css" rel="stylesheet" />
<script src="Modules/Purchasing_System/assets/js/select2.min.js"></script>

<div style="border:1px solid #000; padding:20px; font-family:Arial; font-size:13px; max-height:83vh; overflow-y:auto">
    
    
    <div class="no-print" style="display:flex; justify-content:flex-end; gap:5px; margin-bottom:10px;">
	    <button onclick="bactomain()" class="btn btn-sm btn-secondary btn-sm">
	        <i class="fa fa-arrow-left"></i> Back
	    </button>
	    
	    <!--button onclick="viewcanvassing('<?= $canvass_no?>','<?= $prnumber?>','<?= $status?>')" class="btn btn-sm btn-info btn-sm">
	        <i class="fa fa-sync"></i> Reload
	    </button-->
		
		<!--button class="btn btn-primary btn-sm" onclick="printContents()">
		    <i class="fa fa-print"></i> Print
		</button-->	    
	    
	</div>
    
    <h4 class="text-center mb-3"><b>CANVASS SHEET</b></h4>

    <table width="100%" style="margin-bottom:15px">
        <tr>
            <td><b>DATE:</b> <?= date('Y-m-d', strtotime($header['created_at'])) ?></td>
            <td><b>PR NO:</b> <?= htmlspecialchars($header['pr_no']) ?></td>
        </tr>
        <tr>
            <td><b>CANVASS NO:</b> <?= htmlspecialchars($header['canvass_no']) ?></td>
            <td title="<?= $header['remarks']?>">
            	<b>REMARKS:</b>  
            	<?= htmlspecialchars(mb_strimwidth($header['remarks'], 0, 30, '...')) ?>
            </td>
        </tr>
    </table>

    <?php if($items->num_rows > 0): ?>
        <?php $x=1; while($item = $items->fetch_assoc()): ?>
        <div style="margin-bottom:20px">
            <b><?= $x++ ?>.</b> <?= htmlspecialchars($item['item_description']) ?> (<?= $item['quantity'].' '.$item['unit'] ?>)

            <table class="table table-bordered mt-2" style="font-size:12px">
                <thead class="table-light">
                    <tr>
                        <th width="2%">#</th>
                        <th>SUPPLIER</th>
                        <th width="20%">BRAND</th>
                        <th width="20%">PRICE</th>
                        <th width="10%">REMARKS</th>
                        <th width="10%">APPROVE</th>
                        <?php if(!$lockEdit): ?>
						    <th width="10%">ACTION</th>
						<?php endif; ?>
                    </tr>
                </thead>
                <tbody id="supplierRows-<?= $item['id'] ?>">
                <?php
                $sup = $db->prepare("
                    SELECT id, supplier_id, supplier_name, brand, price, status, remarks
                    FROM purchase_canvassing_suppliers
                    WHERE canvass_no = ? AND canvass_item_id = ?
                ");
                $sup->bind_param("si", $canvass_no, $item['id']);
                $sup->execute();
                $suppliers = $sup->get_result();
                $num = 0;
                if($suppliers->num_rows > 0):
                    while($s = $suppliers->fetch_assoc()):
                    $num++;
                ?>
                    <tr>
                        <td><?= $num?></td>
                        
                        <td>
                            <?= htmlspecialchars($s['supplier_name']) ?>
                            <input type="hidden" class="supplier-id" value="<?= $s['supplier_id'] ?: '' ?>">
                            <input type="hidden" class="supplier-name" value="<?= htmlspecialchars($s['supplier_name']) ?>">
                        </td>
                        
                        <td><?= htmlspecialchars($s['brand']) ?></td>
                        <td><?= number_format($s['price'],2) ?></td>
                        <td title="<?= htmlspecialchars($s['remarks'] ?? '') ?>"><?= htmlspecialchars($s['remarks'] ?? '') ?></td>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input approve-radio" type="radio" name="approve-<?= $item['id'] ?>" value="SELECTED" <?= $s['status'] === 1 ? 'checked' : '' ?> <?= $disableActions ?>>
                                <label class="form-check-label">Approve</label>
                            </div>
                        </td>
                        <?php if(!$lockEdit): ?>
                        <td>
                            <button class="btn btn-sm btn-primary edit-btn w-100 <?= $disableActions ?>" onclick="editSupplier(this)">Edit</button>
                            <button style="display:none" class="btn btn-sm btn-danger delete-btn w-45 <?= $disableActions ?>" onclick="deleteSupplier(this)">Delete</button>
                            <button class="btn btn-sm btn-success save-btn d-none w-100 <?= $disableActions ?>" onclick="saveSupplier(this, <?= $item['id'] ?>)">Save</button>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php
                    endwhile;
                endif;
                ?>
                </tbody>
            </table>

            
			<?php if(!$lockEdit): ?>
			<button class="btn btn-sm btn-primary btn-sm" onclick="addSupplierRow(<?= $item['id'] ?>)">
			    Add Supplier
			</button>
			<?php endif; ?>            

        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center text-muted">No items found</div>
    <?php endif; ?>

    <br>
    <table width="100%" style="margin-top:40px">
        <tr>

            <td><b>REQUESTED BY:</b><br><br><?= htmlspecialchars($header['requested_by']) ?></td>
            <td><b>PREPARED BY:</b><br><br><?= htmlspecialchars($header['prepared_by']) ?></td>
            <td><b>REVIEWED BY:</b><br><br><?= htmlspecialchars($header['reviewed_by']) ?></td>

            <td><b>APPROVED BY:</b><br><br>
                <?php if(empty($header['approved_by'])): ?>
                    <button class="btn btn-sm btn-outline-primary " onclick="approveCanvass('<?= $canvass_no ?>')"><i class="fa fa-thumbs-up"></i> Approve</button>
                <?php else: ?>
                    <?= htmlspecialchars($header['approved_by']) ?>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>

<?php
function checkPolicy($username, $applications, $module, $permission, $db)
{
    $sql = "SELECT * FROM tbl_system_permission 
            WHERE username = ? 
              AND modules = ? 
              AND applications = ? 
              AND $permission = 1 
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("sss", $username, $module, $applications);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows > 0){
        return 1; // allowed
    } else {
        return 0; // not allowed
    }
}
?>

<script src="Modules/Purchasing_System/assets/js/select2.min.js"></script>

<script>



document.querySelectorAll('#contents .approve-radio').forEach(radio => {
    radio.addEventListener('change', function(){
        if(!radio.checked) return; // ignore if unchecked

        let row = radio.closest('tr');
        let tbody = row.closest('tbody');
        let itemId = tbody.id.replace('supplierRows-', '');
        let supplierId = row.querySelector('.supplier-id').value;
        let price = row.querySelector('.price-input').value;

        if(!supplierId || !price){
            alert('Supplier or price missing');
            return;
        }

        let formData = new FormData();
        formData.append('item_id', itemId);
        formData.append('selected_supplier_id', supplierId);
        formData.append('selected_price', price);

        fetch('./Modules/Purchasing_System/actions/update_selected_supplier.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(resp => {
            if(resp.status === 'success'){
//                alert('Supplier approved successfully!');
                
                // Optional: refresh the item tbody to update radio check visually
                tbody.querySelectorAll('.approve-radio').forEach(r => {
                    if(r !== radio) r.checked = false;
                });
            } else {
                alert('Error: ' + resp.msg);
            }
        });
    });
});




function reviewCanvass(canvassNo){

    // Use the correct PHP variable
    var userPrivilegeReview = parseInt('<?= $userpreveledgereview ?>') || 0;

	if(userPrivilegeReview !== 1){
	    swal('Access Denied','You do not have permission to review this canvass.','error');
	    return;
	}

    // User is allowed → continue
    swal({
        title: "Confirm Review",
        text: "Are you sure the review is complete? Once reviewed, all items and suppliers will be locked and can no longer be edited.",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
    .then((willApprove) => {
        if(!willApprove) return;

        // Validate each item has exactly one approved supplier
        let valid = true;
        document.querySelectorAll('#contents tbody[id^="supplierRows-"]').forEach(tbody => {
            if(tbody.querySelectorAll('.approve-radio:checked').length !== 1){
                valid = false;
            }
        });

        if(!valid){
            swal('System Message','Each item must have exactly one approved supplier.','warning');
            return;
        }

        // Prepare FormData
        let fd = new FormData();
        fd.append('pr_no', '<?= $header['pr_no'] ?>');
        fd.append('canvass_no', canvassNo);
        fd.append('action', 'REVIEWED');
        fd.append('remarks', '');

        // Send to server
        fetch('./Modules/Purchasing_System/actions/save_canvas_approval.php',{
            method:'POST',
            body:fd
        })
        .then(res => res.json())
        .then(resp => {
            if(resp.status === 'success'){
                swal('Success','Canvass reviewed successfully!','success');
                // Reload the view
                viewcanvassing('<?= $canvass_no ?>','<?= $prnumber ?>','<?= $status ?>');
            } else {
                swal('Error', resp.message || 'Failed to review canvass','error');
            }
        })
        .catch(err => {
            console.error(err);
            swal('Error','An unexpected error occurred.','error');
        });

    });

} // end reviewCanvass



function approveCanvass(canvassNo){

	var userPrivilegeApprove = parseInt('<?= $userpreveledgeapprover ?>')  || 0;

    if(userPrivilegeApprove !== 1){
        swal('Access Denied','You do not have permission to approve this canvass.','error');
        return;
    }

    swal({
        title: "Approve Canvass?",
        text: "This action cannot be undone.",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
    .then((willApprove) => {
        if(!willApprove) return;

        let fd = new FormData();
        fd.append('pr_no', '<?= $header['pr_no'] ?>');
        fd.append('canvass_no', canvassNo);
        fd.append('action', 'APPROVED');
        fd.append('remarks', '');

        fetch('./Modules/Purchasing_System/actions/save_canvas_approval.php',{
            method:'POST',
            body:fd
        })
        .then(res=>res.json())
        .then(resp=>{
            if(resp.status === 'success'){
                swal('Success','Canvass approved successfully!','success');
                viewcanvassing('<?= $canvass_no ?>','<?= $prnumber ?>','<?= $status ?>');
            }
        });
    });
}




function addSupplierRow(itemId){
    fetch(`./Modules/Purchasing_System/actions/get_supplier.php?item_id=${itemId}`)
    .then(res => res.json())
    .then(data => {
        let tbody = document.getElementById('supplierRows-'+itemId);

        if(data.length === 0){
            alert('No more suppliers available to add for this item.');
            return;
        }

        let rowCount = tbody.querySelectorAll('tr').length + 1;
        let row = document.createElement('tr');
        row.innerHTML = `
            <td>${rowCount}</td>
            <td>
                <select class="form-select form-select-sm supplier-select" style="width:100%">
                    <option value="">Select Supplier</option>
                    ${data.map(s => `<option value="${s.id}">${s.name}</option>`).join('')}
                </select>
                <input type="hidden" class="supplier-id">
                <input type="hidden" class="supplier-name">
            </td>
            <td><input type="text" class="form-control form-control-sm brand-input"></td>
            <td><input type="number" class="form-control form-control-sm price-input" step="0.01"></td>
            <td><input type="text" class="form-control form-control-sm remarks-input"></td>
            <td colspan="2">
                <button class="btn btn-sm btn-success save-btn w-100" onclick="saveSupplier(this, ${itemId})"><i class="bi bi-pencil"></i> Save</button>
            </td>
        `;
        tbody.appendChild(row);

        $(row).find('.supplier-select').select2({ placeholder: "Select Supplier", width: 'resolve' });

        let select = row.querySelector('.supplier-select');
        let hiddenId = row.querySelector('.supplier-id');
        let hiddenName = row.querySelector('.supplier-name');

        select.addEventListener('change', function(){
            hiddenId.value = select.value;
            hiddenName.value = select.options[select.selectedIndex].text;
        });
    });
}



function editSupplier(btn){
    let row = btn.closest('tr');
    row.querySelectorAll('input').forEach(input => input.disabled = false);
    row.querySelector('.edit-btn').classList.add('d-none');
    row.querySelector('.delete-btn').classList.add('d-none');
    row.querySelector('.save-btn').classList.remove('d-none');
}

function deleteSupplier(btn){
    if(!confirm('Are you sure you want to delete this supplier?')) return;
    let row = btn.closest('tr');
    let supplier_id = row.querySelector('.supplier-id')?.value;
    let formData = new FormData();
    formData.append('canvass_no', '<?= $canvass_no ?>');
    formData.append('supplier_id', supplier_id);

    fetch('./Modules/Purchasing_System/actions/delete_canvas_supplier.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(resp => { alert(resp); row.remove(); });
}


function saveSupplier(btn, itemId){
    let row = btn.closest('tr');
    let supplier_id = row.querySelector('.supplier-id').value;
    let supplier    = row.querySelector('.supplier-name').value;


    // Fallback: kung empty, kuhaa gikan sa select
    if(!supplier_id || !supplier){
        let select = row.querySelector('.supplier-select');
        if(select && select.value){
            supplier_id = select.value;
            supplier = select.options[select.selectedIndex].text;
        }
    }

    let brand  = row.querySelector('.brand-input').value;
    let price  = row.querySelector('.price-input').value;
    let remarks= row.querySelector('.remarks-input').value;

	
	if(!supplier_id || !supplier){
        swal('System Message', 'Please select supplier.', 'warning');
        return;
    }
    
    if(!brand || brand == ''){
        swal('System Message', 'Please select brand.', 'warning');
        return;
    }
	
	if(!price || price == ''){
        swal('System Message', 'Please select price.', 'warning');
        return;
    }
    

    let formData = new FormData();
    formData.append('canvass_no', '<?= $canvass_no ?>');
    formData.append('item_id', itemId);
    formData.append('supplier_id', supplier_id);
    formData.append('supplier', supplier);
    formData.append('brand', brand);
    formData.append('price', price);
    formData.append('remarks', remarks);
    formData.append('status', 'OPTION');
    

    fetch('./Modules/Purchasing_System/actions/save_canvas_supplier.php',{
        method:'POST',
        body:formData
    })
    .then(res=>res.text())
    .then(()=> viewcanvassing('<?= $canvass_no ?>','<?= $prnumber ?>','<?= $status ?>'));
}



function bactomain(){
    $('#contents').load('./Modules/Purchasing_System/includes/pc_approval.php');
}

function viewcanvassing(canvasnumber,prnumber,status){
    $.post("./Modules/Purchasing_System/apps/pc_approval_sheet.php", { canvasnumber: canvasnumber, prnumber: prnumber, status: status },
    function(data) {
        $('#contents').html(data);
    });
}


function printContents() {
    // i-select ang sulod sa div nga e-print
    var divContents = document.getElementById("contents").innerHTML;
    
    // i-save ang original body content
    var originalContents = document.body.innerHTML;
    
    // i-replace ang body content sa div content lang
    document.body.innerHTML = divContents;
    
    // i-print ang page
    window.print();
    
    document.body.innerHTML = originalContents;
}


</script>
