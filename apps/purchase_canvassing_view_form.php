<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$canvasnumber = $_POST['canvassnumber'] ?? '';

$sql = "
    SELECT item_code, item_description, quantity, unit
    FROM purchase_canvassing_items
    WHERE canvass_no = ?
";
$stmt = $db->prepare($sql);
$stmt->bind_param("s", $canvasnumber);
$stmt->execute();
$result = $stmt->get_result();
?>

<table class="table table-bordered mb-0">
    <thead class="table-light">
        <tr>
            <th>ITEM CODE</th>
            <th>ITEM DESCRIPTION</th>
            <th>QTY</th>
            <th>UNIT</th>
        </tr>
    </thead>
    <tbody>
        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['item_code']) ?></td>
                <td><?= htmlspecialchars($row['item_description']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= htmlspecialchars($row['unit']) ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" class="text-center text-muted">No items found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="mt-3 text-end">
    <button class="btn btn-primary" onclick="viewCanvassSheet('<?= $canvasnumber ?>')">
        <i class="fa fa-file-text"></i> View Canvass Sheet
    </button>
</div>

<script>
function viewCanvassSheet(canvass_no){
    
    $.post("./Modules/Purchasing_System/apps/purchase_canvassing_sheet.php", { canvass_no: canvass_no },
	function(data) {
		$('#contents').html(data);
	});
    
}
</script>
