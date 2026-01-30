<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require '../../../init.php'; // DB connection
require_once $_SERVER['DOCUMENT_ROOT'] . "/Plugins/PSAMailer/src/PHPMailer.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/Plugins/PSAMailer/src/SMTP.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/Plugins/PSAMailer/src/Exception.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/Plugins/PSAdompdf/autoload.inc.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

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
$emailQueue = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$emailQueue) {
    echo json_encode(['status'=>'error','message'=>'Email not found or already sent.']);
    exit;
}

// --- Get PO main info ---
$poNumber = $emailQueue['po_number'];
$supplierEmail = trim($emailQueue['supplier_email'] ?? '');
$subject = $emailQueue['subject'] ?? 'Purchase Order';

$stmt = $configdb->prepare("
    SELECT po.*, s.name AS supplier_name
    FROM purchase_orders po
    JOIN suppliers s ON s.id = po.supplier_id
    WHERE po.po_number=? LIMIT 1
");
$stmt->bind_param('s', $poNumber);
$stmt->execute();
$po = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$po) {
    echo json_encode(['status'=>'error','message'=>'PO not found']);
    exit;
}

// --- Get PO items ---
$stmt = $configdb->prepare("
    SELECT item_code, description, qty, uom, unit_price, total_price
    FROM purchase_order_items 
    WHERE po_id=?
");
$stmt->bind_param('i', $po['id']);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Logo path ---
$logoPath = '../../../Images/jathnier_logo.png';
$logoCid  = 'companylogo';

// --- Encode logo as base64 for PDF ---
$logoData = base64_encode(file_get_contents($logoPath));
$pdfLogoBase64 = 'data:image/png;base64,' . $logoData;

// --- Build HTML body ---
$body = '
<div style="font-family:Arial,sans-serif; max-width:800px; margin:auto; color:#333;font-size:10px;">

    <!-- HEADER -->
    <div style="padding:10px 20px; text-align:left;">
        <img src="cid:'.$logoCid.'" style="height:40px; margin-bottom:5px;"><br>
        <strong style="font-size:14px;">Jathnier Corporation</strong><br>
        Ruby St., RGA Village, Dacudao Avenue,<br>
        Davao City, Philippines
    </div>

    <div style="padding:10px 20px; text-align:right; font-size:20px; font-weight:bold; margin-bottom:10px;">
        Purchase Order
    </div>

    <!-- PO INFO -->
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
            <td style="padding:5px;" colspan="3">'.htmlspecialchars($po['supplier_name']).'</td>

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
        <tfoot>
            <tr>
                <td colspan="4" style="padding:8px; border:1px solid #000; font-weight:bold;">PO remarks: '.htmlspecialchars($po['remarks']).'</td>
                <td style="padding:8px; border:1px solid #000; font-weight:bold; text-align:right;">Total:</td>
                <td style="padding:8px; border:1px solid #000; font-weight:bold; text-align:right;">'.number_format($po['total_amount'],2).'</td>
            </tr>
        </tfoot>
    </table>

    <!-- SIGNATURES -->
    <table style="width:100%; margin-top:40px; text-align:center; border-collapse:collapse;">
        <tr>
            <td style="width:33%; border-top:1px solid #000; padding-top:5px;">
                Prepared by<br><strong>'.htmlspecialchars($po['prepared_by']).'</strong><br>
                <small>'.htmlspecialchars($po['prepared_date']).'</small>
            </td>
            <td style="width:33%; border-top:1px solid #000; padding-top:5px;">
                Reviewed by<br><strong>'.htmlspecialchars($po['reviewed_by']).'</strong><br>
                <small>'.htmlspecialchars($po['reviewed_date']).'</small>
            </td>
            <td style="width:33%; border-top:1px solid #000; padding-top:5px;">
                Approved by<br><strong>'.htmlspecialchars($po['approved_by']).'</strong><br>
                <small>'.htmlspecialchars($po['approved_date']).'</small>
            </td>
        </tr>
    </table>
</div>';

// --- Prepare PDF version ---
$pdfBody = str_replace('cid:'.$logoCid, $pdfLogoBase64, $body);

try {
    // --- PHPMailer setup ---
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

    // --- Embed logo for email ---
    $mail->addEmbeddedImage($logoPath, $logoCid, 'jathnier_logo.png');

    // --- Generate PDF ---
    $dompdf = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->set_option('isHtml5ParserEnabled', true);
    $dompdf->loadHtml($pdfBody);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfFileName = 'PO_'.$po['po_number'].'.pdf';
    $pdfPath = sys_get_temp_dir().'/'.$pdfFileName;
    file_put_contents($pdfPath, $dompdf->output());

    $mail->addAttachment($pdfPath, $pdfFileName);
    $mail->send();

    if (file_exists($pdfPath)) unlink($pdfPath);

    // --- Mark as sent ---
    $stmt = $configdb->prepare("UPDATE purchase_order_email_queue SET status='sent', sent_at=NOW() WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    ob_clean();
    echo json_encode(['status'=>'success','message'=>'PO email sent successfully with PDF!']);

} catch (Exception $e) {
    $error = $mail->ErrorInfo ?? $e->getMessage();
    $stmt = $configdb->prepare("UPDATE purchase_order_email_queue SET status='failed', error_message=? WHERE id=?");
    $stmt->bind_param('si', $error, $id);
    $stmt->execute();
    $stmt->close();

    ob_clean();
    echo json_encode(['status'=>'error','message'=>'Failed to send email: '.$error]);
}
