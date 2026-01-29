<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$approver = $_SESSION['purch_appnameuser'] ?? '';
$prnumber = $_POST['prnumber'] ?? '';
$status = $_POST['status'] ?? '';

if (empty($prnumber)) {
    echo '<script>swal("Error","PR Number not provided","error");</script>';
    exit;
}

// Fetch PR items
$stmt = $db->prepare("
    SELECT pri.item_type, pri.item_code, pri.item_description, pri.quantity, pri.unit, pri.estimated_cost, pri.total_estimated
    FROM purchase_request_items pri
    JOIN purchase_request pr ON pr.id = pri.pr_id
    WHERE pr.pr_number = ?
");

$stmt->bind_param("s", $prnumber);
$stmt->execute();
$result = $stmt->get_result();
$items = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Fetch PR info (requested_by & approved_by)

$stmt2 = $db->prepare("SELECT requested_by, approved_by, remarks FROM purchase_request WHERE pr_number = ? LIMIT 1");

$stmt2->bind_param("s", $prnumber);
$stmt2->execute();
$stmt2->bind_result($requested_by, $approved_by, $remarks);
$stmt2->fetch();
$stmt2->close();


// Calculate grand total
$grandTotal = 0;
foreach($items as $item){
    $grandTotal += $item['total_estimated'];
}
?>

<table class="table table-bordered mb-0" id="itemsTable">
    <thead class="table-light">
        <tr>
            <th width="3%">#</th>
            <th width="12%">ITEM TYPE</th>
            <th width="12%">ITEM CODE</th>
            <th>ITEM DESCRIPTION</th>
            <th width="8%">QTY</th>
            <th width="8%">Unit</th>
            <th width="12%">Est. Cost</th>
            <th width="12%">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php if(!empty($items)): ?>
            <?php $i=0; foreach($items as $item): $i++; ?>
                <tr>
                    <td><?= $i ?></td>
                    <td><?= htmlspecialchars($item['item_type']) ?></td>
                    <td><?= htmlspecialchars($item['item_code']) ?></td>
                    <td><?= htmlspecialchars($item['item_description']) ?></td>
                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                    <td><?= htmlspecialchars($item['unit']) ?></td>
                    <td><?= number_format($item['estimated_cost'],2) ?></td>
                    <td><?= number_format($item['total_estimated'],2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" class="text-center">No items found.</td></tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="7" class="text-end"><strong>Grand Total:</strong></td>
            <td><strong><?= number_format($grandTotal, 2) ?></strong></td>
        </tr>
    </tfoot>
</table>



<!-- REMARKS SECTION -->
<div class="card mx-3 my-3">
    <div class="card-header py-2 fw-semibold">
        <?= nl2br(htmlspecialchars($remarks ?: 'No remarks provided.')) ?>
    </div>
</div>

<br>

<div style="margin-top:10px; margin-left:20px; margin-right:20px; margin-bottom:50px; display:flex; justify-content:space-between">
    <div>Requested By:<?= htmlspecialchars($requested_by) ?></div>
    <div>Approved By: <?= htmlspecialchars($approved_by ?: '-') ?></div>
</div>

<script>
function approvePR(prnumber,status) {
    swal({
        title: "Confirm Approval",
        text: "Are you sure you want to approve PR: " + prnumber + "?",
        icon: "warning",
        buttons: true,
        dangerMode: false,
    }).then((willApprove) => {
        if (!willApprove) return;

        $.ajax({
            url: "../../../Modules/Purchasing_System/actions/actions.php",
            type: "POST",
            dataType: "json",
            data: { mode: "approvepurchaserequest", prnumber: prnumber },
            success: function(res) {
                console.log("RESPONSE:", res);
                if (res.success) {
                    swal("Approved!", res.message ?? ("PR " + prnumber + " approved"), "success")
                        .then(() => window.location.reload());
                } else {
                    swal("Error", res.message ?? "Approval failed", "error");
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                swal("Error", "Server error occurred", "error");
            }
        });
    });
}
</script>
