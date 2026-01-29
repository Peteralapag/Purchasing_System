<?php
// dashboard_notify.php

include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// COUNT PENDING PO EMAILS
$pe_sql = "SELECT COUNT(*) AS count FROM purchase_order_email_queue WHERE status='pending'";
$pe_stmt = $db->prepare($pe_sql);
$pe_stmt->execute();
$pe_result = $pe_stmt->get_result();
$pending_emails = $pe_result->fetch_assoc()['count'] ?? 0;
$pe_stmt->close();

// RETURN JSON
header('Content-Type: application/json');
echo json_encode([
    'pending_emails' => (int)$pending_emails
]);
exit;