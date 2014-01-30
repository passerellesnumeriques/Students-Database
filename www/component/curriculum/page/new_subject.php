<?php 
class page_new_subject extends Page {
	
	public function get_required_rights() { return array("edit_curriculum"); }
	
	public function execute() {
		?>
		<div>
		<form name='new_subject' onsubmit='return false;'>
		<div id='error' style='color:red;height:20px'></div>
		Create a new subject:<br/>
		<input type='radio' name='choice' value='new' checked='checked'/>
		Code <input type='text' name='code' maxlength=100/>
		Name <input type='text' name='name' maxlength=100/>
		<br/>
		Or select an existing one:<br/>
		<table>
		<tr><th></th><th>Code</th><th>Name</th></tr>
		<?php
		$q = SQLQuery::create()->select("CurriculumSubject")->whereValue("CurriculumSubject", "category", $_GET["category"])->groupBy("CurriculumSubject", "code")->group_by("CurriculumSubject","name");
		if (isset($_GET["specialization"]))
			$q->whereValue("CurriculumSubject", "specialization", $_GET["specialization"]);
		else
			$q->whereNull("CurriculumSubject", "specialization");
		$subjects = $q->execute();
		$q = SQLQuery::create()->select("CurriculumSubject")->whereValue("CurriculumSubject", "category", $_GET["category"])->whereValue("CurriculumSubject", "period", $_GET["period"]);
		if (isset($_GET["specialization"]))
			$q->whereValue("CurriculumSubject", "specialization", $_GET["specialization"]);
		else
			$q->whereNull("CurriculumSubject", "specialization");
		$already = $q->execute(); 
		foreach ($subjects as $subject) {
			$found = false;
			foreach ($already as $a) if ($a["code"] == $subject["code"] && $a["name"] == $subject["name"]) { $found = true; break; }
			if ($found) continue;
			echo "<tr>";
			echo "<td>";
			echo "<input type='radio' name='choice' value='".$subject['id']."' subject_code=".json_encode($subject["code"])." subject_name=".json_encode($subject["name"])."/>";
			echo "</td>";
			echo "<td>";
			echo htmlentities($subject["code"]);
			echo "</td>";
			echo "<td>";
			echo htmlentities($subject["name"]);
			echo "</td>";
			echo "</tr>";
		} 
		?>
		</table>
		</form>
		</div>
		<script type='text/javascript'>
		var existing = [<?php
		$first = true;
		foreach ($already as $a) {
			if ($first) $first = false; else echo ",";
			echo "{code:".json_encode($a["code"]).",name:".json_encode($a["name"])."}";
		} 
		?>];
		function validate() {
			var form = document.forms['new_subject'];
			var choice = form.elements['choice'].value;
			if (!choice) { error('Please select an option'); return false; }
			if (choice == 'new') {
				var code = form.elements['code'].value;
				var name = form.elements['name'].value;
				if (code.length == 0) { error('Please enter a code'); return false; }
				if (name.length == 0) { error('Please enter a name'); return false; }
				for (var i = 0; i < existing.length; ++i) {
					if (existing[i].code.toLowerCase().trim() == code.toLowerCase().trim()) { error('A subject already exists with this code'); return false; }
					if (existing[i].name.toLowerCase().trim() == name.toLowerCase().trim()) { error('A subject already exists with this name'); return false; }
				}
			}
			error(null);
			return true;
		}
		function error(msg) {
			var e = document.getElementById('error');
			if (msg == null) e.innerHTML = "";
			else e.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom;padding-right:3px'/>"+msg;
		}
		function get_code_and_name() {
			var form = document.forms['new_subject'];
			var choice = form.elements['choice'].value;
			if (choice == 'new')
				return {code:form.elements['code'].value.trim(),name:form.elements['name'].value.trim()};
			for (var i = 0; i < form.elements['choice'].length; ++i)
				if (form.elements['choice'][i].value == choice)
					return {code:form.elements['choice'][i].getAttribute('subject_code'),name:form.elements['choice'][i].getAttribute('subject_name')};
			return null;
		}
		</script>
		<?php 
	}
	
}
?>