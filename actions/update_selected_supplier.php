<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$item_id = $_POST['item_id'] ?? '';
$supplier_id = $_POST['selected_supplier_id'] ?? '';
$price = $_POST['selected_price'] ?? '';

if(empty($item_id) || empty($supplier_id)){
    echo json_encode(['status'=>'error','msg'=>'Invalid data']);
    exit;
}

$price = floatval($price);


$stmt = $db->prepare("UPDATE purchase_canvassing_items SET selected_supplier_id = ?, selected_price = ? WHERE id = ?");
$stmt->bind_param("idd", $supplier_id, $price, $item_id);
$stmt->execute();


$stmt2 = $db->prepare("UPDATE purchase_canvassing_suppliers SET status = 0 WHERE canvass_item_id = ?");
$stmt2->bind_param("i", $item_id);
$stmt2->execute();


$stmt3 = $db->prepare("UPDATE purchase_canvassing_suppliers SET status = 1 WHERE canvass_item_id = ? AND supplier_id = ?");
$stmt3->bind_param("ii", $item_id, $supplier_id);
$stmt3->execute();



echo json_encode([
    'status'=>'success',
    'msg'=>'Supplier approved',
    'item_updated'=>$stmt->affected_rows,
    'suppliers_updated'=>$stmt3->affected_rows
]);
