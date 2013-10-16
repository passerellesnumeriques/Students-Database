<?php 
class page_staff extends Page {

	public function get_required_rights() { return array("consult_staff_list"); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/page_header.js");
		$this->onload("new page_header('staff_header');");
		$this->add_javascript("/static/widgets/tree/tree.js");
		$this->onload("init_staff_tree();");
		$this->add_javascript("/static/data_model/editable_cell.js");
		
		$can_edit = PNApplication::$instance->user_management->has_right("manage_staff", true);
		
		?>
		<div id='staff_header' icon='/static/staff/staff_32.png' title="PN Staff">
			<?php if ($can_edit) {?>
			<div class='button' onclick="create_new_department();"><img src='<?php echo theme::$icons_16["add"];?>'/> Create New Department</div>
			<?php }?>
		</div>
		<div style='width:100%;height:100%;overflow:auto'>
			<div id='staff_tree'>
			</div>
		</div>
		<script type='text/javascript'>
		var staff_dept = [<?php 
		$list = SQLQuery::create()->select("PNDepartment")->execute();
		$first = true;
		foreach ($list as $d) {
			if ($first) $first = false; else echo ",";
			echo "{id:".$d["id"].",name:".json_encode($d["name"])."}";
		}
		?>];
		var staff = [<?php
		$staff = SQLQuery::create()->select("Staff")->join("Staff","People",array("people"=>"id"))->execute();
		$positions = SQLQuery::create()->select("Staff")->join("Staff","StaffPosition",array("people"=>"people"))->execute();
		foreach ($staff as &$s) {
			$s["positions"] = array();
			foreach ($positions as $p) {
				if ($p["people"] == $s["people"])
					array_push($s["positions"], $p);
			}
		}
		$first = true;
		foreach ($staff as $s) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "people_id:".$s["people"];
			echo ",first_name:".json_encode($s["first_name"]);
			echo ",last_name:".json_encode($s["last_name"]);
			echo ",department_id:".$s["department"];
			echo ",positions:[";
			$first_pos = true;
			foreach ($s["positions"] as $p) {
				if ($first_pos) $first_pos = false; else echo ",";
				echo "{";
				echo "department_id:".$p["department"];
				echo ",position:".json_encode($p["position"]);
				echo ",start:".json_encode($p["start"]);
				echo ",end:".json_encode($p["end"]);
				echo "}";
			}
			echo "]";
			echo "}";
		}
		?>];
		var staff_tree = null;
		function init_staff_tree() {
			staff_tree = new tree('staff_tree');
			staff_tree.setShowColumn(true);
			staff_tree.addColumn(new TreeColumn("Last Name"));
			staff_tree.addColumn(new TreeColumn("First Name"));
			staff_tree.addColumn(new TreeColumn("Position"));
			staff_tree.addColumn(new TreeColumn("Started"));
			for (var i = 0; i < staff_dept.length; ++i) {
				var dept = department_item(staff_dept[i]);
				staff_tree.addItem(dept);
				for (var j = 0; j < staff.length; ++j)
					if (staff[j].department_id == dept.id)
						dept.addItem(staff_item(staff[j]));
			}
			var not_assigned = new TreeItem([new TreeCell("Staff not assigned to a department")]);
			staff_tree.addItem(not_assigned);
			for (var j = 0; j < staff.length; ++j)
				if (staff[j].department_id == null)
					not_assigned.addItem(staff_item(staff[j]));
		}

		function department_item(dept) {
			var html = document.createElement("SPAN");
			new editable_cell(html, "PNDepartment", "name", dept.id, "field_text", {max_length:100}, dept.name);
			var icon = document.createElement("IMG");
			icon.src = theme.icons_16.remove;
			icon.style.paddingLeft = '2px';
			icon.style.cursor = 'pointer';
			icon.style.verticalAlign = 'bottom';
			icon.onclick = function() {
				confirm_dialog("Are you sure you want to remove the department "+dept.name+" ?", function(yes){
					if (!yes) return;
					// TODO remove
				});
			};
			html.appendChild(icon);
			return new TreeItem([new TreeCell(html)]);
		}

		function create_new_department() {
			input_dialog(theme.icons_16.add,"New Department","Name of the new department","",100,function(name){
				if (name.length == 0)
					return "Please enter a name";
				for (var i = 0; i < staff_dept.length; ++i)
					if (staff_dept[i].name.toLowerCase() == name.toLowerCase())
						return "This department already exists";
				return null;
			},function(name){
				service.json("data_model","save_entity",{table:"PNDepartment",field_name:name},function(res){
					if (res && res.key) {
						var dept = {id:res.key,name:name};
						staff_dept.push(dept);
						staff_tree.addItem(department_item(dept));
					}
				});
			});
		}
		</script>
		<?php 
	}
	
}
?>