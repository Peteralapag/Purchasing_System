<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Count pending suppliers for badge
$sql = "
SELECT COUNT(*) AS total_pending
FROM purchase_canvassing_suppliers pcs
JOIN purchase_canvassing pc
    ON pc.canvass_no = pcs.canvass_no
LEFT JOIN purchase_orders po
    ON po.pr_number = pc.pr_no
WHERE (pc.status = 'APPROVED' OR pc.status = 'PARTIAL_PO_CREATED')
  AND pcs.status = 1
  AND pcs.created_po = 0
  AND (po.status IS NULL OR po.status <> 'CANCELLED')

";

$res = $db->query($sql);

if(!$res){
    die("Query Error: " . $db->error);
}

$row = $res->fetch_assoc();

echo json_encode([
    'pending' => (int)$row['total_pending']
]);
