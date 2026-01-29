<?php 
include '../../../init.php';

// Database connection
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$applications = 'Purchasing System';
$branch = 'PURCHASING';

// --- Step 1: Fetch all users from PURCHASING branch ---
$userQuery = $db->prepare("
    SELECT idcode, firstname, lastname, company, cluster, branch, department, username
    FROM tbl_system_user 
    WHERE branch = ?
    ORDER BY lastname, firstname
");
$userQuery->bind_param("s", $branch);
$userQuery->execute();
$userResult = $userQuery->get_result();

// --- Step 2: Fetch existing permissions for this application ---
$permQuery = $db->prepare("
    SELECT DISTINCT idcode 
    FROM tbl_system_permission 
    WHERE applications = ?
");
$permQuery->bind_param("s", $applications);
$permQuery->execute();
$permResult = $permQuery->get_result();

// Store permitted users in an array
$permittedUsers = [];
while($perm = $permResult->fetch_assoc()) {
    $permittedUsers[] = $perm['idcode'];
}
?>

<!-- Users Table -->
<div class="table-responsive">
<table class="table table-bordered table-striped table-hover" id="potable">
    <thead class="table-primary">
        <tr>
            <th style="width:5%;">#</th>
            <th>Idcode</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Company</th>
            <th>Cluster</th>
            <th>Branch</th>
            <th>Department</th>
            <th>Username</th>
            <th>Action</th>
        </tr>        
    </thead>
    <tbody>
        <?php
        $i = 1;
        while($user = $userResult->fetch_assoc()):
        ?>
        <tr data-id="<?= htmlspecialchars($user['idcode']) ?>">
            <td><?= $i ?></td>
            <td><?= htmlspecialchars($user['idcode']) ?></td>
            <td><?= htmlspecialchars($user['firstname']) ?></td>
            <td><?= htmlspecialchars($user['lastname']) ?></td>
            <td><?= htmlspecialchars($user['company']) ?></td>
            <td><?= htmlspecialchars($user['cluster']) ?></td>
            <td><?= htmlspecialchars($user['branch']) ?></td>
            <td><?= htmlspecialchars($user['department']) ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td>
                <button class="btn btn-sm btn-primary edit-user-btn" onclick="editroles('<?= $user['idcode']?>')">
                    <i class="fa fa-edit"></i> Edit Roles
                </button>
            </td>
        </tr>
        <?php 
        $i++;
        endwhile; 
        ?>
    </tbody>
</table>
</div>

<script>

function editroles(idcode) {

    $.post("./Modules/Purchasing_System/apps/settings_edit_roles.php", { idcode: idcode }, function(data) {
        $('#smnavdata').html(data);
    });
}

</script>

