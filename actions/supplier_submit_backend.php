<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$token      = $_POST['token'] ?? '';
$brand      = trim($_POST['brand'] ?? '');
$price      = floatval($_POST['price'] ?? 0);
$remarks    = $_POST['remarks'] ?? '';
$item_id    = $_POST['item_id'] ?? '';
$supplier_id= $_POST['supplier_id'] ?? '';
$canvass_no = $_POST['canvass_no'] ?? '';

if(!$token || !$brand || $price <= 0){
    echo json_encode(['status'=>'error','msg'=>'Brand and price are required, price must be >0']);
    exit;
}

// Check token validity
$stmt = $db->prepare("SELECT id, submitted_at FROM purchase_canvassing_suppliers WHERE token=? AND canvass_item_id=? AND supplier_id=? LIMIT 1");
$stmt->bind_param("sii", $token, $item_id, $supplier_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if(!$data){
    echo json_encode(['status'=>'error','msg'=>'Invalid token or supplier data']);
    exit;
}

if($data['submitted_at']){
    echo json_encode(['status'=>'error','msg'=>'You have already submitted your quotation']);
    exit;
}

// Update submission
$update = $db->prepare("UPDATE purchase_canvassing_suppliers SET brand=?, price=?, remarks=?, submitted_at=NOW() WHERE id=?");
$update->bind_param("sdsi", $brand, $price, $remarks, $data['id']);

if($update->execute()){
    echo json_encode(['status'=>'success','msg'=>'Quotation submitted successfully']);
} else {
    echo json_encode(['status'=>'error','msg'=>'Failed to submit quotation']);
}