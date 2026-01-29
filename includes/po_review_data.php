<?php 
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$status = $_POST['status'] ?? '';
$limit  = (int)($_POST['limit'] ?? 50);

$sql = "
    SELECT 
        po.*, 
        s.name AS supplier_name, 
        s.supplier_code
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    WHERE 1=1
";

$params = [];
$types  = "";

if ($status !== '') {
    $sql .= " AND po.status = ? ORDER BY po.id DESC LIMIT ?";
    $params = [$status, $limit];
    $types = "si";
} else {
    $sql .= " AND po.reviewed_by IS NULL ORDER BY po.id DESC LIMIT ?";
    $params = [$limit];
    $types = "i";
}

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();


$pos = [];
while ($row = $result->fetch_assoc()) {
    $pos[] = $row;
}
$stmt->close();



$i = 0;

$status_badge = [
    'PENDING'  => 'badge bg-danger',
    'ASSIGNED'     => 'badge bg-success',
    'APPROVED' => 'badge bg-success',
    'PARTIAL_RECEIVED'  => 'badge bg-success',
    'RECEIVED' => 'badge bg-success',
    'CANCELLED'=> 'badge bg-warning'
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
						<button class="btn btn-primary btn-sm" onclick="viewpo('<?= $po['po_number'] ?>','<?= $po['pr_number'] ?>','<?= $po['supplier_name'] ?>')">
					        <i class="fa fa-eye"></i> View
					    </button>
					</td>                    

                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="11" class="text-center">No purchase orders found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script>

function viewpo(ponumber,prnumber,suppliername){
	
	$.post("./Modules/Purchasing_System/apps/po_review_view_form.php", { ponumber: ponumber, prnumber: prnumber, suppliername: suppliername },
	function(data) {
		$('#contents').html(data);
	});
}

</script>
