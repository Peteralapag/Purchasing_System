<?php


include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

require $_SERVER['DOCUMENT_ROOT']. "/Plugins/PSAMailer/src/PHPMailer.php";
require $_SERVER['DOCUMENT_ROOT']. "/Plugins/PSAMailer/src/SMTP.php";
require $_SERVER['DOCUMENT_ROOT']. "/Plugins/PSAMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// Required fields
$canvassNo   = $data['canvass_no'] ?? '';
$itemId      = $data['item_id'] ?? '';
$supplierId  = $data['supplier_id'] ?? '';
$supplier    = $data['supplier_name'] ?? '';
$supplierEmail = $data['supplier_email'] ?? '';
$itemDesc    = $data['item_desc'] ?? '';
$qtyUnit     = $data['qty_unit'] ?? '';

$token = bin2hex(random_bytes(32));
$tokenExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

$brand       = $data['brand'] ?? '';
$price       = $data['price'] ?? 0;
$remarks     = $data['remarks'] ?? '';
$status      = $data['status'] ?? 'OPTION';

if(empty($canvassNo) || empty($itemId) || empty($supplierId) || empty($supplierEmail)){
    echo json_encode(['status'=>'error','msg'=>'Missing required data']);
    exit;
}

try {
    $mail = new PHPMailer(true);

    // SMTP setup
    $mail->isSMTP();
    $mail->Host       = 'smtp.dreamhost.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'purchasing-test@rosebakeshop.co';
    $mail->Password   = 'Testonly@1g4';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('purchasing-test@rosebakeshop.co', 'Jathnier Corp. Purchaser');
    $mail->addAddress($supplierEmail, $supplier);

    $mail->isHTML(true);
    $mail->Subject = "Canvassing Request #$canvassNo";
	$mail->Body = '
	<table style="width:100%; max-width:600px; margin:auto; font-family:Arial,sans-serif; border-collapse:collapse; border:1px solid #ddd;">
	    <!-- Header with logo left -->
	    <tr style="background-color:#007bff;">
	        <td style="padding:20px;">
	            <table style="width:100%; border-collapse:collapse;">
	                <tr>
	                    <td style="width:80px; vertical-align:middle;">
	                        <img src="http://applications.rosebakeshop.net/Images/logo.png" alt="Jathnier Logo" style="height:60px;">
	                    </td>
	                    <td style="color:white; font-size:20px; font-weight:bold; vertical-align:middle; padding-left:10px;">
	                        Jathnier Corporation Purchasing System
	                    </td>
	                </tr>
	            </table>
	        </td>
	    </tr>
	
	    <!-- Body -->
	    <tr>
	        <td style="padding:20px;">
	            <p>Dear <strong>' . htmlspecialchars($supplier) . '</strong>,</p>
	            <p>We kindly request your quotation for the following item:</p>
	
	            <table style="width:100%; border-collapse:collapse; margin:15px 0;">
	                <tr>
	                    <td style="padding:8px; border:1px solid #ddd;"><strong>Item</strong></td>
	                    <td style="padding:8px; border:1px solid #ddd;">' . htmlspecialchars($itemDesc) . '</td>
	                </tr>
	                <tr>
	                    <td style="padding:8px; border:1px solid #ddd;"><strong>Quantity / Unit</strong></td>
	                    <td style="padding:8px; border:1px solid #ddd;">' . htmlspecialchars($qtyUnit) . '</td>
	                </tr>
	            </table>
	
	            <p>Please submit your quotation by clicking the button below:</p>
	            
	            <p style="text-align:center; margin:30px 0;">
	                <a href="http://psapowerapps.applications.prj/supplier_submit.php?token=' . urlencode($token) . '" 
	                   style="background-color:#28a745; color:white; text-decoration:none; padding:12px 25px; border-radius:5px; display:inline-block; font-weight:bold;">
	                   Submit Quotation
	                </a>
	            </p>
	
	            <p>Thank you for your prompt response.</p>
	
	            <p style="font-size:12px; color:#555;">This is an automated email from Jathnier Corporation Purchasing System.</p>
	        </td>
	    </tr>
	
	    <!-- Footer -->
	    <tr style="background-color:#f1f1f1;">
	        <td style="padding:15px; text-align:center; font-size:12px; color:#777;">
	            &copy; ' . date("Y") . ' Jathnier Corporation. All rights reserved.
	        </td>
	    </tr>
	</table>
	';
	
	
    $mail->send();

    // Email sent successfully -> save/update supplier
    $check = $db->prepare("SELECT id FROM purchase_canvassing_suppliers WHERE canvass_no=? AND canvass_item_id=? AND supplier_id=?");
    $check->bind_param("sii", $canvassNo, $itemId, $supplierId);
    $check->execute();
    $res = $check->get_result();

    if($res->num_rows > 0){
		
		$update = $db->prepare("
		    UPDATE purchase_canvassing_suppliers 
		    SET supplier_name=?, brand=?, price=?, remarks=?, status=?,
		        token=?, token_expires_at=?, token_used=0,
		        email_sent=1, email_sent_at=NOW()
		    WHERE canvass_no=? AND canvass_item_id=? AND supplier_id=?
		");
		$update->bind_param(
		"ssdsssssii",
		$supplier, $brand, $price, $remarks, $status,
		$token, $tokenExpiry,
		$canvassNo, $itemId, $supplierId
		); 
        
        $update->execute();
    } else {
        
        $insert = $db->prepare("
		    INSERT INTO purchase_canvassing_suppliers 
		    (canvass_no, canvass_item_id, supplier_id, supplier_name,
		     brand, price, remarks, status,
		     token, token_expires_at, token_used,
		     email_sent, email_sent_at, created_at)
		    VALUES (?,?,?,?,?,?,?,?,?,?,0,1,NOW(),NOW())
		");
		$insert->bind_param(
		    "siissdssss",
		    $canvassNo, $itemId, $supplierId, $supplier,
		    $brand, $price, $remarks, $status,
		    $token, $tokenExpiry
		);
        
        $insert->execute();
    }

    echo json_encode(['status'=>'success','msg'=>'Email sent and supplier saved']);

} catch (Exception $e) {
    echo json_encode(['status'=>'error','msg'=>'Email failed: '.$mail->ErrorInfo]);
}