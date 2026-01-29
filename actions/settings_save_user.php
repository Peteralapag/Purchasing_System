<?php

header('Content-Type: application/json');
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$response = ['status'=>'error','message'=>'Unknown error'];

$idcode   = $_POST['idcode'] ?? '';
$username = $_POST['username'] ?? '';

if (!$idcode || !$username) {
    $response['message'] = 'Missing required fields';
    echo json_encode($response); exit;
}


function encryptedPassword($password,$db)
{		
	$asin_ang_ulam = "DevelopedAndCodedByRonanSarbon";
	$password_enc = $encrypted_string=openssl_encrypt($password,"AES-256-ECB",$asin_ang_ulam);
	$strHashedPass = mysqli_real_escape_string($db, $password_enc);	
	$strHash = hash( 'sha256', $strHashedPass);
	return $strHash;
}


// check existing user
$check = $db->prepare("SELECT id FROM tbl_system_user WHERE idcode=?");
$check->bind_param("s",$idcode);
$check->execute();
$check->store_result();
if ($check->num_rows>0) {
    $response['message'] = 'User already exists';
    echo json_encode($response); exit;
}

// get employee
$empStmt = $db->prepare("SELECT company, cluster, branch, department, firstname, lastname, CONCAT(firstname,' ',lastname) AS acctname FROM tbl_employees WHERE idcode=? LIMIT 1");
$empStmt->bind_param("s",$idcode);
$empStmt->execute();
$empResult = $empStmt->get_result();
if ($empResult->num_rows===0){
    $response['message'] = 'Employee not found';
    echo json_encode($response); exit;
}
$emp = $empResult->fetch_assoc();

// default password
$defaultPassword = encryptedPassword('1234', $db);

// insert user
$insert = $db->prepare("
    INSERT INTO tbl_system_user
    (company, cluster, branch, department, idcode, firstname, lastname, acctname, username, password, role, level)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
");

$role = 'User';
$levelInt = 10;

$insert->bind_param(
    "sssssssssssi",
    $emp['company'],
    $emp['cluster'],
    $emp['branch'],
    $emp['department'],
    $idcode,
    $emp['firstname'],
    $emp['lastname'],
    $emp['acctname'],
    $username,
    $defaultPassword,
    $role,
    $levelInt
);

if ($insert->execute()){

    // --- INSERT DEFAULT PERMISSION ---
    $applications = 'Application Management';
    $modules      = 'Main';
    $p_view       = 1;

    $permStmt = $db->prepare("
        INSERT INTO tbl_system_permission
        (idcode, acctname, username, userlevel, applications, modules, p_view)
        VALUES (?,?,?,?,?,?,?)
    ");
    $permStmt->bind_param(
        "sssissi",
        $idcode,
        $emp['acctname'],
        $username,
        $levelInt,
        $applications,
        $modules,
        $p_view
    );
    $permStmt->execute();

    $response['status']='success';
    $response['message']='User added successfully';

} else {
    $response['message']='Failed to add user: '.$insert->error;
}

echo json_encode($response);
exit;
