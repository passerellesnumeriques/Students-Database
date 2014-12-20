<?php 
class page_new_user extends Page {
	
	public function getRequiredRights() { return array("manage_users"); }
	
	public function execute() {
		$as = PNApplication::$instance->authentication->getAuthenticationSystem(PNApplication::$instance->local_domain);
		$this->requireJavascript("form.js");
?>
<div style='background-color:white;padding:10px'>
<form name='new_user' onsubmit='return false;'>
Type of user:<br/>
 <input type='radio' name='type' value='as'<?php if (!($as instanceof AuthenticationSystem_ManageUsers)) echo " disabled='disabled'";?>/> In domain <?php echo PNApplication::$instance->local_domain;?><br/>
 <input type='radio' name='type' value='internal'/> Internal (will only exists in Students Management Software)<br/>
<br/>
Username: <input type='text' name='username'/><br/>
<br/>
Password: <input type='password' name='password'/>
Confirm password: <input type='password' name='password2'/>
</form>
</div>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_frame(window);
popup.addOkCancelButtons(function() {
	var form = document.forms['new_user'];
	var type = getRadioValue(form,'type');
	if (!type) { alert("Please select the type of user"); return; }
	var username = form.elements['username'].value;
	username = username.trim().latinize().toLowerCase();
	form.elements['username'].value = username;
	if (username.length == 0) { alert("Please enter a username"); return; }
	var pass = form.elements['password'].value;
	if (form.elements['password2'].value != pass) { alert("The 2 passwords you entered are different. Please retry."); return; }
	if (type == 'as') {
		alert("TODO");
		// TODO
	} else {
		popup.freeze("Checking username...");
		service.json("user_management","check_username",{username:username},function(res) {
			popup.unfreeze();
			if (!res) return;
			popup_frame(theme.build_icon("/static/user_management/user_16.png",theme.icons_10.add),"New User","/dynamic/people/page/popup_new_person?type=user&ondone=people_created",{
				fixed_data: [
				 	{table:'Users',data:'Domain',value:<?php echo json_encode(PNApplication::$instance->local_domain);?>},
				 	{table:'Users',data:'Username',value:username}
				 ]
			},null,null,function(frame,pop) {
				frame.people_created = function(people_id) {
					popup.freeze("Creating user...");
					service.json("user_management","create_user",{authentication_system:'internal',username:username,password:pass,people_id:people_id},function(res) {
						if (!res) { popup.unfreeze(); return; }
						popup.close();
					});
				};
			});
		});
	}
});
</script>
<?php 
	}
	
}
?>