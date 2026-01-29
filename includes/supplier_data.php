<?php 
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$query = "SELECT * FROM suppliers ORDER BY name ASC";
$result = $db->query($query);

$suppliers = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}
$i = 0;
?>

<table class="table table-bordered table-striped" id="suppliertable">
    <thead>
        <tr>
            <th>#</th>
            <th>Supplier Code</th>
            <th>Name</th>
            <th>Address</th>
            <th>TIN</th>
            <th>Payment Terms</th>
            <th>Contact Person</th>
            <th>Phone / Mobile</th>
            <th>Email</th>
            <th>Tax Type</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if(!empty($suppliers)): ?>
            <?php foreach($suppliers as $sup): $i++; ?>
                <tr>
                    <td><?= $i ?></td>
                    <td><?= htmlspecialchars($sup['supplier_code']) ?></td>
                    <td><?= htmlspecialchars($sup['name']) ?></td>
                    <td><?= htmlspecialchars($sup['address']) ?></td>
                    <td><?= htmlspecialchars($sup['tin']) ?></td>
                    <td><?= htmlspecialchars($sup['payment_terms']) ?></td>
                    <td><?= htmlspecialchars($sup['contact_person']) ?></td>
                    <td><?= htmlspecialchars($sup['person_contact']) ?></td>
                    <td><?= htmlspecialchars($sup['email']) ?></td>
                    <td><?= htmlspecialchars($sup['tax_type']) ?></td>
                    <td><?= $sup['status'] == 1 ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm w-100" onclick="editSupplier('<?= $sup['id'] ?>')">
                            <i class="fa fa-pencil"></i> Edit
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="11" class="text-center">No suppliers found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>


<script>

function editSupplier(params){
	
	$('#modaltitle').html("MODIFY SUPPLIER");
	$.post("./Modules/Purchasing_System/apps/supplier_edit.php", { params: params },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
</script>
