<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$po = $_POST['po_number'] ?? '';
$user = $_SESSION['purch_appnameuser'] ?? '';

if(!$po || !$user){
    echo json_encode(['status'=>'error','msg'=>'Invalid request']);
    exit;
}

$stmt = $db->prepare("
    UPDATE purchase_orders
    SET prepared_by = ?, prepared_date = NOW()
    WHERE po_number = ? AND prepared_by IS NULL
");
$stmt->bind_param("ss", $user, $po);
$stmt->execute();

if($stmt->affected_rows > 0){
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error','msg'=>'PO already prepared or not found']);
}

$stmt->close();
$db->close();
