<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

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
$stmt = $db->prepare("SELECT status FROM purchase_orders WHERE po_number = ? LIMIT 1");
$stmt->bind_param("s", $ponumber);
$stmt->execute();
$po = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$po) {
    exit('PO not found');
}


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


/* ===== APPROVE PO ===== */
if ($mode === 'approvepo') {

    if ($po['status'] === 'APPROVED') {
        exit('PO already approved');
    }

    if ($po['status'] === 'CANCELLED') {
        exit('Cannot approve cancelled PO');
    }

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
        echo 'PO approved successfully';
    } else {
        echo 'Failed to approve PO';
    }

    $stmt->close();
    $db->close();
    exit;
}



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
