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
function create_people_student_section() {
	this.batch = document.getElementById('student_batch');
	if (window.create_people.student_batch) {
		for (var i = 0; i < this.batch.options.length; ++i)
			if (this.batch.options[i].value == window.create_people.student_batch) {
				this.batch.selectedIndex = i;
				break;
			} 
	}
	this.validate_and_save = function() {
		window.create_people.student_batch = this.batch.value;
		if (window.create_people.student_batch == '') {
			alert("Please select a batch");
			return false;
		}
		return true;
	}
}
window.create_people_sections.push(new create_people_student_section());
</script>