<?php
// TODO if people_id pre-selected 
require_once("contact.inc");
$contact_id = $this->generate_id();
$contact_fct = contact($this,"people",$contact_id,-1);
require_once("address.inc");
$address_id = $this->generate_id();
$address_fct = address($this,"people",$address_id,-1);
?>

<div style='padding:5px'>
	<table>
		<tr>
			<td valign=top id='<?php echo $contact_id;?>'></td>
			<td valign=top id='<?php echo $address_id;?>'></td>
		</tr>
	</table>
</div>

<script type='text/javascript'>
var contact = new <?php echo $contact_fct;?>(function(contact) {
	if (window.parent.create_people.contacts) {
		contact.setResult(window.parent.create_people.contacts);
	}
});
var address = new <?php echo $address_fct;?>(function(address) {
	if (window.parent.create_people.addresses) {
		address.setResult(window.parent.create_people.addresses);
	}
});
function save_create_people_info(validate) {
	window.parent.create_people.contacts = contact.result;
	window.parent.create_people.addresses = address.result;
	return true;
}
</script>