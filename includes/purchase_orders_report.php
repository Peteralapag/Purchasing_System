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
        po.approved_by,
        po.approved_date,
        s.name AS supplier_name
    FROM purchase_orders po
    JOIN suppliers s ON s.id = po.supplier_id
    WHERE DATE(po.order_date) BETWEEN ? AND ?
";

$params = [$fromDate, $toDate];
$types  = "ss";

// STATUS FILTER
if ($status !== '') {
    $sql .= " AND po.status = ?";
    $params[] = $status;
    $types .= "s";
}

// ORDER
$sql .= " ORDER BY po.order_date DESC";

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
            <th>PO No.</th>
            <th>P.R. No.</th>
            <th>Branch</th>
            <th>Supplier</th>
            <th>Order Date</th>
            <th>Expected Delivery</th>
            <th>Status</th>
            <th>Total Amount</th>
            <th>Approved By</th>
            <th>Approved Date</th>
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
        echo '<td>'.htmlspecialchars($row['po_number']).'</td>';
        echo '<td>'.htmlspecialchars($row['pr_number']).'</td>';
        echo '<td>'.htmlspecialchars($row['branch']).'</td>';
        echo '<td>'.htmlspecialchars($row['supplier_name']).'</td>';
        echo '<td>'.htmlspecialchars($row['order_date']).'</td>';
        echo '<td>'.htmlspecialchars($row['expected_delivery']).'</td>';
        echo '<td>'.htmlspecialchars($displayStatus).'</td>';
        echo '<td align="right">'.number_format($row['total_amount'],2).'</td>';
        echo '<td>'.htmlspecialchars($row['approved_by']).'</td>';
        echo '<td>'.htmlspecialchars($row['approved_date']).'</td>';
        echo '</tr>';
        $count++;
    }
} else {
    echo '<tr><td colspan="11" align="center">No Purchase Orders found.</td></tr>';
}

echo '</tbody></table>';

$stmt->close();
?>
