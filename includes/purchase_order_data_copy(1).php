<?php 
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Fetch all POs with supplier info
$query = "
    SELECT po.*, s.name AS supplier_name, s.supplier_code
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    ORDER BY po.created_at DESC
";
$result = $db->query($query);

$pos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pos[] = $row;
    }
}
$i = 0;

$status_badge = [
    'PENDING'  => 'badge bg-warning',
    'ASSIGNED'     => 'badge bg-info',
    'APPROVED' => 'badge bg-success',
    'PARTIAL'  => 'badge bg-primary',
    'RECEIVED' => 'badge bg-dark',
    'CANCELLED'=> 'badge bg-danger'
];

?>

<table class="table table-bordered table-striped" id="potable">
    <thead>
        <tr>
            <th>#</th>
            <th>PO Number</th>
            <th>PR Number</th>
            <th>Supplier</th>
            <th>Order Date</th>
            <th>Expected Delivery</th>
            <th>Status</th>
            <th>Subtotal</th>
            <th>VAT</th>
            <th>Total Amount</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if(!empty($pos)): ?>
            <?php foreach($pos as $po): $i++; ?>
                <tr>
                    <td><?= $i ?></td>
                    <td><?= htmlspecialchars($po['po_number']) ?></td>
                    <td><?= htmlspecialchars($po['pr_number']) ?></td>
                    <td><?= htmlspecialchars($po['supplier_name']) ?></td>
                    <td><?= htmlspecialchars($po['order_date']) ?></td>
                    <td><?= htmlspecialchars($po['expected_delivery']) ?></td>
                    <td><span class="<?= $status_badge[$po['status']] ?>"><?= $po['status'] ?></span></td>
                    <td><?= number_format($po['subtotal'],2) ?></td>
                    <td><?= number_format($po['vat'],2) ?></td>
                    <td><?= number_format($po['total_amount'],2) ?></td>
                    
					<td style="text-align:center">
						<?php
							
							if($po['status'] === 'PENDING'){  
						?>
							    <button class="btn btn-primary btn-sm" onclick="editPO('<?= $po['id'] ?>')">
							        <i class="fa fa-pencil"></i> Edit
							    </button>
					    <?php
					    	} else {
						?>	
					    		<button class="btn btn-danger btn-sm">
							        <i class="fa fa-lock"></i> Locked
							    </button>
					    
					    <?php
					    	}
					    ?>
					    
					</td>                    

                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="11" class="text-center">No purchase orders found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
function editPO(po_id){
    $('#modaltitle').html("MODIFY PURCHASE ORDER");
    $.post("./Modules/Purchasing_System/apps/purchase_order_edit.php", { po_id: po_id },
    function(data) {		
        $('#formmodal_page').html(data);
        $('#formmodal').show();
    });
}
</script>
