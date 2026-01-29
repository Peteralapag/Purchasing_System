<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$idcode   = $_POST['idcode'] ?? '';

$modules = ['Dashboard','Suppliers','Purchase Requests','Purchase Canvassing','PC Review','Purchase Orders (PO)','PO Review','Report'];
$applications = 'Purchasing System';

// --- Fetch user by idcode ---
$userResult = $db->query("SELECT idcode, username, password FROM tbl_system_user WHERE idcode='$idcode'");

// --- Fetch permissions for this user and application ---
$permResult = $db->query("SELECT modules, p_view, p_review FROM tbl_system_permission WHERE applications='$applications' AND idcode='$idcode'");
$permissions = [];
while($row = $permResult->fetch_assoc()){
    $permissions[$row['modules']] = $row['p_view']; // key = module
}







//PASSWORDPAGKUHA
/*

class Password {  
	public function encryptedPassword($password,$db)
	{		
		$asin_ang_ulam = "DevelopedAndCodedByRonanSarbon";
		$password_enc = $encrypted_string=openssl_encrypt($password,"AES-256-ECB",$asin_ang_ulam);
		$strHashedPass = mysqli_real_escape_string($db, $password_enc);	
		$strHash = hash( 'sha256', $strHashedPass);
		return $strHash;
	}
}
*/



?>

<!-- Styles -->
<style>
    table.table-bordered td input.perm {
        width: 16px;      /* checkbox width */
        height: 16px;     /* checkbox height */
        margin: 0 auto;   /* center horizontally */
        display: block;   /* needed for margin auto */
        cursor: pointer;
    }

    table.table-bordered td {
        padding: 4px 6px; 
        text-align: center;
    }
    table.table-bordered th {
        text-align: center;
    }

    .form-control-sm {
        padding: 2px 6px;  /* compact inputs */
        font-size: 0.85rem;
    }
</style>

<!-- Table -->
<table class="table table-bordered">
    <thead>
    	<tr>
    		<th colspan="3" style="vertical-align:middle">User</th>
    		<th colspan="8">SIDEBAR MODULES</th>
    	</tr>
        <tr>
        	<th style="vertical-align:middle">Username</th>
            <th style="vertical-align:middle">Password</th>
            <th style="vertical-align:middle">Reviewer</th>

            
            <?php foreach($modules as $mod): ?>
                <th><?= $mod ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php if($user = $userResult->fetch_assoc()): ?>
        <tr data-id="<?= $user['idcode'] ?>">
            <td>
                <input type="text" class="form-control form-control-sm username" value="<?= htmlspecialchars($user['username']) ?>" autocomplete="off">
            </td>
            <td>
                <input type="password" class="form-control form-control-sm password" placeholder="Leave blank if no change" autocomplete="off">
            </td>
            <td>
            	<input type="checkbox" id="reviewer" <?= (!empty($permissions['PC Review']) || !empty($permissions['PO Review'])) ? 'checked' : '' ?>>
            </td>
            <?php foreach($modules as $mod): ?>
                <td>
                    <input type="checkbox" class="perm" data-module="<?= $mod ?>" <?= !empty($permissions[$mod]) ? 'checked' : '' ?>>
                </td>
            <?php endforeach; ?>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<hr>

<div class="mt-2 text-end">
    <button class="btn btn-success btn-sm" onclick="saveRoles('<?= $idcode ?>')">
        <i class="fa fa-save"></i> Save
    </button>
</div>


<script>

$('#reviewer').on('change', function(){

    let idcode = '<?= $idcode ?>'; // user idcode
    let val = $(this).is(':checked') ? 1 : 0;

    $.post('./Modules/Purchasing_System/actions/settings_save_reviewer.php', {
        idcode: idcode,
        reviewer: val
    }, function(res){
        if(res.status==='success'){
            console.log('Reviewer updated');
        } else {
            alert('Error updating reviewer');
        }
    }, 'json');
});

function saveRoles(idcode) {
    let username = $('tr[data-id="'+idcode+'"] .username').val();
    let password = $('tr[data-id="'+idcode+'"] .password').val();
    
    let modules = {};
    $('tr[data-id="'+idcode+'"] .perm').each(function(){
        let mod = $(this).data('module');
        modules[mod] = $(this).is(':checked') ? 1 : 0;
    });

    let data = {
        idcode: idcode,
        username: username,
        password: password,
        modules: modules
    };

    console.log('Saving:', data);

    $.post(
        './Modules/Purchasing_System/actions/settings_save_roles.php',
        data,
        function(res){
            if(res.status === 'success'){
                swal({
                    title: "Success",
                    text: res.message,
                    icon: "success",
                    timer: 1500,
                    buttons: false
                }).then(() => {
                    // clear password field after save
                    $('tr[data-id="'+idcode+'"] .password').val('');
                });
            } else {
                swal({
                    title: "Error",
                    text: res.message,
                    icon: "error"
                });
            }
        },
        'json'
    );
}


</script>
