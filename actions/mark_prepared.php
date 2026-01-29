<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$canvass_no = $_POST['canvass_no'] ?? '';
$action     = $_POST['action'] ?? '';
$username   = $_SESSION['purch_appnameuser'] ?? '';

header('Content-Type: application/json');

if(empty($canvass_no) || $action !== 'PREPARED'){
    echo json_encode(['status'=>'error', 'msg'=>'Invalid request']);
    exit;
}

/* ================= CHECK HEADER ================= */
$stmt = $db->prepare("
    SELECT prepared_by, approved_by 
    FROM purchase_canvassing 
    WHERE canvass_no = ?
");
$stmt->bind_param("s", $canvass_no);
$stmt->execute();
$header = $stmt->get_result()->fetch_assoc();

if(!$header){
    echo json_encode(['status'=>'error','msg'=>'Canvass not found']);
    exit;
}

if(!empty($header['prepared_by'])){
    echo json_encode(['status'=>'error','msg'=>'Canvass already marked as prepared']);
    exit;
}

if(!empty($header['approved_by'])){
    echo json_encode(['status'=>'error','msg'=>'Canvass already approved']);
    exit;
}

/* ================= VALIDATE ITEMS ================= */
$chk = $db->prepare("
    SELECT 
        canvass_item_id,
        COUNT(*) AS total_suppliers,
        SUM(status = 1) AS approved_count
    FROM purchase_canvassing_suppliers
    WHERE canvass_no = ?
    GROUP BY canvass_item_id
");
$chk->bind_param("s", $canvass_no);
$chk->execute();
$res = $chk->get_result();

if($res->num_rows === 0){
    echo json_encode(['status'=>'error','msg'=>'No suppliers found for this canvass']);
    exit;
}

while($row = $res->fetch_assoc()){

    if($row['total_suppliers'] < 3){
        echo json_encode([
            'status'=>'error',
            'msg'=>'Each item must have at least 3 suppliers.'
        ]);
        exit;
    }

    if($row['total_suppliers'] > 5){
        echo json_encode([
            'status'=>'error',
            'msg'=>'Each item must have maximum of 5 suppliers.'
        ]);
        exit;
    }

    if($row['approved_count'] != 1){
        echo json_encode([
            'status'=>'error',
            'msg'=>'Each item must have exactly 1 approved supplier.'
        ]);
        exit;
    }
}

/* ================= MARK AS PREPARED ================= */
$upd = $db->prepare("
    UPDATE purchase_canvassing 
    SET prepared_by = ?, prepared_date = NOW() 
    WHERE canvass_no = ?
");
$upd->bind_param("ss", $username, $canvass_no);

if($upd->execute()){
    echo json_encode(['status'=>'success']);
}else{
    echo json_encode(['status'=>'error','msg'=>'Failed to mark as prepared']);
}
