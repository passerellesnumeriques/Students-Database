<div style="padding:5px">
	Please select a batch for the new student: 
	<select id='student_batch'>
	<option value=''></option>
	<?php
	$batches = SQLQuery::create()->select("StudentBatch")->order_by("StudentBatch","start_date",false)->execute();
	foreach ($batches as $b)
		echo "<option value='".$b["id"]."'>".htmlentities($b["name"])."</option>"; 
	?>
	</select>
</div>
<script type='text/javascript'>
var batch = document.getElementById('student_batch');
if (window.parent.create_people.student_batch) {
	for (var i = 0; i < batch.options.length; ++i)
		if (batch.options[i].value == window.parent.create_people.student_batch) {
			batch.selectedIndex = i;
			break;
		} 
}
function save_create_people_info(validate) {
	window.parent.create_people.student_batch = batch.value;
	if (window.parent.create_people.student_batch == '') {
		alert("Please select a batch");
		return false;
	}
	return true;
}
</script>