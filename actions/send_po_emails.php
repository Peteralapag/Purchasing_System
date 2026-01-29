<?php
require '../../../init.php'; // DB connection
require_once $_SERVER['DOCUMENT_ROOT'] . "/Plugins/PSAMailer/src/PHPMailer.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/Plugins/PSAMailer/src/SMTP.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/Plugins/PSAMailer/src/Exception.php";

require_once $_SERVER['DOCUMENT_ROOT'] . "/Plugins/dompdf/autoload.inc.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

header('Content-Type: application/json');

// --- Get POST ID ---
$id = $_POST['id'] ?? null;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'No email ID provided']);
    exit;
}

// --- Fetch pending PO email from queue ---
$stmt = $configdb->prepare("SELECT * FROM purchase_order_email_queue WHERE id=? AND status='pending' LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$emailQueue = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$emailQueue) {
    echo json_encode(['status'=>'error','message'=>'Email not found or already sent.']);
    exit;
}

// --- Get PO main info ---
$poNumber = $emailQueue['po_number'] ?? null;
$supplierEmail = trim($emailQueue['supplier_email'] ?? '');
$subject = $emailQueue['subject'] ?? 'Purchase Order';

$stmt = $configdb->prepare("SELECT * FROM purchase_orders WHERE po_number=? LIMIT 1");
$stmt->bind_param('s', $poNumber);
$stmt->execute();
$po = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$po) {
    echo json_encode(['status'=>'error','message'=>'PO not found in purchase_orders table']);
    exit;
}

// --- Get PO items ---
$stmt = $configdb->prepare("SELECT * FROM purchase_order_items WHERE po_id=?");
$stmt->bind_param('i', $po['id']);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Build HTML email body ---



$body = '
<div style="font-family:Arial,sans-serif; max-width:800px; margin:auto; color:#333;">
    <!-- HEADER -->
    <div style="padding:10px 20px;">
        <div style="font-size:18px; font-weight:bold;">Jathnier Corporation</div>
        <div>Ruby St., RGA Village, Dacudao Avenue,</div>
        <div>Davao City, Philippines</div>
    </div>

    <!-- PO TITLE -->
    <div style="padding:10px 20px; text-align:right; font-size:20px; font-weight:bold; margin-bottom:10px;">
        Purchase Order
    </div>

    <!-- PO DETAILS GRID -->
    <table style="width:100%; border-collapse:collapse; margin-bottom:10px; border:1px solid #000;">
        <tr>
            <td style="font-weight:bold; padding:5px; width:12%;">Date</td>
            <td style="padding:5px; width:18%;">'.date('Y-m-d', strtotime($po['order_date'])).'</td>

            <td style="font-weight:bold; padding:5px; width:12%;">P.O. No.</td>
            <td style="padding:5px; width:18%;">'.$po['po_number'].'</td>

            <td style="font-weight:bold; padding:5px; width:12%;">P.R. No.</td>
            <td style="padding:5px; width:18%;">'.$po['pr_number'].'</td>

            <td style="font-weight:bold; padding:5px; width:12%;">Expected</td>
            <td style="padding:5px; width:18%;">'.date('Y-m-d', strtotime($po['expected_delivery'])).'</td>
        </tr>
        <tr>
            <td style="font-weight:bold; padding:5px;">Vendor</td>
            <td style="padding:5px;" colspan="3">'.htmlspecialchars($emailQueue['supplier_name'] ?? 'Vendor').'</td>

            <td style="font-weight:bold; padding:5px;">Ship To</td>
            <td style="padding:5px;" colspan="3">ADMIN CEBU</td>
        </tr>
    </table>

    <!-- ITEMS TABLE -->
    <table style="width:100%; border-collapse:collapse; margin-top:5px; border:1px solid #000;">
        <thead>
            <tr style="background-color:#f1f1f1;">
                <th style="padding:8px; border:1px solid #000;">Item</th>
                <th style="padding:8px; border:1px solid #000;">Description</th>
                <th style="padding:8px; border:1px solid #000; text-align:right;">Qty</th>
                <th style="padding:8px; border:1px solid #000;">U/M</th>
                <th style="padding:8px; border:1px solid #000; text-align:right;">Rate</th>
                <th style="padding:8px; border:1px solid #000; text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>';

foreach ($items as $index => $item) {
    // Alternate row color for readability
    $bgColor = ($index % 2 == 0) ? '#fff' : '#f9f9f9';
    $body .= '<tr style="background-color:'.$bgColor.';">
        <td style="padding:8px; border:1px solid #000;">'.htmlspecialchars($item['item_code']).'</td>
        <td style="padding:8px; border:1px solid #000;">'.htmlspecialchars($item['description']).'</td>
        <td style="padding:8px; border:1px solid #000; text-align:right;">'.number_format($item['qty'],2).'</td>
        <td style="padding:8px; border:1px solid #000;">'.htmlspecialchars($item['uom']).'</td>
        <td style="padding:8px; border:1px solid #000; text-align:right;">'.number_format($item['unit_price'],2).'</td>
        <td style="padding:8px; border:1px solid #000; text-align:right;">'.number_format($item['total_price'],2).'</td>
    </tr>';
}

$body .= '</tbody>
        <!-- PO Remarks & Total -->
        <tfoot>
            <tr>
                <td colspan="4" style="padding:8px; border:1px solid #000; font-weight:bold;">PO remarks</td>
                <td style="padding:8px; border:1px solid #000; font-weight:bold; text-align:right;">Total:</td>
                <td style="padding:8px; border:1px solid #000; font-weight:bold; text-align:right;">'.number_format($po['total_amount'],2).'</td>
            </tr>
        </tfoot>
    </table>

    <!-- SIGNATURES -->
    <table style="width:100%; margin-top:30px; text-align:center;">
        <tr>
            <td style="border-top:1px solid #000; padding-top:5px;">
                <strong>Prepared by</strong><br>
                '.htmlspecialchars($po['prepared_by']).'<br>
                '.date('Y-m-d H:i:s', strtotime($po['prepared_at'] ?? $po['created_at'])).'
            </td>
            <td style="border-top:1px solid #000; padding-top:5px;">
                <strong>Reviewed by</strong><br>
                '.htmlspecialchars($po['reviewed_by']).'<br>
                '.date('Y-m-d H:i:s', strtotime($po['reviewed_at'] ?? $po['created_at'])).'
            </td>
            <td style="border-top:1px solid #000; padding-top:5px;">
                <strong>Approved by</strong><br>
                '.htmlspecialchars($po['approved_by']).'<br>
                '.date('Y-m-d H:i:s', strtotime($po['approved_at'] ?? $po['created_at'])).'
            </td>
        </tr>
    </table>
</div>';




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
    $mail->AltBody = strip_tags($body);


    $mail->send();

    // --- Update DB as sent ---
    $stmt = $configdb->prepare("UPDATE purchase_order_email_queue SET status='sent', sent_at=NOW() WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status'=>'success','message'=>'PO email sent successfully!']);
} catch (Exception $e) {
    $error = $mail->ErrorInfo ?? $e->getMessage();
    $stmt = $configdb->prepare("UPDATE purchase_order_email_queue SET status='failed', error_message=? WHERE id=?");
    $stmt->bind_param('si', $error, $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status'=>'error','message'=>'Failed to send email: ' . $error]);
}