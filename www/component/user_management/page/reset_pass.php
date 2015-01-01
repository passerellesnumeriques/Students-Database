<?php 
class page_reset_pass extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$domain = $_GET["domain"];
		$username = $_GET["username"];
		$input = array();
		if (isset($_POST["input"])) $input = json_decode($_POST["input"],true);
		if ($domain <> PNApplication::$instance->local_domain || $username <> PNApplication::$instance->user_management->username || PNApplication::$instance->user_management->domain <> PNApplication::$instance->local_domain) {
			if (!PNApplication::$instance->user_management->has_right("manage_users")) {
				PNApplication::error("Access denied");
				return;
			}
		}
?>
<table style='background-color:white'>
	<tr>
		<td>Enter new password</td>
		<td><input type='password' id='new_pass'/></td>
	</tr>
	<tr>
		<td>Confirm password</td>
		<td><input type='password' id='confirm'/></td>
	</tr>
</table>
<script type='text/javascript'>
var popup = window.parent.getPopupFromFrame(window);
popup.addOkCancelButtons(function() {
	var new_pass = document.getElementById('new_pass').value;
	var confirm = document.getElementById('confirm').value;
	if (new_pass.length < 6) { alert("Please choose a password with at least 6 characters"); return; }
	if (confirm != new_pass) { alert("The 2 passwords you entered are different. Please retry."); return; }
	popup.freeze("Resetting password...");
	service.json("user_management","reset_password",{
		username: <?php echo json_encode($username);?>,
		password: new_pass
		<?php
		if ($domain <> PNApplication::$instance->local_domain) echo ",domain:".json_encode($domain);
		if (isset($input["token"]))
			echo ",token:".json_encode($input["token"]);
		?>
	},function(res){
		if (!res)
			popup.unfreeze();
		else
			popup.close();
	});
});
</script>
<?php 
	}
	
}
?>