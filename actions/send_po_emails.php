<?php
require '../../../init.php'; // DB connection
require_once $_SERVER['DOCUMENT_ROOT'] . "/Plugins/PSAMailer/src/PHPMailer.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/Plugins/PSAMailer/src/SMTP.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/Plugins/PSAMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// --- Get POST ID ---
$id = $_POST['id'] ?? null;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'No email ID provided']);
    exit;
}

// --- Fetch pending PO email ---
$stmt = $configdb->prepare("SELECT * FROM purchase_order_email_queue WHERE id=? AND status='pending' LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$email = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$email) {
    echo json_encode(['status' => 'error', 'message' => 'Email not found or already sent.']);
    exit;
}

// --- Build HTML body dynamically ---
$poNumber = htmlspecialchars($email['po_number']);
$subject = htmlspecialchars($email['subject']);
$supplierEmail = trim($email['supplier_email']);

$body = '
<table style="width:100%; max-width:600px; margin:auto; font-family:Arial,sans-serif; border-collapse:collapse; border:1px solid #ddd;">
    <tr style="background-color:#007bff;">
        <td style="padding:20px; color:white; font-size:20px; font-weight:bold;">
            Jathnier Corporation Purchasing System
        </td>
    </tr>
    <tr>
        <td style="padding:20px;">
            <p>Dear Supplier,</p>
            <p>Please find your Purchase Order details below:</p>
            <table style="width:100%; border-collapse:collapse; margin:15px 0;">
                <tr>
                    <td style="padding:8px; border:1px solid #ddd;"><strong>PO Number</strong></td>
                    <td style="padding:8px; border:1px solid #ddd;">'.$poNumber.'</td>
                </tr>
            </table>
            <p>You can view your PO by logging into our system or contacting your account manager.</p>
            <p>Thank you for your prompt attention.</p>
            <p style="font-size:12px; color:#555;">This is an automated email from Jathnier Corporation Purchasing System.</p>
        </td>
    </tr>
    <tr style="background-color:#f1f1f1;">
        <td style="padding:15px; text-align:center; font-size:12px; color:#777;">
            &copy; '.date("Y").' Jathnier Corporation. All rights reserved.
        </td>
    </tr>
</table>';

// --- Send email ---
try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.dreamhost.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'purchasing-test@rosebakeshop.co';
    $mail->Password   = 'Testonly@1g4';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('purchasing-test@rosebakeshop.co', 'Jathnier Corp. Purchaser');
    $mail->addAddress($supplierEmail);

    $mail->Subject = $subject;
    $mail->isHTML(true);
    $mail->Body = $body;
    $mail->AltBody = strip_tags($body); // fallback for plain text clients

    // Optional debug
    // $mail->SMTPDebug = 2;
    // $mail->Debugoutput = 'html';

    $mail->send();

    // --- Update DB as sent ---
    $stmt = $configdb->prepare("UPDATE purchase_order_email_queue SET status='sent', sent_at=NOW() WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status' => 'success', 'message' => 'PO email sent successfully!']);

} catch (Exception $e) {
    $error = $mail->ErrorInfo ?? $e->getMessage();

    // Update DB as failed
    $stmt = $configdb->prepare("UPDATE purchase_order_email_queue SET status='failed', error_message=? WHERE id=?");
    $stmt->bind_param('si', $error, $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status' => 'error', 'message' => 'Failed to send email: ' . $error]);
}