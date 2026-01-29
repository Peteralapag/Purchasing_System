<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);


$

/* ================= HEADER ================= */
$h = $db->prepare("
    SELECT canvass_no, pr_no, requested_by, created_at
    FROM purchase_canvassing 
    WHERE id=?
");
$h->bind_param("i", $id);
$h->execute();
$header = $h->get_result()->fetch_assoc();


$canvass_no = $header['canvass_no'] ?? '';

/* ================= ITEMS ================= */
$items = $db->prepare("
    SELECT item_description, quantity 
    FROM purchase_canvassing_items 
    WHERE canvass_no=?
");
$items->bind_param("s", $canvass_no); // string, NOT id
$items->execute();
$item_res = $items->get_result();
?>

<!-- ================= ITEMS LIST ================= -->
<h4><i class="fa fa-list"></i> Canvass Items</h4>

<table class="table table-bordered">
    <thead class="table-light">
        <tr>
            <th width="5%">#</th>
            <th>Item Description</th>
            <th width="15%">Quantity</th>
        </tr>
    </thead>
    <tbody>
        <?php if($item_res->num_rows > 0): ?>
            <?php $i=1; while($it=$item_res->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($it['item_description']) ?></td>
                <td><?= $it['quantity'] ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3" class="text-center text-muted">
                    No items found
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<hr>

<!-- ================= CANVASS SHEET ================= -->
<div style="border:1px solid #000; padding:20px; font-family:Arial">

    <h4 class="text-center"><b>CANVASS SHEET</b></h4>

    <table width="100%" style="margin-bottom:15px">
        <tr>
            <td><b>DATE:</b> <?= date('Y-m-d', strtotime($header['created_at'])) ?></td>
            <td><b>PR/MRS #:</b> <?= htmlspecialchars($header['pr_no']) ?></td>
        </tr>
        <tr>
            <td><b>BRANCH:</b> <?= htmlspecialchars($header['branch']) ?></td>
            <td><b>CANVASS NO:</b> <?= htmlspecialchars($header['canvass_no']) ?></td>
        </tr>
    </table>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>SUPPLIER</th>
                <th width="25%">PRICE</th>
                <th width="25%">BRAND</th>
            </tr>
        </thead>
        <tbody>
            <!-- Blank rows (same as physical form) -->
            <tr><td>&nbsp;</td><td></td><td></td></tr>
            <tr><td>&nbsp;</td><td></td><td></td></tr>
            <tr><td>&nbsp;</td><td></td><td></td></tr>
            <tr><td>&nbsp;</td><td></td><td></td></tr>
        </tbody>
    </table>

    <br>

    <table width="100%">
        <tr>
            <td>
                <b>PREPARED BY:</b><br><br>
                <?= htmlspecialchars($header['requested_by']) ?>
            </td>
            <td>
                <b>REVIEWED BY:</b><br><br>
                ______________________
            </td>
            <td>
                <b>APPROVED BY:</b><br><br>
                ______________________
            </td>
        </tr>
    </table>

</div>
