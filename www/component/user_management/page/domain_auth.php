<?php 
class page_domain_auth extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$input = json_decode($_POST["input"], true);
?>
<table style='background-color:white;padding:10px'>
<tr>
	<td>Authenticate on domain</td>
	<td>
		<?php
		if (isset($input["domain"])) {
			echo $input["domain"];
			echo "<input type='hidden' id='domain' value='".$input["domain"]."'/>";
		} else { ?>
			<select id='domain' onchange='domainChanged();'>
				<?php foreach (PNApplication::$instance->getDomains() as $domain=>$descr) {
					echo "<option value='".$domain."'".($domain == PNApplication::$instance->local_domain ? " selected='selected'" : "").">".$domain."</option>";
				}?>
			</select>
		<?php } ?>
	</td>
</tr>
<tr>
	<td colspan=2 id='domain_message' style='display:none'></td>
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

var domains = {<?php
$first = true;
foreach (PNApplication::$instance->getDomains() as $domain=>$descr) {
	if ($first) $first = false; else echo ",";
	echo json_encode($domain).":";
	if (!isset($input["feature"])) echo "true";
	else {
		$as = PNApplication::$instance->authentication->getAuthenticationSystem($domain);
		$cl = get_class($as);
		$cl = new ReflectionClass($cl);
		if ($cl->implementsInterface($input["feature"])) echo "true"; else echo "false";
	}
}
?>};

function domainChanged() {
	var support = domains[document.getElementById('domain').value];
	var msg = document.getElementById('domain_message');
	if (support) {
		msg.style.display = 'none';
	} else {
		msg.style.display = '';
		msg.innerHTML = "<img src='"+theme.icons_16.warning+"' style='vertical-align:bottom'/> This domain does not support this feature";
	}
	layout.changed(msg);
}

<?php if (isset($input["domain"])) echo "domainChanged();";?>

function goNext(domain, token) {
	popup.removeButtons();
	postData(<?php echo json_encode($input["url"]);?>,{domain:domain,token:token},window);
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