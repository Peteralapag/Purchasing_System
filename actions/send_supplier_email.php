<?php

ini_set('display_errors', 0); // hide warnings in output
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json');


include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$canvass_no = $_POST['canvass_no'] ?? '';
$item_id     = $_POST['item_id'] ?? '';
$supplier_id = $_POST['supplier_id'] ?? '';

header('Content-Type: application/json');

if(!$canvass_no || !$item_id || !$supplier_id){
    echo json_encode(['status'=>'error','msg'=>'Missing required data']);
    exit;
}

// Get supplier email and name
$stmt = $db->prepare("SELECT name, email FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$supplier = $stmt->get_result()->fetch_assoc();

if(!$supplier || empty($supplier['email'])){
    echo json_encode(['status'=>'error','msg'=>'Supplier email not found']);
    exit;
}

// Optional: get item info for email body
$item_stmt = $db->prepare("SELECT item_description, quantity, unit FROM purchase_canvassing_items WHERE id = ?");
$item_stmt->bind_param("i", $item_id);
$item_stmt->execute();
$item = $item_stmt->get_result()->fetch_assoc();

// Prepare email
$to = $supplier['email'];
$subject = "Canvass Request: $canvass_no";
$message = "Dear {$supplier['supplier_name']},\n\n";
$message .= "You have been selected for canvass item:\n";
$message .= "- Item: {$item['item_description']}\n";
$message .= "- Quantity: {$item['quantity']} {$item['unit']}\n\n";
$message .= "Please provide your offer by clicking the link below:\n";
$message .= "https://yourdomain.com/supplier_response.php?canvass_no=$canvass_no&item_id=$item_id&supplier_id=$supplier_id\n\n";
$message .= "Thank you.";

// Headers
$headers = "From: no-reply@yourdomain.com\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send email
if(mail($to, $subject, $message, $headers)){

    // Optional: mark in DB that email has been sent
    $upd = $db->prepare("INSERT INTO purchase_canvassing_email_log (canvass_no, item_id, supplier_id, sent_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE sent_at = NOW()");
    $upd->bind_param("sii", $canvass_no, $item_id, $supplier_id);
    $upd->execute();

    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error','msg'=>'Failed to send email']);
}
