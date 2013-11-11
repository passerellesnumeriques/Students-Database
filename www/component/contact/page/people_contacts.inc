<?php 
$contact_div_id = $this->generate_id();
$address_div_id = $this->generate_id();
echo "<table><tr><td valign=top id='$contact_div_id'></td><td valign=top id='$address_div_id'></td></tr></table>";
require_once("contact.inc");
contact($this, "people", $contact_div_id, $people_id);
require_once("address.inc");
address($this, "people", $address_div_id, $people_id);
?>