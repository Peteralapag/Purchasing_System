<?php 
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$status = $_POST['status'] ?? '';
$limit  = (int)($_POST['limit'] ?? 50);

// =======================
// BUILD QUERY
// =======================
$sql = "SELECT * FROM purchase_request WHERE 1=1";
$params = [];
$types  = "";

// STATUS FILTER
if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
} else {
    // default: hide pending
    $sql .= " AND status = ?";
    $params[] = 'approved';
    $types .= "s";
}

// ORDER + LIMIT
$sql .= " ORDER BY id DESC LIMIT ?";
$params[] = $limit;
$types .= "i";

// =======================
// EXECUTE
// =======================
$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();

$i = 0;

$status_badge = [
    'pending'  => 'badge bg-success',
    'approved' => 'badge bg-danger',
    'rejected' => 'badge bg-success',
    'for_canvassing' => 'badge bg-info',
    'canvassing_reviewed' => 'badge bg-success',
    'canvassing_approved' => 'badge bg-success',
    'partial_conversion' => 'badge bg-success',
    'converted' => 'badge bg-success',
    'convert_rejected' => 'badge bg-success',
];
?>



<table class="table table-bordered table-striped" id="purchaserequesttable">
    <thead>
        <tr>
            <th>#</th>
            <th>PR Number</th>
            <th>Requested By</th>
            <th>Branch</th>
            <th>Department</th>
            <th>Request Date</th>
            <th>Remarks</th>
            <th>Status</th>
            <th class="col-action no-print">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if(!empty($requests)): ?>
            <?php foreach($requests as $req): $i++;
            	
            		$prnumber = $req['pr_number'];
            		$status = $req['status'];
            		$remarks = $req['remarks'];
            		$max = 15;
            ?>
                <tr>
                    <td><?= htmlspecialchars($i) ?></td>
                    <td><?= htmlspecialchars($prnumber) ?></td>
                    <td><?= htmlspecialchars($req['requested_by']) ?></td>
                    <td><?= htmlspecialchars($req['source']) ?></td>
                    <td><?= htmlspecialchars($req['department']) ?></td>
                    <td><?= date('Y-m-d', strtotime($req['request_date'])) ?></td>
                    <td title="<?= $remarks?>"><?= htmlspecialchars(strlen($remarks) > $max ? substr($remarks, 0, $max) . '...' : $remarks)?></td>
                    <td>
                    	<span class="<?= $status_badge[$status] ?>"><?= $status ?></span>
                    </td>
                    <td class="col-action no-print" style="text-align:center">
                    	<button type="button" onclick="vieviapr('<?= $prnumber?>','<?= $status?>')" class="btn btn-primary btn-sm w-100 btn-sm"><i class="fa-solid fa-eye"></i> View</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" class="text-center">No purchase requests found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script>

function vieviapr(prnumber,status) {

	
	$.post("./Modules/Purchasing_System/includes/purchase_request_view.php", { prnumber: prnumber, status: status },
	function(data) {
		$('#contents').html(data);
	});

}

</script>
