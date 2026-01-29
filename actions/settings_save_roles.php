<?php
include '../../../init.php';
header('Content-Type: application/json');

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$idcode   = $_POST['idcode'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$modules  = $_POST['modules'] ?? [];

if(!$idcode || !$username){
    echo json_encode(['status'=>'error','message'=>'IDCODE or username missing']);
    exit;
}

// --- Encrypt old system password if provided ---
function encryptedPassword($password, $db){
    $key = "DevelopedAndCodedByRonanSarbon";
    $enc = openssl_encrypt($password,"AES-256-ECB",$key);
    return hash('sha256', mysqli_real_escape_string($db,$enc));
}

// --- Update username & password in tbl_system_user ---
if(!empty($password)){
    $enc = encryptedPassword($password, $db);
    $stmt = $db->prepare("UPDATE tbl_system_user SET username=?, password=? WHERE idcode=?");
    $stmt->bind_param("sss", $username, $enc, $idcode);
    $stmt->execute();
} else {
    $stmt = $db->prepare("UPDATE tbl_system_user SET username=? WHERE idcode=?");
    $stmt->bind_param("ss", $username, $idcode);
    $stmt->execute();
}

// --- Get acctname & userlevel from tbl_system_user ---
$q = $db->prepare("SELECT acctname, level FROM tbl_system_user WHERE idcode=?");
$q->bind_param("s", $idcode);
$q->execute();
$res = $q->get_result();
$user = $res->fetch_assoc();
$acctname = $user['acctname'] ?? '';
$userlevel = $user['level'] ?? 10;

// --- UPDATE username + acctname sa tanan tbl_system_permission rows for this user ---
$upd_all = $db->prepare("UPDATE tbl_system_permission SET username=?, acctname=? WHERE idcode=?");
$upd_all->bind_param("sss", $username, $acctname, $idcode);
$upd_all->execute();

// --- Loop modules and update p_view (and handle Reviewer separately if needed) ---
$app = 'Purchasing System';


foreach($modules as $mod => $p_val){
    $p_view_val = $p_val ? 1 : 0;

    $chk = $db->prepare("SELECT id FROM tbl_system_permission WHERE idcode=? AND applications=? AND modules=?");
    $chk->bind_param("sss", $idcode, $app, $mod);
    $chk->execute();
    $chk->store_result();

    if($chk->num_rows > 0){
        $upd = $db->prepare("UPDATE tbl_system_permission SET p_view=? WHERE idcode=? AND applications=? AND modules=?");
        $upd->bind_param("isss", $p_view_val, $idcode, $app, $mod);
        $upd->execute();
    } else {
        $ins = $db->prepare("INSERT INTO tbl_system_permission (idcode, acctname, username, userlevel, applications, modules, p_view) VALUES (?,?,?,?,?,?,?)");
        $ins->bind_param("sssissi", $idcode, $acctname, $username, $userlevel, $app, $mod, $p_view_val);
        $ins->execute();
    }
}

echo json_encode(['status'=>'success','message'=>'User roles saved successfully']);
