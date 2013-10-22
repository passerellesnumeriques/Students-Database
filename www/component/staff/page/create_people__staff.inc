<?php 
// TODO if people_id pre-selected
require_once("staff_info.inc");
$container_id = $this->generate_id();
$fct = staff_info($this,-1,$container_id);
?>

<div style='padding:5px'>
	<div id='<?php echo $container_id;?>'></div>
</div>

<script type='text/javascript'>
var staff = new <?php echo $fct;?>();
if (window.parent.create_people.staff_positions)
	staff.setPositions(window.parent.create_people.staff_positions);
function save_create_people_info(validate) {
	window.parent.create_people.staff_positions = staff.positions;
	if (staff.positions.length == 0 && validate)
		error_dialog("You must enter at least one position");
	return staff.positions.length > 0;
}
</script>