<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    

$ponumber = $_POST['ponumber'] ?? '';

if(!$ponumber){
    echo '<div class="text-danger">Invalid PO Number</div>';
    exit;
}

$stmt = $db->prepare("
    SELECT 
        po.id,
        po.po_number,
        po.pr_number,
        po.branch,
        po.order_date,
        po.expected_delivery,
        po.status,
        po.subtotal,
        po.vat,
        po.total_amount,
        po.remarks,
        po.created_by,
        po.prepared_by,
        po.prepared_date,
		po.reviewed_by,
        po.reviewed_date,
        po.approved_by,
        po.approved_date,
        po.closed_po,
        s.name AS supplier_name
    FROM purchase_orders po
    JOIN suppliers s ON s.id = po.supplier_id
    WHERE po.po_number = ?
    LIMIT 1
");

$stmt->bind_param("s", $ponumber);
$stmt->execute();
$po = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$po){
    echo '<div class="text-danger">PO not found</div>';
    exit;
}



$stmt = $db->prepare("
    SELECT item_code, description, qty, uom, unit_price, total_price
    FROM purchase_order_items
    WHERE po_id = ?
");
$stmt->bind_param("i", $po['id']);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$isApproved = !empty($po['approved_by']);

$username = $_SESSION['purch_username'];
$applications = 'Purchasing System';
$userpreveledgeapprover = checkPolicy($username, $applications, 'Purchase Orders (PO)', 'p_approver', $db);

?>

<style>
/* Default styles */
.po-wrapper { font-size:13px; color:#000; }
.po-header { display:flex; justify-content:space-between; align-items:flex-start; }
.po-box table { width:100%; border-collapse:collapse; }
.po-box td { border:1px solid #000; padding:5px; }
.po-items { border-collapse:collapse; width:100%; }
.po-items th, .po-items td { border:1px solid #000; padding:6px; }
.po-items th { background:#f2f2f2; }

/* Buttons container */
.no-print { display:flex; justify-content:flex-end; gap:5px; margin-bottom:10px; }

/* ===== Print Styles ===== */

@media print {
    body { font-size: 9px; }
    
    .po-box table,
    .po-items { font-size: 9px; }

    .po-items th, .po-items td { padding: 3px 4px; }

    .po-wrapper div[style*="border-top:1px solid"] strong {
        font-size: 10px; /* gamay ra pero visible */
    }
    
    .po-wrapper div[style*="border-top:1px solid"] small {
        font-size: 8px; /* para sa date */
    }

    .no-print { display: none; }

    @page { margin: 5mm 10mm; }
}


</style>


<div class="po-wrapper">
	
	
	
	<div class="no-print d-flex justify-content-between align-items-center">

	    <!-- LEFT SIDE (destructive actions) -->
	    <div>
	        
	    </div>
	
	    <!-- RIGHT SIDE (normal actions) -->
	    <div class="no-print">
	        <button onclick="bactomain()" class="btn btn-sm btn-secondary">
	            <i class="fa fa-arrow-left"></i> Back
	        </button>
	
	        <?php if(in_array($po['status'], ['APPROVED','PARTIAL_RECEIVED','RECEIVED'])): ?>
	            <button class="btn btn-primary btn-sm" onclick="printContents()">
	                <i class="fa fa-print"></i> Print
	            </button>
	        <?php endif; ?>
	    </div>
	
	</div>

	
	
	
    <!-- HEADER -->
    <div class="po-header">
        <div>
            <strong>Jathnier Corporation</strong><br>
            Ruby St., RGA Village, Dacudao Avenue,<br>
            Davao City, Philippines
        </div>
        <h3>Purchase Order</h3>
    </div>

    <br>

    <!-- PO INFO -->
    <div class="po-box">
        <table>
            <tr>
                <td>Date</td>
                <td><?= htmlspecialchars($po['order_date']) ?></td>
                <td>P.O. No.</td>
                <td><?= htmlspecialchars($po['po_number']) ?></td>
                <td>P.R. No.</td>
                <td><?= htmlspecialchars($po['pr_number']) ?></td>
                <td>Expected</td>
                <td><?= htmlspecialchars($po['expected_delivery']) ?></td>
            </tr>
            <tr>
                <td colspan="4">
                    Vendor<br>
                    <strong><?= htmlspecialchars($po['supplier_name']) ?></strong>
                </td>
                <td colspan="4">
                    Ship To<br>
                    <strong><?= htmlspecialchars($po['branch']) ?></strong>
                </td>
            </tr>
        </table>
    </div>

    <br>

    <!-- ITEMS -->
    <table class="po-items">
        <thead>
            <tr>
                <th width="12%">Item</th>
                <th>Description</th>
                <th width="8%">Qty</th>
                <th width="8%">U/M</th>
                <th width="12%">Rate</th>
                <th width="14%">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($items)): ?>
                <?php foreach($items as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['item_code']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td align="right"><?= number_format($row['qty'],2) ?></td>
                    <td><?= htmlspecialchars($row['uom']) ?></td>
                    <td align="right"><?= number_format($row['unit_price'],2) ?></td>
                    <td align="right"><?= number_format($row['total_price'],2) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" align="center">No items found</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" align="left"><?= htmlspecialchars($po['remarks']) ?></td>
                
				<td colspan="2">
				  <div style="display: flex; justify-content: space-between;">
				    <strong>Total:</strong>
				    <strong><?= number_format($po['total_amount'],2) ?></strong>
				  </div>
				</td>             

            </tr>
        </tfoot>
    </table>

    <br>

    <!-- SIGNATURE / APPROVAL -->
	<div style="display:flex; justify-content:space-between; margin-top:40px; align-items:flex-start;">
	
	    <!-- PREPARED -->
		<div style="width:30%;">
		<?php if (empty($po['prepared_by'])): ?>
			<div style="border-top:1px solid #000; padding-top:5px; text-align:center">
				<button class="btn btn-sm btn-success" onclick="markPreparedPO('<?= $po['po_number'] ?>')"><i class="fa fa-check-circle"></i> Confirm Preparing</button>
			</div>
		
			
		<?php else: ?>
		
		    <div style="border-top:1px solid #000; padding-top:5px; text-align:center">
		        Prepared by<br>
		        <strong><?= htmlspecialchars($po['prepared_by']) ?></strong><br>
		        <small><?= htmlspecialchars($po['prepared_date']) ?></small>
		    </div>
		
		<?php endif; ?>
		</div>
		
		
		<!-- REVIEWED -->
		<div style="width:30%;">
		<?php if (empty($po['reviewed_by'])): ?>
		
		
		<?php else: ?>
		
		    <div style="border-top:1px solid #000; padding-top:5px; text-align:center">
		        Reviewed by<br>
		        <strong><?= htmlspecialchars($po['reviewed_by']) ?></strong><br>
		        <small><?= htmlspecialchars($po['reviewed_date']) ?></small>
		    </div>
		
		<?php endif; ?>
		</div>

		
		
	    <!-- APPROVAL -->
	    <div style="width:30%; text-align:right;">
	
	        <?php if (empty($po['approved_by'])): ?>
		
			
			<?php else: ?>
				<div style="border-top:1px solid #000; padding-top:5px; text-align:center">
		        Approved by<br>
			        <strong><?= htmlspecialchars($po['approved_by']) ?></strong><br>
			        <small><?= htmlspecialchars($po['approved_date']) ?></small>
			    </div>

			
			<?php endif; ?>

	
	    </div>
	
	</div>

    
    
    
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


<script>


function markPreparedPO(poNumber){

    swal({
        title: "Mark PO as Prepared?",
        text: "This will lock the Purchase Order and move it to next stage.",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
    .then((willPrepare) => {
        if(!willPrepare) return;

        let fd = new FormData();
        fd.append('po_number', poNumber);

        fetch('./Modules/Purchasing_System/actions/mark_prepared_po.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(resp => {
            if(resp.status === 'success'){
                swal('Success', 'Purchase Order marked as prepared!', 'success');
                reloadme(poNumber);
            } else {
                swal('Error', resp.msg || 'Failed to mark prepared', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            swal('Error', 'Unexpected error occurred', 'error');
        });
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
    
    // i-restore ang original page content
    document.body.innerHTML = originalContents;

}

function bactomain(){
    $('#contents').load('./Modules/Purchasing_System/includes/purchase_order.php');
}


function reloadme(ponumber,prnumber,suppliername=''){
	
	$.post("./Modules/Purchasing_System/apps/purchase_order_view_form.php", { ponumber: ponumber, prnumber: prnumber, suppliername: suppliername },
	function(data) {
		$('#contents').html(data);
	});
}

</script>
