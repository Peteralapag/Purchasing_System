<?php
// dashboard_notify.php

include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// Today’s date
$today = date('Y-m-d');

// ==========================
// COUNT NEW APPROVED POs
// ==========================
$po_sql = "SELECT COUNT(*) AS count FROM purchase_orders WHERE status='approved' AND DATE(approved_date) = ?";
$po_stmt = $db->prepare($po_sql);
$po_stmt->bind_param("s", $today);
$po_stmt->execute();
$po_result = $po_stmt->get_result();
$po_count = $po_result->fetch_assoc()['count'] ?? 0;
$po_stmt->close();

// ==========================
// COUNT NEW APPROVED PRs
// ==========================
$pr_sql = "SELECT COUNT(*) AS count FROM purchase_request WHERE status='approved' AND DATE(approved_at) = ?";
$pr_stmt = $db->prepare($pr_sql);
$pr_stmt->bind_param("s", $today);
$pr_stmt->execute();
$pr_result = $pr_stmt->get_result();
$pr_count = $pr_result->fetch_assoc()['count'] ?? 0;
$pr_stmt->close();

// ==========================
// COUNT NEW APPROVED Canvassing
// ==========================
$pc_sql = "SELECT COUNT(*) AS count FROM purchase_canvassing WHERE status='approved' AND DATE(approved_date) = ?";
$pc_stmt = $db->prepare($pc_sql);
$pc_stmt->bind_param("s", $today);
$pc_stmt->execute();
$pc_result = $pc_stmt->get_result();
$pc_count = $pc_result->fetch_assoc()['count'] ?? 0;
$pc_stmt->close();

// ==========================
// RETURN JSON
// ==========================
header('Content-Type: application/json');
echo json_encode([
    'po' => (int)$po_count,
    'pr' => (int)$pr_count,
    'pc' => (int)$pc_count
]);
exit;
?>
