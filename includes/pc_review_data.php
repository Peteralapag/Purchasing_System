<?php 
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$status = $_POST['status'] ?? '';
$limit  = (int)($_POST['limit'] ?? 50);


$sql = "SELECT * FROM purchase_canvassing WHERE 1=1";
$params = [];
$types  = "";

if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
} else {
    // default: hide pending
    $sql .= " AND (status = ?) AND prepared_by IS NOT NULL";
    $params[] = 'OPEN';
    $types .= "s";
}

// ORDER + LIMIT
$sql .= " ORDER BY id DESC LIMIT ?";
$params[] = $limit;
$types .= "i";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();




$status_badge = [
    'OPEN'         => 'badge bg-danger',
    'FOR_APPROVAL' => 'badge bg-danger',
    'APPROVED'     => 'badge bg-success',
    'REJECTED'     => 'badge bg-success',
    'PARTIAL_PO_CREATED'   => 'badge bg-success',
    'PO_CREATED'   => 'badge bg-success'
];
?>

<table class="table table-bordered table-striped" id="canvassingtable">
    <thead>
        <tr>
            <th>#</th>
            <th>Canvass No.</th>
            <th>PR No.</th>
            <th>Canvass By</th>
            <th>Remarks</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
	<?php if (!empty($requests)): ?>
	    <?php $i = 1; foreach ($requests as $row): ?>
	        <tr>
	            <td><?= $i++; ?></td>
	            <td><?= htmlspecialchars($row['canvass_no']); ?></td>
	            <td><?= htmlspecialchars($row['pr_no']); ?></td>
	            <td><?= htmlspecialchars($row['requested_by']); ?></td>
	
	            <?php
	            $max = 10;
	            $remarksDisplay = strlen($row['remarks']) > $max 
	                ? substr($row['remarks'], 0, $max) . '...' 
	                : $row['remarks'];
	            ?>
	            <td title="<?= htmlspecialchars($row['remarks']) ?>">
	                <?= htmlspecialchars($remarksDisplay) ?>
	            </td>
	
	            <td>
	                <span class="<?= $status_badge[$row['status']] ?? 'badge bg-secondary' ?>">
	                    <?= htmlspecialchars($row['status']) ?>
	                </span>
	            </td>
	
	            <td>
	                <button class="btn btn-sm btn-primary w-100"
	                    onclick='viewcanvassing(
	                        <?= json_encode($row["canvass_no"]) ?>,
	                        <?= json_encode($row["pr_no"]) ?>,
	                        <?= json_encode($row["status"]) ?>
	                    )'>
	                    <i class="fa fa-eye"></i> View
	                </button>
	            </td>
	        </tr>
	    <?php endforeach; ?>
	<?php else: ?>
	    <tr>
	        <td colspan="7" class="text-center text-muted">
	            <i class="fa fa-info-circle"></i> Canvassing not available yet
	        </td>
	    </tr>
	<?php endif; ?>
	</tbody>
</table>


<script>
function viewcanvassing(canvasnumber,prnumber,status){
			
	$.post("./Modules/Purchasing_System/apps/pc_review_sheet.php", { canvasnumber: canvasnumber, prnumber: prnumber, status: status },
	function(data) {
		$('#contents').html(data);
	});

	

}



</script>

