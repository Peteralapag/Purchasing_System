<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$from   = $_POST['from'] ?? '';
$to     = $_POST['to'] ?? '';
$status = $_POST['status'] ?? ''; // status from dropdown

if (!$from || !$to) {
    echo '<div class="text-danger">Invalid date range</div>';
    exit;
}

$fromDate = date('Y-m-d', strtotime($from));
$toDate   = date('Y-m-d', strtotime($to));

// ==========================
// BUILD QUERY
// ==========================
$sql = "
    SELECT 
        pc.canvass_no,
        pc.pr_no,
        pc.requested_by,
        pc.status,
        pc.reviewed_by,
        pc.reviewed_date,
        pc.approved_by,
        pc.approved_date,
        pc.remarks,
        SUM(pci.estimated_cost * pci.quantity) AS total_estimated
    FROM purchase_canvassing pc
    LEFT JOIN purchase_canvassing_items pci ON pci.canvass_no = pc.canvass_no
    WHERE DATE(pc.created_at) BETWEEN ? AND ?
";

$params = [$fromDate, $toDate];
$types  = "ss";

// STATUS FILTER
if ($status !== '') {
    $sql .= " AND pc.status = ?";
    $params[] = $status;
    $types .= "s";
}

// GROUP + ORDER
$sql .= " GROUP BY pc.canvass_no ORDER BY pc.created_at DESC";

// ==========================
// EXECUTE
// ==========================
$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// ==========================
// DISPLAY
// ==========================
$max = 15;
echo '<table class="table table-bordered table-sm">';
echo '<thead>
        <tr>
        	<th>#</th>
            <th>Canvass No.</th>
            <th>P.R. No.</th>
            <th>Requested By</th>
            <th>Status</th>
            <th>Reviewed By</th>
            <th>Reviewed Date</th>
            <th>Approved By</th>
            <th>Approved Date</th>
            <th>Total Estimated</th>
        </tr>
      </thead>';
echo '<tbody>';

$count = 1;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        // format status display: uppercase + replace underscore
        $displayStatus = strtoupper(str_replace('_', ' ', $row['status']));

        echo '<tr>';
        echo '<td>'.$count.'</td>';
        echo '<td>'.htmlspecialchars($row['canvass_no']).'</td>';
        echo '<td>'.htmlspecialchars($row['pr_no']).'</td>';
        echo '<td>'.htmlspecialchars($row['requested_by']).'</td>';
        echo '<td>'.htmlspecialchars($displayStatus).'</td>';
        echo '<td>'.htmlspecialchars($row['reviewed_by']).'</td>';
        echo '<td>'.htmlspecialchars($row['reviewed_date']).'</td>';
        echo '<td>'.htmlspecialchars($row['approved_by']).'</td>';
        echo '<td>'.htmlspecialchars($row['approved_date']).'</td>';
        echo '<td align="right">'.number_format($row['total_estimated'],2).'</td>';
        echo '</tr>';
        
        $count++;
    }
} else {
    echo '<tr><td colspan="9" align="center">No canvassing records found.</td></tr>';
}

echo '</tbody></table>';

$stmt->close();
?>
