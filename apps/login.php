<?php session_start(); ?>
<link rel="stylesheet" href="../Modules/Purchasing_System/styles/styles.css">
<div class="login-wrapper">
	<div class="input-box">
		<span class="icon"><i class="fa-solid fa-user"></i></span>
		<input id="uname" type="text" required value="<?php echo $_SESSION['application_username']; ?>" disabled>
		<!-- label>Username</label -->
	</div>
	<input id="uname" type="hidden" required value="<?php echo $_SESSION['application_username']; ?>" disabled>
	<div class="input-box">
		<span class="icon"><i class="fa-solid fa-key"></i></span>
		<input id="upass" type="password" required>
		<label>Password</label>
	</div>
	<div class="login-button">
		<button id="submitlogin" class="btn btn-info btn-lg w-100 color-white" onclick="PushLogin()">Sign In</button>
	</div>
<div class="loginresults"></div>
</div>
<script>
$(function()
{
	document.getElementById('upass').focus();
	$('#upass').on('keyup', function(event)
	{
		if (event.keyCode === 13) {
			$('#submitlogin').trigger('click');
		}
	}); 
});
function PushLogin()
{
	var mode = 'c10cc6b684e1417e8ffa924de1e58373';
	var usr = $('#uname').val();
	var psw = $('#upass').val();
	if(usr == '')
	{
		app_alert("Login Error","Invalid Username","warning","Ok","uname","focus");
		return false;
	}
	else if(psw == '')
	{
		app_alert("Login Error","Invalid Password","warning","Ok","upass","focus");
		return false;
	}
	$('#submitlogin').html('Signing In... <i class="fa fa-spinner fa-spin"></i>');
	$('#submitlogin').attr('disabled', true);
 	setTimeout(function()
 	{
	 	$.post("./Modules/Purchasing_System/actions/login_process.php", { mode: mode, username: usr, password: psw },
		function(data) {
			$('.loginresults').html(data);
			$('#submitlogin').html('Sign In');
			$('#submitlogin').attr('disabled', false);
		});
	},2000);
}
</script>