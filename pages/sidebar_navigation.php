<?php
include '../../../init.php';
include '../../../Plugins/reloader/reloader.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
$user_level = $_SESSION['purch_userlevel'] ?? 0;
?>

<style>
.sidebar-nav { list-style-type:none; margin:0;padding:0 }
.navpadleft {margin-left:10px;cursor:pointer; width:100%;}
.sidebar-nav li { display: flex; padding:5px 5px; border-bottom: 1px solid #aeaeae; width:100%; gap: 15px; cursor:pointer; align-items:center; }
.sidebar-nav li:hover {background:#e7e7e7;}
.sidebar-nav .nav-icon {width:30px;text-align:center;font-size:18px;}
.sidebar-nav span {flex:1;}
.active-nav {background: #dcdfe0;}
.nav-bottom-btn { position:absolute; bottom: 2px; margin-left:3px; width: 98%; }
.badge-notify {
	background: red;
	color: white;
	font-size: 10px;
	font-weight: bold;
	padding: 2px 5px;
	border-radius: 50%;
	display:inline-block;
	margin-left:5px;
}
</style>

<ul class="sidebar-nav">
<?php

$sqlMenu = "SELECT * FROM purch_navigation WHERE active=1 ORDER BY ordering ASC";
$MenuResults = mysqli_query($db, $sqlMenu);    
if ($MenuResults->num_rows > 0) {
	$m = 0;
	while($MENUROW = mysqli_fetch_array($MenuResults)) {
        // Check user permission for this menu
        $showMenu = true; // default for admin
        if($user_level < 80) {
            $username = $_SESSION['purch_username'] ?? '';
            $permCheck = "SELECT p_view 
                          FROM tbl_system_permission 
                          WHERE username='$username' 
                          AND modules='{$MENUROW['menu_name']}' 
                          AND applications='Purchasing System'";
            $permRes = mysqli_query($db, $permCheck);
            if($permRes->num_rows > 0) {
                $permRow = mysqli_fetch_assoc($permRes);
                if($permRow['p_view'] != 1) {
                    $showMenu = false;
                }
            } else {
                $showMenu = false;
            }
        }
				
        if(!$showMenu) continue; // skip this menu if no view permission

		$m++;
?>
	<li id="nav<?php echo $m; ?>" data-nav="nav<?php echo $m; ?>"
	    onclick="Check_Permissions('p_view', openMenuGranted, '<?php echo $MENUROW['page_name']; ?>', '<?php echo $MENUROW['menu_name']; ?>')">
		<div class="nav-icon"><i class="<?php echo $MENUROW['icon_class']; ?>"></i></div>
		<span class="menu-label" data-menu="<?php echo $MENUROW['menu_name']; ?>">
			<?php echo $MENUROW['menu_name']; ?>
			<span class="badge-notify" style="display:none">0</span>
		</span>
	</li>
<?php
	}
} else { 
	echo "<li>Menu is Empty.</li>"; 
}


?>
</ul>

<div class="btn-group nav-bottom-btn" role="group" aria-label="Ronan Sarbon">
	<button class="btn btn-secondary" onclick="wmsSettings()">Settings <i class="fa-solid fa-gear"></i></button>
	<button class="btn btn-danger" onclick="closeApps()">Exit Application <i class="fa-solid fa-right-from-bracket"></i></button>
</div>

<script>
function wmsSettings() {
	var user_level = '<?php echo $user_level; ?>';
	if(user_level >= 80) {
		$.post("./Modules/Purchasing_System/pages/wms_settings.php", {}, function(data) {
			$('#contents').html(data);
		});
	}
}

function openMenuGranted(page) {
	psaSpinnerOn();
	$.post("./Modules/Purchasing_System/pages/menu_pages.php", { page: page }, function(data) {
		$('#contents').html(data);
		psaSpinnerOff();
	});
}

$(function() {
	// Highlight previous active menu
	if(sessionStorage.navpcs && sessionStorage.navpcs !== 'null') {
		$("#"+sessionStorage.navpcs).addClass('active-nav').trigger('click');
	}

	$('.sidebar-nav li').click(function() {
		var tab_id = $(this).attr('data-nav');
		sessionStorage.setItem("navpcs", tab_id);
		$('.sidebar-nav li').removeClass('active-nav');
		$(this).addClass('active-nav');
	});
});

function closeApps() {
	dialogue_confirm("Warning", "Are you sure to close Warehouse Management System!", "warning", "closeAppsYes", "", "red");
}
function closeAppsYes() {
	$.post("./Modules/Purchasing_System/actions/close_applications.php", {}, function(data) {
		$('#contents').html(data);
	});
}

// -----------------------------
// Live Badge Updates
// -----------------------------
function updateBadges() {
	$.getJSON("./Modules/Purchasing_System/actions/get_pending_counts.php", function(data){

		$('.menu-label').each(function(){
			var menuName = $(this).data('menu');
			var badgeEl = $(this).find('.badge-notify');

			if(data[menuName] && data[menuName] > 0){
				badgeEl.text(data[menuName]).show();
			} else {
				badgeEl.hide();
			}
		});

	}).fail(function(xhr){
		console.error("Badge AJAX error:", xhr.responseText);
	});
}

// Initial load
updateBadges();
// Refresh badges every 10 seconds
setInterval(updateBadges, 10000);
</script>

<script src="../Modules/Purchasing_System/scripts/script.js"></script>
