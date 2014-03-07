<?php
$span_id = $this->generateID();
$this->add_javascript("/static/widgets/typed_field/typed_field.js");
$this->add_javascript("/static/widgets/typed_field/field_text.js");
?>
Designation of the contact point (job title...):<br/>
<span id='<?php echo $span_id;?>' style='padding:5px'></span>
<script type='text/javascript'>
function create_people_contact_point_section() {
	this.field = new field_text(null,true,{min_length:1,max_length:100,can_be_null:false});
	document.getElementById('<?php echo $span_id;?>').appendChild(this.field.getHTMLElement());
	this.validate_and_save = function() {
		window.create_people.contact_point_designation = this.field.getCurrentData();
		return !this.field.hasError();
	};
}
window.create_people_sections.push(new create_people_contact_point_section());
</script>