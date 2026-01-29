<?php
include '../../../init.php';
header('Content-Type: application/json');

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$pr_no        = $_POST['pr_no'] ?? '';
$canvass_no   = $_POST['canvass_no'] ?? '';
$action       = $_POST['action'] ?? ''; // REVIEWED | APPROVED | REJECTED
$remarks      = $_POST['remarks'] ?? '';
$approver     = $_SESSION['purch_appnameuser'] ?? 'Unknown';

if(!$pr_no || !$canvass_no || !$action){
    echo json_encode(['status'=>'error','msg'=>'Invalid parameters']);
    exit;
}

/* ================= CHECK LOCK ================= */
$chk = $db->prepare("
    SELECT reviewed_by, approved_by
    FROM purchase_canvassing
    WHERE canvass_no = ?
");
$chk->bind_param("s",$canvass_no);
$chk->execute();
$st = $chk->get_result()->fetch_assoc();

if(!$st){
    echo json_encode(['status'=>'error','msg'=>'Canvass not found']);
    exit;
}

/* ================= ROLE MAPPING ================= */
$role = '';
if($action === 'REVIEWED')  $role = 'HEAD';
if($action === 'APPROVED')  $role = 'OWNER';
if($action === 'REJECTED')  $role = 'HEAD';

if(!$role){
    echo json_encode(['status'=>'error','msg'=>'Invalid action']);
    exit;
}

/* ================= UPSERT APPROVAL ================= */
$ins = $db->prepare("
    INSERT INTO purchase_canvassing_approval
    (pr_no, canvass_no, approver_name, approver_role, action, remarks, action_date)
    VALUES (?,?,?,?,?,?,NOW())
    ON DUPLICATE KEY UPDATE
        action = VALUES(action),
        remarks = VALUES(remarks),
        action_date = NOW()
");
$ins->bind_param(
    "ssssss",
    $pr_no,
    $canvass_no,
    $approver,
    $role,
    $action,
    $remarks
);
$ins->execute();

/* ================= UPDATE MAIN TABLE ================= */
if($action === 'REVIEWED'){
    if(!empty($st['reviewed_by'])){
        echo json_encode(['status'=>'error','msg'=>'Already reviewed']);
        exit;
    }

    $up = $db->prepare("
        UPDATE purchase_canvassing
        SET reviewed_by = ?, reviewed_date = NOW(), status = 'FOR_APPROVAL'
        WHERE canvass_no = ?
    ");
    $up->bind_param("ss", $approver, $canvass_no);
    $up->execute();

    // Update purchase_request status
    $updPR = $db->prepare("
        UPDATE purchase_request
        SET status = 'canvassing_reviewed'
        WHERE pr_number = ?
    ");
    $updPR->bind_param("s", $pr_no);
    $updPR->execute();
}


elseif($action === 'APPROVED'){
    if(empty($st['reviewed_by'])){
        echo json_encode(['status'=>'error','msg'=>'Must be reviewed first']);
        exit;
    }
    if(!empty($st['approved_by'])){
        echo json_encode(['status'=>'error','msg'=>'Already approved']);
        exit;
    }

    $up = $db->prepare("
        UPDATE purchase_canvassing
        SET approved_by = ?, approved_date = NOW(), status = 'APPROVED'
        WHERE canvass_no = ?
    ");
    $up->bind_param("ss", $approver, $canvass_no);
    $up->execute();

    // Update purchase_request status
    $updPR = $db->prepare("
        UPDATE purchase_request
        SET status = 'canvassing_approved'
        WHERE pr_number = ?
    ");
    $updPR->bind_param("s", $pr_no);
    $updPR->execute();
}

else{
    echo json_encode(['status'=>'error','msg'=>'Unsupported action']);
    exit;
}

$up->bind_param("ss", $approver, $canvass_no);
$up->execute();

echo json_encode(['status'=>'success']);
exit;
