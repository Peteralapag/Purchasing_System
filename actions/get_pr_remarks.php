<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$pr_no = $_GET['pr_no'] ?? '';
if(!$pr_no){
    echo json_encode(['remarks'=>'']);
    exit;
}

$stmt = $db->prepare("SELECT remarks FROM purchase_request WHERE pr_number = ? LIMIT 1");
$stmt->bind_param("s", $pr_no);
$stmt->execute();
$stmt->bind_result($remarks);
$stmt->fetch();
$stmt->close();

echo json_encode(['remarks' => $remarks]);
