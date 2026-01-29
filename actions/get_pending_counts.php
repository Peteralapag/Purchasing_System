<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Result array
$pending = [];

// Purchase Requests
$res = $db->query("SELECT COUNT(*) AS cnt FROM purchase_request WHERE status='approved'");
$pending['Purchase Requests'] = $res ? (int)$res->fetch_assoc()['cnt'] : 0;

// Purchase Canvassing (combined)
$res = $db->query("
    SELECT
        SUM(status='OPEN' AND prepared_by IS NULL) AS pc_open,
        SUM(status='OPEN' AND prepared_by IS NOT NULL) AS pc_review,
        SUM(status='FOR_APPROVAL') AS pc_approval
    FROM purchase_canvassing
");
$row = $res ? $res->fetch_assoc() : [];
$pending['Purchase Canvassing'] = (int)($row['pc_open'] ?? 0);
$pending['PC Review'] = (int)($row['pc_review'] ?? 0);
$pending['PC Approval'] = (int)($row['pc_approval'] ?? 0);

// Purchase Orders (combined in one query)
$res = $db->query("
    SELECT
        SUM(prepared_by IS NULL AND status='PENDING') AS po_pending,
        SUM(prepared_by IS NOT NULL AND reviewed_by IS NULL AND approved_by IS NULL AND status='PENDING') AS po_review,
        SUM(prepared_by IS NOT NULL AND reviewed_by IS NOT NULL AND approved_by IS NULL AND status='PENDING') AS po_for_approval
    FROM purchase_orders
");
$row = $res ? $res->fetch_assoc() : [];
$pending['Purchase Orders (PO)'] = (int)($row['po_pending'] ?? 0);
$pending['PO Review'] = (int)($row['po_review'] ?? 0);
$pending['PO Approval'] = (int)($row['po_for_approval'] ?? 0);

// Return JSON
header('Content-Type: application/json');
echo json_encode($pending);

$db->close();
