<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Get POST parameters
$from   = $_POST['from'] ?? '';
$to     = $_POST['to'] ?? '';
$status = $_POST['status'] ?? ''; // status from dropdown

if (!$from || !$to) {
    echo '<div class="text-danger">Invalid date range</div>';
    exit;
}

$fromDate = date('Y-m-d', strtotime($from));
$toDate   = date('Y-m-d', strtotime($to));

$max = 15;

// ==========================
// BUILD QUERY
// ==========================
$sql = "
    SELECT 
        pr.id,
        pr.pr_number,
        pr.request_date,
        pr.requested_by,
        pr.remarks,
        pr.status,
        pr.approved_by,
        pr.approved_at,
        SUM(ri.total_estimated) AS total_estimated
    FROM purchase_request pr
    LEFT JOIN purchase_request_items ri ON ri.pr_id = pr.id
    WHERE DATE(pr.request_date) BETWEEN ? AND ?
";

$params = [$fromDate, $toDate];
$types  = "ss";

// STATUS FILTER
if ($status !== '') {
    $sql .= " AND pr.status = ?";
    $params[] = $status;
    $types .= "s";
}

// GROUP + ORDER
$sql .= " GROUP BY pr.id ORDER BY pr.request_date DESC";

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
echo '<table class="table table-bordered table-sm">';
echo '<thead>
        <tr>
        	<th>#</th>
            <th>P.R. Number</th>
            <th>Date</th>
            <th>Requested By</th>
            <th>Remarks</th>
            <th>Status</th>
            <th>Approved By</th>
            <th>Approved At</th>
            <th>Total Est.</th>
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
        echo '<td>'.htmlspecialchars($row['pr_number']).'</td>';
        echo '<td>'.htmlspecialchars($row['request_date']).'</td>';
        echo '<td>'.htmlspecialchars($row['requested_by']).'</td>';
        echo '<td title="'.htmlspecialchars($row['remarks']).'">'.htmlspecialchars(mb_strimwidth($row['remarks'], 0, $max, '...')).'</td>';
        echo '<td>'.htmlspecialchars($displayStatus).'</td>';
        echo '<td>'.htmlspecialchars($row['approved_by']).'</td>';
        echo '<td>'.htmlspecialchars($row['approved_at']).'</td>';
        echo '<td align="right">'.number_format($row['total_estimated'],2).'</td>';
        echo '</tr>';
        
        $count++;
    }
} else {
    echo '<tr><td colspan="9" align="center">No purchase requests found.</td></tr>';
}

echo '</tbody></table>';

$stmt->close();
?>
