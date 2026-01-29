<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$id = $_POST['id'] ?? 0;

$query = $db->prepare("SELECT * FROM purchase_canvassing WHERE id = ?");

$items_query = $db->prepare("SELECT * FROM purchase_canvassing WHERE id = ?");


$suppliers_query = $db->prepare("SELECT * FROM purchase_canvassing_suppliers WHERE id=?");

?>

<div class="canvass-sheet" style="font-family:sans-serif;">
    <h3 style="text-align:center;">CANVASS SHEET</h3>
    <p><strong>DATE:</strong> <?= $row['created_at'] ?></p>
    <p><strong>ITEM DESCRIPTION:</strong> <?= htmlspecialchars($row['item_description']) ?></p>
    <p><strong>QUANTITY:</strong> <?= $row['quantity'] ?></p>
    <p><strong>BRANCH:</strong> <?= htmlspecialchars($row['branch']) ?></p>
    <p><strong>PR/MRS #:</strong> <?= htmlspecialchars($row['pr_no']) ?></p>

    <table class="table table-bordered" style="width:100%; border-collapse:collapse; margin-top:15px;">
        <thead>
            <tr>
                <th>SUPPLIER</th>
                <th>PRICE</th>
                <th>BRAND</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>

    <div style="display:flex; justify-content:space-between; margin-top:20px;">
        <div><strong>PREPARED BY:</strong><br><?= htmlspecialchars($row['requested_by']) ?></div>
        <div><strong>REVIEWED BY:</strong><br><?= htmlspecialchars($row['reviewed_by'] ?? '-') ?></div>
        <div><strong>APPROVED BY:</strong><br><?= htmlspecialchars($row['approved_by'] ?? '-') ?></div>
    </div>
</div>
