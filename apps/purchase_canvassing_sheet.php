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
$isReviewed = !empty($header['prepared_by']);
$isApproved = !empty($header['approved_by']);
$lockEdit = ($isReviewed || $isApproved); // para suppliers & radios
$disableActions = $lockEdit ? 'disabled' : '';

$username = $_SESSION['purch_username'];
$applications = 'Purchasing System';
$userpreveledgereview = checkPolicy($username, $applications, 'Purchase Canvassing', 'p_review', $db);
$userpreveledgeapprover = checkPolicy($username, $applications, 'Purchase Canvassing', 'p_approver', $db);

?>


<div style="border:1px solid #000; padding:20px; font-family:Arial; font-size:13px; max-height:83vh; overflow-y:auto">
    <h4 class="text-center mb-3"><b>CANVASS SHEET</b></h4>

    <?php if($items->num_rows > 0): ?>
        <?php $x=1; while($item = $items->fetch_assoc()): ?>
        <div style="margin-bottom:20px">
            <b><?= $x++ ?>.</b> <?= htmlspecialchars($item['item_description']) ?> (<?= $item['quantity'].' '.$item['unit'] ?>)

            <table class="table table-bordered mt-2" style="font-size:12px">
                <thead class="table-light">
                    <tr>
                        <th width="2%">#</th>
                        <th>SUPPLIER</th>
                        <th>EMAIL</th>
                        <th>BRAND</th>
                        <th>PRICE</th>
                        <th>REMARKS</th>
                        <th>APPROVE</th>
                        <?php if(!$lockEdit): ?><th>ACTION</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="supplierRows-<?= $item['id'] ?>">
                <?php
                $sup = $db->prepare("
                    SELECT pcs.id, pcs.supplier_id, pcs.supplier_name, pcs.brand, pcs.price, pcs.status, pcs.remarks,
                           s.email
                    FROM purchase_canvassing_suppliers pcs
                    LEFT JOIN suppliers s ON pcs.supplier_id = s.id
                    WHERE pcs.canvass_no = ? AND pcs.canvass_item_id = ?
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
                    <td><?= $num ?></td>
                    <td>
                        <?= htmlspecialchars($s['supplier_name']) ?>
                        <input type="hidden" class="supplier-id" value="<?= $s['supplier_id'] ?: '' ?>">
                        <input type="hidden" class="supplier-name" value="<?= htmlspecialchars($s['supplier_name']) ?>">
                        <input type="hidden" class="supplier-email" value="<?= htmlspecialchars($s['email'] ?? '') ?>">
                    </td>
                    <td class="supplier-email-cell"><?= htmlspecialchars($s['email'] ?? '') ?></td>
                    <td><input type="text" class="form-control form-control-sm brand-input" value="<?= htmlspecialchars($s['brand']) ?>" disabled></td>
                    <td><input type="number" class="form-control form-control-sm price-input" value="<?= number_format($s['price'],2) ?>" step="0.01" disabled></td>
                    <td title="<?= $s['remarks'];?>"><input type="text" class="form-control form-control-sm remarks-input" value="<?= htmlspecialchars($s['remarks'] ?? '') ?>" disabled></td>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input approve-radio" type="radio" name="approve-<?= $item['id'] ?>" value="SELECTED" <?= $s['status'] === 1 ? 'checked' : '' ?> <?= $disableActions ?>>
                            <label class="form-check-label">Approve</label>
                        </div>
                    </td>
                    <?php if(!$lockEdit): ?>
                    <td>

                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>

            <?php if(!$lockEdit): ?>
            <button class="btn btn-sm btn-primary btn-sm" onclick="addSupplierRow(<?= $item['id'] ?>)">Add Supplier</button>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center text-muted">No items found</div>
    <?php endif; ?>




    <br>
    <table width="100%" style="margin-top:40px">
        <tr>
            <td>
            	<b>REQUESTED BY:</b>
            	<br>
            	<br>
            	<?= htmlspecialchars($header['requested_by']) ?>
            </td>
            <td>
			    <div style="display:flex; justify-content:flex-end; align-items:center;">
			        <?php if(empty($header['prepared_by'])): ?>
			            <button class="btn btn-sm btn-outline-primary" onclick="markPrepared('<?= $canvass_no ?>')">
			                <i class="fa fa-check-circle"></i> Prepared By
			            </button>
			        <?php else: ?>
			            <b>PREPARED BY:</b>&nbsp;&nbsp;<?= htmlspecialchars($header['prepared_by']) ?>
			        <?php endif; ?>
			    </div>
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

<script src="./Modules/Purchasing_System/assets/js/select2.min.js"></script>

<script>


function sendCanvassEmailBtn(btn, itemId, supplierId){

    const row = btn.closest('tr');
    const supplierName  = row.querySelector('.supplier-name').value;
    const supplierEmail = row.querySelector('.supplier-email').value;
    const textContent   = row.closest('div').querySelector('b').nextSibling.textContent.trim();

    const match    = textContent.match(/(.+)\s\((.+)\)/);
    const itemDesc = match ? match[1] : '';
    const qtyUnit  = match ? match[2] : '';

    const canvassNo = '<?= $canvass_no ?>';
    const token     = Math.random().toString(36).substr(2, 10);

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-envelope"></i> Sending...';

    swal({
        title: "Sending Email...",
        text: "Please wait",
        icon: "info",
        buttons: false
    });

    fetch('./Modules/Purchasing_System/actions/send_canvass_email.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            supplier_email: supplierEmail,
            supplier_name: supplierName,
            item_desc: itemDesc,
            qty_unit: qtyUnit,
            canvass_no: canvassNo,
            item_id: itemId,
            supplier_id: supplierId,
            token: token
        })
    })
    .then(res => res.text())
    .then(text => {

        let resp;
        try {
            resp = JSON.parse(text);
        } catch (e) {
            swal("Server Error", text, "error");
            resetBtn();
            return;
        }

        if (resp.status === 'success') {

            swal({
                title: "Email Sent!",
                text: resp.msg || "Supplier has been notified.",
                icon: "success",
                button: "OK"
            }).then(() => {
                viewcanvassing(
                    '<?= $canvass_no ?>',
                    '<?= $prnumber ?>',
                    '<?= $status ?>'
                );
            });

        } else {

            swal({
                title: "Failed",
                text: resp.msg || "Unable to send email",
                icon: "error"
            });

            resetBtn();
        }
    })
    .catch(err => {
        console.error(err);

        swal({
            title: "Unexpected Error",
            text: "Check console for details.",
            icon: "error"
        });

        resetBtn();
    });

    function resetBtn(){
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-envelope"></i> Send Email';
    }
}



function markPrepared(canvassNo){

    // Validate all items: each item must have at least one supplier selected
    let valid = true;
	let errorMsg = '';
	
	document.querySelectorAll('tbody[id^="supplierRows-"]').forEach(tbody => {
	
	    let suppliers = tbody.querySelectorAll('tr');
	    let approved  = tbody.querySelectorAll('.approve-radio:checked');
	
	    if(suppliers.length < 3){
	        valid = false;
	        errorMsg = 'Each item must have at least 3 suppliers.';
	    }
	
	    if(suppliers.length > 5){
	        valid = false;
	        errorMsg = 'Each item must have no more than 5 suppliers.';
	    }
	
	    if(approved.length !== 1){
	        valid = false;
	        errorMsg = 'Each item must have exactly 1 approved supplier.';
	    }
	});
	
	if(!valid){
	    swal('System Message', errorMsg, 'warning');
	    return;
	}


    // Confirm action
    swal({
        title: "Mark as Prepared?",
        text: "Once marked as prepared, this canvass will be locked and ready for review.",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
    .then((willPrepare) => {
        if(!willPrepare) return;

        let fd = new FormData();
        fd.append('canvass_no', canvassNo);
        fd.append('action', 'PREPARED');

        fetch('./Modules/Purchasing_System/actions/mark_prepared.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(resp => {
            if(resp.status === 'success'){
                swal('Success', 'Canvass marked as prepared!', 'success');
                viewcanvassing('<?= $canvass_no ?>','<?= $prnumber ?>','<?= $status ?>');
            } else {
                swal('Error', resp.msg || 'Failed to mark prepared', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            swal('Error', 'An unexpected error occurred', 'error');
        });
    });
}



document.querySelectorAll('#contents .approve-radio').forEach(radio => {
    radio.addEventListener('change', function(){
        if(!radio.checked) return; // ignore if unchecked

        let row = radio.closest('tr');
        let tbody = row.closest('tbody');
        let itemId = tbody.id.replace('supplierRows-', '');
        let supplierId = row.querySelector('.supplier-id').value;
        let price = parseFloat(row.querySelector('.price-input').value);
        let brand = row.querySelector('.brand-input').value.trim();

        // Validation: supplierId, price, brand
        if(!supplierId){
            radio.checked = false;
            swal('System Message', 'Supplier missing', 'warning');
            return;
        }
        if(!brand){
            radio.checked = false;
            swal('System Message', 'Cannot approve: Brand is empty', 'warning');
            return;
        }
        if(price < 1){
            radio.checked = false;
            swal('System Message', 'Cannot approve: Price must be at least 1', 'warning');
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
                // Uncheck other radios visually
                tbody.querySelectorAll('.approve-radio').forEach(r => {
                    if(r !== radio) r.checked = false;
                });
            } else {
                radio.checked = false;
                swal('System Message', 'Error: ' + resp.msg, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            radio.checked = false;
            swal('System Message', 'Unexpected error occurred', 'error');
        });
    });
});




function addSupplierRow(itemId) {
    const tbody = document.getElementById('supplierRows-' + itemId);
    const currentCount = tbody.querySelectorAll('tr').length;

    if (currentCount >= 5) {
        swal('System Message', 'Maximum of 5 suppliers only per item.', 'warning');
        return;
    }

    fetch(`./Modules/Purchasing_System/actions/get_supplier.php?item_id=${itemId}`)
        .then(res => res.json())
        .then(data => {
            if (!data || data.length === 0) {
                swal('System Message', 'No more suppliers available to add for this item.', 'warning');
                return;
            }

            const rowCount = tbody.querySelectorAll('tr').length + 1;
            const row = document.createElement('tr');

            row.innerHTML = `
                <td>${rowCount}</td>
                <td>
                    <select class="form-select form-select-sm supplier-select" style="width:100%">
                        <option value="">Select Supplier</option>
                        ${data.map(s => `<option value="${s.id}" data-email="${s.email}">${s.name}</option>`).join('')}
                    </select>
                    <input type="hidden" class="supplier-id">
                    <input type="hidden" class="supplier-name">
                    <input type="hidden" class="supplier-email">
                </td>
                <td class="supplier-email-cell"></td>
                <td><input type="text" class="form-control form-control-sm brand-input" disabled></td>
                <td><input type="number" class="form-control form-control-sm price-input" step="0.01" disabled></td>
                <td><input type="text" class="form-control form-control-sm remarks-input" disabled></td>
                <td colspan="2">
                    <button class="btn btn-sm btn-success send-email-btn w-100" 
                        onclick="sendCanvassEmailBtn(this, ${itemId}, this.closest('tr').querySelector('.supplier-id').value)">
                        <i class="bi bi-envelope"></i> Send Email
                    </button>
                </td>
            `;

            tbody.appendChild(row);

            // Initialize Select2
            $(row).find('.supplier-select').select2({ placeholder: "Select Supplier", width: 'resolve' });

            const select = row.querySelector('.supplier-select');
            const hiddenId = row.querySelector('.supplier-id');
            const hiddenName = row.querySelector('.supplier-name');
            const hiddenEmail = row.querySelector('.supplier-email');
            const emailCell = row.querySelector('.supplier-email-cell');

            // Sync hidden inputs & display email on change
            select.addEventListener('change', function () {
                hiddenId.value = select.value;
                hiddenName.value = select.options[select.selectedIndex].text;
                hiddenEmail.value = select.options[select.selectedIndex].dataset.email || '';
                emailCell.textContent = hiddenEmail.value;
                
                const sendBtn = row.querySelector('.send-email-btn');
				if(sendBtn){
				sendBtn.style.display = hiddenId.value ? 'block' : 'none';
				}
				
            });
        })
        .catch(err => {
            console.error(err);
            swal('Error', 'Failed to load suppliers.', 'error');
        });
}



function bactomain(){
    $('#contents').load('./Modules/Purchasing_System/includes/purchase_canvassing.php');
}

function viewcanvassing(canvasnumber,prnumber,status){
    $.post("./Modules/Purchasing_System/apps/purchase_canvassing_sheet.php", { canvasnumber: canvasnumber, prnumber: prnumber, status: status },
    function(data) {
        $('#contents').html(data);
    });
}




</script>
