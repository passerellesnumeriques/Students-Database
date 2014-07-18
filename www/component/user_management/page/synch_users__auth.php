<?php 
class page_synch_users__auth extends Page {
	
	public function getRequiredRights() { return array("manage_users"); }
	
	public function execute() {
?>
<table style='background-color:white;padding:10px'>
<tr>
	<td>Synchronize with domain</td>
	<td>
		<select id='domain'>
			<?php foreach (PNApplication::$instance->getDomains() as $domain=>$descr) {
				echo "<option value='".$domain."'".($domain == PNApplication::$instance->local_domain ? " selected='selected'" : "").">".$domain."</option>";
			}?>
		</select>
	</td>
</tr>
<tr id='row_username'>
	<td>Username</td>
	<td><input type='text' id='username'/></td>
</tr>
<tr id='row_password'>
	<td>Password</td>
	<td><input type='password' id='password'/></td>
</tr>
</table>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_frame(window);

var local = <?php echo json_encode(PNApplication::$instance->local_domain);?>;
var local_token = <?php echo json_encode(PNApplication::$instance->user_management->auth_token);?>;

function goNext(domain, token) {
	popup.removeButtons();
	location.href = 'synch_users__list?domain='+encodeURIComponent(domain)+"&token="+encodeURIComponent(token);
}

popup.addNextButton(function() {
	var select = document.getElementById('domain');
	var domain = select.value;
	var username = document.getElementById('username').value;
	var password = document.getElementById('password').value;
	if (domain == local && username == "") {
		goNext(local, local_token);
		return;
	}
	popup.freeze("Authenticating on "+domain+"...");
	service.json("authentication", "auth", {
		domain:domain,
		username: username,
		password: password
	}, function(res) {
		popup.unfreeze();
		if (res && res.token)
			goNext(domain, res.token);
	});
});
popup.addCancelButton();

</script>
<?php 
	}
	
}
?>