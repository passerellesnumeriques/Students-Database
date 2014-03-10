<?php 
require_once("component/people/PeopleCreatePlugin.inc");
class StudentCreatePlugin implements PeopleCreatePlugin {
	
	public function isValidForTypes($types) { return in_array("student",$types); }
	
	public function getPriority() { return 30; }
	public function getIcon() { return "/static/students/student_16.png"; }
	public function getName() { return "Student"; }
	public function generateSection(&$page) {
?>
<div style="padding:5px">
	Please select a batch for the new student: 
	<select id='student_batch' onchange="window.create_people.tables['Student'][0].value = this.value;">
	<option value=''></option>
	<?php
	$batches = PNApplication::$instance->curriculum->getBatches(); 
	foreach ($batches as $b)
		echo "<option value='".$b["id"]."'>".htmlentities($b["name"])."</option>"; 
	?>
	</select>
</div>
<script type='text/javascript'>
window.create_people.tables["Students"] = [{name:'Batch',value:null}];
window.create_people_validations.push({fct:function(){
	if (window.create_people.tables["Students"][0].value == null || window.create_people.tables["Students"][0].value == '')
		return "Please select a batch"; 
});
</script>
<?php 
	}
	
	public function create($tables_input, $people_id) {
		foreach ($tables_input["Student"] as $i) if ($i["name"] == "Batch") { $batch_id = $i["value"]; break; }
		SQLQuery::create()->insert("Student", array("people"=>$people_id, "batch"=>$batch_id));
	}
	
}
?>