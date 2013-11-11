<?php
// TODO if people_id pre-selected 
$contact_id = $this->generate_id();
$address_id = $this->generate_id();
$this->add_javascript("/static/contact/contacts.js");
$this->add_javascript("/static/contact/addresses.js");
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
function create_people_contacts_section() {
	this.contacts = new contacts('<?php echo $contact_id;?>', "People_contact", "people", -1, window.create_people.contacts ? window.create_people.contacts : [], true, true, true);
	this.addresses = new addresses('<?php echo $address_id;?>', true, "People_address", "people", -1, window.create_people.addresses ? window.create_people.addresses : [], true, true, true);
	this.validate_and_save = function() {
		window.create_people.contacts = this.contacts.getContacts();
		window.create_people.addresses = this.addresses.getAddresses();
		return true;
	};
}
window.create_people_sections.push(new create_people_contacts_section());
</script>