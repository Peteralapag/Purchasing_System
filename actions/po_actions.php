<?php
include_once '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

require_once $_SERVER['DOCUMENT_ROOT']. "/Plugins/PSAMailer/src/PHPMailer.php";
require_once $_SERVER['DOCUMENT_ROOT']. "/Plugins/PSAMailer/src/SMTP.php";
require_once $_SERVER['DOCUMENT_ROOT']. "/Plugins/PSAMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mode     = $_POST['mode'] ?? '';
$ponumber = $_POST['ponumber'] ?? '';
$user     = $_SESSION['purch_appnameuser'] ?? '';

if (empty($ponumber)) {
    exit('Invalid PO number');
}

if (empty($user)) {
    exit('User not logged in');
}

/* ===== CHECK PO STATUS ===== */
$stmt = $db->prepare("SELECT id, po_number, pr_number, status, supplier_id FROM purchase_orders WHERE po_number = ? LIMIT 1");
$stmt->bind_param("s", $ponumber);
$stmt->execute();
$po = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$po) {
    exit('PO not found');
}

/* ===== APPROVE PO ===== */
if ($mode === 'approvepo') {

    if ($po['status'] === 'APPROVED') {
        exit('PO already approved');
    }

    if ($po['status'] === 'CANCELLED') {
        exit('Cannot approve cancelled PO');
    }

    // UPDATE PO status
    $stmt = $db->prepare("
        UPDATE purchase_orders
        SET 
            status = 'APPROVED',
            approved_by = ?,
            approved_date = NOW(),
            updated_at = NOW(),
            updated_by = ?
        WHERE po_number = ?
    ");
    $stmt->bind_param("sss", $user, $user, $ponumber);

    if ($stmt->execute()) {
        echo "PO approved successfully<br>";

        // ==========================
        // FETCH SUPPLIER
        // ==========================
        if (!empty($po['supplier_id'])) {
            $supplier_id = (int) $po['supplier_id'];
            $stmt2 = $db->prepare("SELECT id, name, email FROM suppliers WHERE id = ?");
            $stmt2->bind_param("i", $supplier_id);
            $stmt2->execute();
            $supplier = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();

            if ($supplier && !empty($supplier['email'])) {
                try {
                    // PHPMailer
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.dreamhost.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'purchasing-test@rosebakeshop.co';
                    $mail->Password   = 'Testonly@1g4';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;

                    $mail->setFrom('purchasing-test@rosebakeshop.co', 'Jathnier Corp. Purchaser');
                    $mail->addAddress($supplier['email'], $supplier['name']);

                    $mail->isHTML(true);
                    $mail->Subject = "Purchase Order #".$po['po_number'];

                    // ===== LAST WORKING EMAIL FORMAT =====
                    $body = "
                        Dear {$supplier['name']},<br><br>
                        You have been selected as the supplier for PO #{$po['po_number']}.<br><br>
                        Please check your order and prepare for delivery.
                    ";
                    $mail->Body = $body;

                    $mail->send();
                    echo "DEBUG: Email sent successfully.<br>";

                } catch (Exception $e) {
                    echo "DEBUG: Email failed: ".$mail->ErrorInfo."<br>";
                }
            } else {
                echo "DEBUG: Supplier email missing.<br>";
            }
        }

    } else {
        exit("Failed to approve PO: ".$stmt->error);
    }

    $stmt->close();
    $db->close();
    exit;
}

/* ===== REVIEW, CLOSE, VOID PO LOGIC ===== */
/* Keep the rest of your original logic for reviewpo, closepo, voidpo here without touching */





/* ===== REVIEW PO ===== */
if ($mode === 'reviewpo') {

    if ($po['status'] === 'CANCELLED') {
        exit('Cannot review cancelled PO');
    }

    if (!empty($po['reviewed_by'])) {
        exit('PO already reviewed');
    }

    $stmt = $db->prepare("
        UPDATE purchase_orders
        SET 
            reviewed_by = ?,
            reviewed_date = NOW(),
            updated_at = NOW(),
            updated_by = ?
        WHERE po_number = ?
    ");
    $stmt->bind_param("sss", $user, $user, $ponumber);

    if ($stmt->execute()) {
        echo "PO marked as reviewed successfully";
    } else {
        exit("Failed to mark PO as reviewed: ".$stmt->error);
    }

    $stmt->close();
    $db->close();
    exit;
}



//close PO

if ($mode === 'closepo') {

    // Step 0: fetch PO
    $stmt = $db->prepare("SELECT status, closed_po FROM purchase_orders WHERE po_number = ?");
    $stmt->bind_param("s", $ponumber);
    $stmt->execute();
    $po = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$po) {
        exit('PO not found');
    }

    // PREVENT invalid close
    if ($po['closed_po'] == 1) {
        exit('PO already closed');
    }

    if (!in_array($po['status'], ['PARTIAL_RECEIVED'])) {
        exit('Only PARTIAL_RECEIVED PO can be closed');
    }

    // CLOSE PO
    $stmt = $db->prepare("
        UPDATE purchase_orders
        SET 
            closed_po = 1,
            closed_by = ?,
            closed_date = NOW(),
            updated_at = NOW(),
            updated_by = ?
        WHERE po_number = ?
    ");
    $stmt->bind_param("sss", $user, $user, $ponumber);

    if ($stmt->execute()) {
        echo "PO has been closed successfully";
    } else {
        exit("Failed to close PO: ".$stmt->error);
    }
    $stmt->close();
    $db->close();
    exit;
}


/* ===== VOID PO ===== */

if ($mode === 'voidpo') {

    // Step 0: fetch PO with receiving check
    $stmt = $db->prepare("
        SELECT 
            pr_number,
            status,
            closed_po
        FROM purchase_orders 
        WHERE po_number = ?
    ");
    $stmt->bind_param("s", $ponumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        exit('PO not found');
    }

    $po = $result->fetch_assoc();
    $stmt->close();

    /* ===============================
       1. PREVENT INVALID VOID
    ================================ */

    if ($po['status'] === 'CANCELLED') {
        exit('PO already voided');
    }

    if (in_array($po['status'], ['PARTIAL_RECEIVED','RECEIVED'])) {
        exit('Cannot void PO with received items');
    }

    /* ===============================
       2. VOID PO (CANCEL + CLOSE)
    ================================ */
    $stmt = $db->prepare("
        UPDATE purchase_orders
        SET 
            status = 'CANCELLED',
            closed_po = 1,
            closed_by = ?,
            closed_date = NOW(),
            approved_by = NULL,
            approved_date = NULL,
            updated_at = NOW(),
            updated_by = ?
        WHERE po_number = ?
    ");
    $stmt->bind_param("sss", $user, $user, $ponumber);

    if (!$stmt->execute()) {
        exit("Failed to void PO: ".$stmt->error);
    }
    $stmt->close();

    /* ===============================
       3. UPDATE PURCHASE REQUEST
    ================================ */
    if (!empty($po['pr_number'])) {
        $stmt2 = $db->prepare("
            UPDATE purchase_request
            SET 
                status = 'rejected',
                updated_at = NOW(),
                updated_by = ?
            WHERE pr_number = ?
        ");
        $stmt2->bind_param("ss", $user, $po['pr_number']);

        if (!$stmt2->execute()) {
            exit("Failed to update purchase request: ".$stmt2->error);
        }
        $stmt2->close();
    }

    $db->close();
    echo 'PO has been voided successfully';
    exit;
}


exit('Invalid action');
