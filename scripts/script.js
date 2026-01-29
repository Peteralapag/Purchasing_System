function returnPage()
{
	$('#' + sessionStorage.navpcs).trigger('click');
}
function Check_Access(params,permission,action)
{
	var module = sessionStorage.module_name;
	$.post("./Modules/Property_Custodian_Systemt/actions/check_permissions.php", { permission: permission, module: module },
	function(data) {
		if(data == 1)
		{
			action(params);
		}
		else if(data == 0)
		{
			swal("Access Denied","You have insufficient access. Please contact System Administrator","warning");
		}
	});
}
function Check_Permissions(permission,action,page,module)
{
	sessionStorage.setItem("page_name", page);
	sessionStorage.setItem("module_name", module);	
	$.post("./Modules/Purchasing_System/actions/check_permissions.php", { permission: permission, module: module },
	function(data) {
		if(data == 1)
		{
			action(page);
		}
		else if(data == 0)
		{
			swal("Access Denied","You have insufficient access. Please contact System Administrator","warning");
		}
	});
}
function dialogue_confirm(dialogtitle,dialogmsg,dialogicon,command,params,btncolor)
{
	if(btncolor == null || btncolor == '') 
	{
		var btncolor = '';
	} else {
		var btncolor = btncolor;
	}
	swal({
		title: dialogtitle,
		text: dialogmsg,
		icon: dialogicon,
		buttons: [
		'No',
		'Yes'
		],
		dangerMode: btncolor,
	}).then(function(isConfirm) {
		if (isConfirm)
		{
			
			if(command == 'resetDataYes')
			{
				resetDataYes();
			}
			if(command == 'closeAppsYes')
			{
				closeAppsYes();
			}
			if(command == 'closeReceivingYes')
			{
				closeReceivingYes(params);
			}
			if(command == 'deleteTransferYes')
			{
				deleteTransferYes(params);
			}
			if(command == 'requestReopenYes')
			{
				requestReopenYes(params);
			}
			if(command == 'removeAssignmentYes')
			{
				removeAssignmentYes();
			}
			if(command == 'deleteItemYes')
			{
				deleteItemYes(params);
			}
		}
	});
}
function validation_alert(p_title,p_text,p_icon,p_button_text,aydi,command)
{
	swal({
		title: p_title,
		text: p_text + "!",
		icon: p_icon,
		button: p_button_text,
	}).then(function()	{
		if(command == 'focus')
		{
			if(aydi != '')
			{
				document.getElementById(aydi).focus();
				document.getElementById(aydi).style.backgroundColor = "#fef2f2";
			} else {
				document.getElementById(aydi).style.backgroundColor = "";
			}
		}
	});		
}
