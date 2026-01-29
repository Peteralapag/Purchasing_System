<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) die("DB connection failed");

$po_number = $_POST['po_number'] ?? '';

if (!$po_number) {
    echo "<p class='text-danger text-center fw-bold'>No PO number provided.</p>";
    exit;
}

// Fetch PO main info
$stmt = $db->prepare("
    SELECT p.id AS po_id, p.po_number, p.created_at, p.status, s.name AS supplier_name, s.email AS supplier_email
    FROM purchase_orders p
    JOIN suppliers s ON s.id = p.supplier_id
    WHERE p.po_number = ?
    LIMIT 1
");
$stmt->bind_param("s", $po_number);
$stmt->execute();
$po = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$po) {
    echo "<p class='text-danger text-center fw-bold'>PO not found.</p>";
    exit;
}

// Fetch PO items
$stmt = $db->prepare("
    SELECT item_code, description, qty, uom, unit_price, total_price
    FROM purchase_order_items
    WHERE po_id = ?
");
$stmt->bind_param("i", $po['po_id']);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();
?>

<!-- PO Header Info -->
<div class="card mb-4 shadow-sm" style="width: 1000px;">
    <div class="card-body">
        <div class="row mb-2">
            <div class="col-md-6">
                <h5 class="fw-bold">PO Number: <span class="text-primary"><?= htmlspecialchars($po['po_number']) ?></span></h5>
                <p class="mb-1"><strong>Supplier:</strong> <?= htmlspecialchars($po['supplier_name']) ?> (<a href="mailto:<?= htmlspecialchars($po['supplier_email']) ?>"><?= htmlspecialchars($po['supplier_email']) ?></a>)</p>
                <p class="mb-0"><strong>Created At:</strong> <?= date('M d, Y H:i', strtotime($po['created_at'])) ?></p>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <span class="badge <?= $po['status'] == 'pending' ? 'bg-warning text-dark' : ($po['status'] == 'approved' ? 'bg-success' : 'bg-secondary') ?> fs-6">
                    <?= htmlspecialchars(ucfirst($po['status'])) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- PO Items Table -->
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="table-light text-uppercase">
            <tr>
                <th>Item Code</th>
                <th>Description</th>
                <th class="text-end">Quantity</th>
                <th>UOM</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Total Price</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $grandTotal = 0;
        if ($items->num_rows > 0): 
            while($row = $items->fetch_assoc()):
                $grandTotal += $row['total_price'];
        ?>
            <tr>
                <td><?= htmlspecialchars($row['item_code']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td class="text-end"><?= htmlspecialchars($row['qty']) ?></td>
                <td><?= htmlspecialchars($row['uom']) ?></td>
                <td class="text-end"><?= number_format($row['unit_price'], 2) ?></td>
                <td class="text-end"><?= number_format($row['total_price'], 2) ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="6" class="text-center text-muted">No items found</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <th colspan="5" class="text-end">Grand Total</th>
                <th class="text-end"><?= number_format($grandTotal, 2) ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<style>
/* Optional styling to make it more polished */
.table-hover tbody tr:hover {
    background-color: #f1f5f9;
    transition: background 0.2s;
}
.table th, .table td {
    vertical-align: middle;
}
</style>