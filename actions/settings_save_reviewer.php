<?php
include '../../../init.php';
header('Content-Type: application/json');

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$idcode   = $_POST['idcode'] ?? '';
$reviewer = $_POST['reviewer'] ?? 0; // 1 kung i-check, 0 kung dili

if (!$idcode) {
    echo json_encode(['status' => 'error', 'message' => 'IDCODE missing']);
    exit;
}

$app = 'Purchasing System';
$modules_to_update = ['PC Review', 'PO Review'];

// --- Loop and update p_review directly ---
foreach ($modules_to_update as $mod) {
    $upd = $db->prepare("
        UPDATE tbl_system_permission
        SET p_review = ?
        WHERE idcode = ? AND applications = ? AND modules = ?
    ");
    $upd->bind_param("isss", $reviewer, $idcode, $app, $mod);
    $upd->execute();
}

echo json_encode(['status' => 'success', 'message' => 'Reviewer updated successfully']);
