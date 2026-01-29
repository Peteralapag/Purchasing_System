<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$token = $_GET['token'] ?? '';

if(!$token){
    die('Invalid access');
}

// Fetch supplier & item details based on token
$stmt = $db->prepare("
    SELECT pcs.id, pcs.canvass_no, pcs.canvass_item_id, pcs.supplier_id, pcs.supplier_name,
           pci.item_description, pci.quantity, pci.unit, pcs.brand, pcs.price, pcs.remarks, pcs.submitted_at
    FROM purchase_canvassing_suppliers pcs
    INNER JOIN purchase_canvassing_items pci ON pcs.canvass_item_id = pci.id
    WHERE pcs.token = ?
    LIMIT 1
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$supplierData = $result->fetch_assoc();

if(!$supplierData){
    die('Invalid or expired token.');
}

// If already submitted, show a message
if($supplierData['submitted_at']){
    die('You have already submitted your quotation. Thank you.');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Quotation</title>
</head>
<body>
<h3>Submit Quotation for <?= htmlspecialchars($supplierData['item_description']) ?> (<?= $supplierData['quantity'].' '.$supplierData['unit'] ?>)</h3>
<form id="supplierForm">
    <label>Brand:</label><br>
    <input type="text" name="brand" value="<?= htmlspecialchars($supplierData['brand']) ?>" required><br><br>

    <label>Price:</label><br>
    <input type="number" name="price" value="<?= htmlspecialchars($supplierData['price']) ?>" min="0.01" step="0.01" required><br><br>

    <label>Remarks:</label><br>
    <textarea name="remarks"><?= htmlspecialchars($supplierData['remarks']) ?></textarea><br><br>

    <input type="hidden" name="supplier_id" value="<?= $supplierData['supplier_id'] ?>">
    <input type="hidden" name="item_id" value="<?= $supplierData['canvass_item_id'] ?>">
    <input type="hidden" name="canvass_no" value="<?= $supplierData['canvass_no'] ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <button type="submit">Submit Quotation</button>
</form>

<div id="msg"></div>

<script>
document.getElementById('supplierForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);

    fetch('supplier_submit_backend.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(resp => {
        const msgDiv = document.getElementById('msg');
        if(resp.status === 'success'){
            msgDiv.innerHTML = '<span style="color:green">'+resp.msg+'</span>';
            this.querySelector('button').disabled = true;
        } else {
            msgDiv.innerHTML = '<span style="color:red">'+resp.msg+'</span>';
        }
    })
    .catch(err => {
        console.error(err);
    });
});
</script>
</body>
</html>