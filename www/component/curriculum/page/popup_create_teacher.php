<?php 
class page_popup_create_teacher extends Page {
	
	public function get_required_rights() { return array("edit_curriculum"); }
	
	public function execute() {
?>
<div style='background-color:white;padding:10px'>
<ul>
<?php 
$q = SQLQuery::create()
	->select("TeacherDates")
	->where("TeacherDates.end < '".SQLQuery::escape($_GET["start"])."'")
	->groupBy("TeacherDates", "people");
PNApplication::$instance->people->joinPeople($q, "TeacherDates", "people");
$teachers = $q->execute();
if (count($teachers) > 0) {
	echo "<li>The teacher already teached before:<ul>";
	foreach ($teachers as $t) {
		echo "<li>";
		echo htmlentities($t["first_name"]." ".$t["last_name"]); // TODO
		echo "</li>";
	}
	echo "</ul></li>";
}

$q = PNApplication::$instance->staff->requestStaffsForDates($_GET["start"], $_GET["end"]);
PNApplication::$instance->people->joinPeople($q, "StaffPosition", "people");
$q->where("People.types NOT LIKE '%/teacher/%'");
$staff = $q->execute();
if (count($staff) > 0) {
	echo "<li>The teacher is a PN staff who didn't teach yet:<ul>";
	foreach ($staff as $s) {
		echo "<li>";
		echo htmlentities($s["first_name"]." ".$s["last_name"]); // TODO
		echo "</li>";
	}
	echo "</ul></li>";
}
?>
	<li><a href='#' onclick='new_person();return false;'>This is a new person</a></li>
</ul>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_frame(window);
function new_person() {
	popup.freeze();
	postData("/dynamic/people/page/popup_create_people?types=teacher&multiple=false&ondone=<?php echo $_GET["ondone"];?>",{
		
	},window);
}
</script>
</div>
<?php 
	}
	
}
?>