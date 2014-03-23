<?php 
class page_departments extends Page {
	
	public function get_required_rights() { return array("manage_staff"); }
	
	public function execute() {
		$departments = SQLQuery::create()->select("PNDepartment")->orderBy("PNDepartment","name",true)->execute();
		echo "<div id='container'>";
		foreach ($departments as $d) {
			echo "<div id='dept_".$d["id"]."'>";
			echo htmlentities($d["name"]);
			echo " <img class='button_verysoft' src='".theme::$icons_10["remove"]."' style='vertical-align:middle' onclick='remove_dept(".$d["id"].")'/>";
			echo "</div>";
		}
		echo "</div>";
		echo "<div style='text-align:center'><button onclick='new_dept();'><img src='".theme::$icons_16["add"]."'/> New Department</button></div>";
		?>
<script type='text/javascript'>
var depts = [<?php
$first = true;
foreach ($departments as $d) {
	if ($first) $first = false; else echo ",";
	echo "{id:".$d["id"].",name:".json_encode($d["name"])."}";
} 
?>];
function new_dept() {
	input_dialog(theme.build_icon("/static/staff/department.png",theme.icons_10.add),"New Department","Name of the new department","",100,function(name){
		if (name.length == 0)
			return "Please enter a name";
		for (var i = 0; i < depts.length; ++i)
			if (depts[i].name.toLowerCase() == name.toLowerCase())
				return "This department already exists";
		return null;
	},function(name){
		if (!name) return;
		service.json("data_model","save_entity",{table:"PNDepartment",field_name:name},function(res){
			if (res && res.key) {
				var dept = {id:res.key,name:name};
				depts.push(dept);
				var div = document.createElement("DIV");
				div.id = "dept_"+dept.id;
				div.appendChild(document.createTextNode(dept.name+" "));
				var img = document.createElement("IMG");
				img.className = "button_verysoft";
				img.style.verticalAlign = "middle";
				img.src = theme.icons_10.remove;
				img.dept_id = dept.id;
				img.onclick = function() { remove_dept(this.dept_id); };
				div.appendChild(img);
				document.getElementById('container').appendChild(div);
				layout.invalidate(document.body);
			}
		});
	});
}
function remove_dept(id) {
	require("popup_window.js");
	service.customOutput("data_model", "get_remove_confirmation_content", {table:"PNDepartment",row_key:id}, function(html) {
		require("popup_window.js", function() {
			var div = document.createElement("DIV");
			div.innerHTML = html;
			var p = new popup_window("Confirmation to remove department", theme.icons_16.question, div);
			p.addYesNoButtons(function() {
				p.freeze();
				service.json("data_model", "remove_row", {table:"PNDepartment",row_key:id}, function(res) {
					if (!res) { p.unfreeze(); return; }
					p.close();
					document.getElementById('container').removeChild(document.getElementById("dept_"+id));
					layout.invalidate(document.body);
				});
			});
			p.show(); 
		});
	});
}
</script>
		<?php 
	}
	
}
?>