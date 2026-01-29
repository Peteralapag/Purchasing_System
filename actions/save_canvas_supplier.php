<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$canvass_no = $_POST['canvass_no'] ?? '';
$item_id    = $_POST['item_id'] ?? '';
$supplier_id= $_POST['supplier_id'] ?? '';
$supplier   = $_POST['supplier'] ?? '';
$brand      = $_POST['brand'] ?? '';
$price      = $_POST['price'] ?? 0;
$remarks    = $_POST['remarks'] ?? '';
$status     = $_POST['status'] ?? 'OPTION';

if(empty($canvass_no) || empty($item_id) || empty($supplier_id)){
    echo 'Missing required data';
    exit;
}

// Check if this supplier already exists for this item
$check = $db->prepare("SELECT id FROM purchase_canvassing_suppliers WHERE canvass_no=? AND canvass_item_id=? AND supplier_id=?");
$check->bind_param("sii", $canvass_no, $item_id, $supplier_id);
$check->execute();
$res = $check->get_result();

if($res->num_rows > 0){
    // Already exists, do update
    $update = $db->prepare("UPDATE purchase_canvassing_suppliers SET supplier_name=?, brand=?, price=?, remarks=?, status=? WHERE canvass_no=? AND canvass_item_id=? AND supplier_id=?");
    $update->bind_param("ssdssiii", $supplier, $brand, $price, $remarks, $status, $canvass_no, $item_id, $supplier_id);
    if($update->execute()){
        echo 'Supplier updated successfully';
    } else {
        echo 'Update failed: ' . $db->error;
    }
} else {
    // Insert new supplier
    $insert = $db->prepare("INSERT INTO purchase_canvassing_suppliers (canvass_no, canvass_item_id, supplier_id, supplier_name, brand, price, remarks, status, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
    $insert->bind_param("siissdss", $canvass_no, $item_id, $supplier_id, $supplier, $brand, $price, $remarks, $status);
    if($insert->execute()){
        echo 'Supplier added successfully';
    } else {
        echo 'Insert failed: ' . $db->error;
    }
}
