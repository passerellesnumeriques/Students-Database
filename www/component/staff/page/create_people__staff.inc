<?php 
// TODO if people_id pre-selected
require_once("staff_info.inc");
$container_id = $this->generateID();
$fct = staff_info($this,-1,$container_id);
?>

<div style='padding:5px'>
	<div id='<?php echo $container_id;?>'></div>
</div>

<script type='text/javascript'>
function create_people_staff_section() {
	this.staff = new <?php echo $fct;?>();
	if (window.create_people.staff_positions)
		this.staff.setPositions(window.create_people.staff_positions);
	this.validate_and_save = function() {
		window.create_people.staff_positions = this.staff.positions;
		if (this.staff.positions.length == 0)
			error_dialog("You must enter at least one position");
		return this.staff.positions.length > 0;
	};
}
window.create_people_sections.push(new create_people_staff_section());
</script>