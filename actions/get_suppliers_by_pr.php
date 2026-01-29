<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$pr_no = $_GET['pr_no'] ?? '';
if (!$pr_no) exit(json_encode([]));

/* =============================
   1. GET CANVASS NO
============================= */
$canvassStmt = $db->prepare("
    SELECT canvass_no
    FROM purchase_canvassing
    WHERE pr_no = ?
      AND status IN ('APPROVED','PARTIAL_PO_CREATED')
    LIMIT 1
");
$canvassStmt->bind_param("s", $pr_no);
$canvassStmt->execute();
$canvassRes = $canvassStmt->get_result()->fetch_assoc();
$canvass_no = $canvassRes['canvass_no'] ?? '';
$canvassStmt->close();

if (!$canvass_no) exit(json_encode([]));

/* =============================
   2. GET SUPPLIERS NOT YET PO
============================= */
$supStmt = $db->prepare("
    SELECT supplier_id, supplier_name, GROUP_CONCAT(remarks SEPARATOR ', ') AS remarks
    FROM purchase_canvassing_suppliers
    WHERE canvass_no = ?
      AND status = 1
      AND created_po = 0
    GROUP BY supplier_id, supplier_name
    ORDER BY supplier_name ASC
");
$supStmt->bind_param("s", $canvass_no);
$supStmt->execute();
$result = $supStmt->get_result();

$suppliers = [];
while ($row = $result->fetch_assoc()) {
    $suppliers[] = $row;
}
$supStmt->close();

echo json_encode($suppliers);