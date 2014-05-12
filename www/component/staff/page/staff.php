<?php 
class page_staff extends Page {

	public function getRequiredRights() { return array("consult_staff_list"); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/header_bar.js");
		$this->onload("new header_bar('staff_header','toolbar_big');");
		$this->add_javascript("/static/widgets/vertical_layout.js");
		$this->onload("new vertical_layout('staff_page');");
		$this->add_javascript("/static/widgets/tree/tree.js");
		$this->onload("init_staff_tree();");
		$this->add_javascript("/static/data_model/editable_cell.js");

		$departments = SQLQuery::create()->select("PNDepartment")->orderBy("PNDepartment","name",true)->execute();
		
		$can_edit = PNApplication::$instance->user_management->has_right("manage_staff", true);
		
		?>
		<div id='staff_page' style='width:100%;height:100%;'>
			<div id='staff_header' icon='/static/staff/staff_32.png' title="PN Staff" layout="fixed">
				<?php if ($can_edit) {?>
				<button class='button_verysoft' onclick="create_new_department();"><img src='/static/application/icon.php?main=/static/staff/department.png&small=<?php echo theme::$icons_10["add"];?>&where=right_bottom'/> Create New Department</button>
				<button class='button_verysoft' onclick="create_new_staff();" <?php if (count($departments) == 0) echo " disabled='disabled'";?>><img src='/static/application/icon.php?main=/static/staff/staff_16.png&small=<?php echo theme::$icons_10["add"];?>&where=right_bottom'/> Create New Staff</button>
				<?php }?>
			</div>
			<div style='width:100%;overflow:auto' layout="fill">
				<div id='staff_tree'>
				</div>
			</div>
		</div>
		<script type='text/javascript'>
		var staff_dept = [<?php 
		$first = true;
		foreach ($departments as $d) {
			if ($first) $first = false; else echo ",";
			echo "{id:".$d["id"].",name:".json_encode($d["name"])."}";
		}
		?>];
		function staff_obj(o) {
			for (var name in o) this[name] = o[name];
			this.get_last_position = function() {
				if (this.positions.length == 0) return null;
				var last = this.positions[0];
				if (last.start == null) return last;
				var last_date = parseSQLDate(last.start);
				for (var i = 1; i < this.positions.length; ++i) {
					if (this.positions[i].start == null) return this.positions[i];
					var date = parseSQLDate(this.positions[i].start);
					if (date.getTime() > last_date.getTime()) {
						last = this.positions[i];
						last_date = date;
					}
				}
				return last;
			};
		}
		var staff = [<?php
		$staff = array();
		$positions = SQLQuery::create()->select("StaffPosition")->execute();
		foreach ($positions as $p) {
			if (!isset($staff[$p["people"]])) {
				$people = PNApplication::$instance->people->getPeople($p["people"]);
				$people["positions"] = array();
				$staff[$p["people"]] = $people;
			}
			array_push($staff[$p["people"]]["positions"], $p);
		}
		$first = true;
		foreach ($staff as $people_id=>$s) {
			if ($first) $first = false; else echo ",";
			echo "new staff_obj({";
			echo "people_id:".$people_id;
			echo ",first_name:".json_encode($s["first_name"]);
			echo ",last_name:".json_encode($s["last_name"]);
			echo ",positions:[";
			$first_pos = true;
			foreach ($s["positions"] as $p) {
				if ($first_pos) $first_pos = false; else echo ",";
				echo "{";
				echo "department:".json_encode($p["department"]);
				echo ",position:".json_encode($p["position"]);
				echo ",start:".json_encode($p["start"]);
				echo ",end:".json_encode($p["end"]);
				echo "}";
			}
			echo "]";
			echo "})";
		}
		?>];
		var staff_tree = null;
		var not_assigned;
		function init_staff_tree() {
			staff_tree = new tree('staff_tree');
			staff_tree.setShowColumn(true);
			staff_tree.addColumn(new TreeColumn(""));
			staff_tree.addColumn(new TreeColumn("Last Name"));
			staff_tree.addColumn(new TreeColumn("First Name"));
			staff_tree.addColumn(new TreeColumn("Position"));
			staff_tree.addColumn(new TreeColumn("Started"));
			for (var i = 0; i < staff_dept.length; ++i) {
				var dept = department_item(staff_dept[i]);
				staff_tree.addItem(dept);
				for (var j = 0; j < staff.length; ++j)
					if (staff[j].get_last_position() != null && staff[j].get_last_position().department == staff_dept[i].id)
						dept.addItem(staff_item(staff[j]));
			}
			not_assigned = new TreeItem([new TreeCell("Staff not assigned to a department")]);
			staff_tree.addItem(not_assigned);
			for (var j = 0; j < staff.length; ++j)
				if (staff[j].get_last_position() == null || staff[j].get_last_position().department == null)
					not_assigned.addItem(staff_item(staff[j]));
		}

		function department_item(dept) {
			var html = document.createElement("SPAN");
			var icon = document.createElement("IMG");
			icon.src = "/static/staff/department.png";
			icon.style.paddingRight = '2px';
			icon.style.verticalAlign = 'bottom';
			html.appendChild(icon);
			var name = document.createElement("SPAN"); html.appendChild(name);
			new editable_cell(name, "PNDepartment", "name", dept.id, "field_text", {max_length:100}, dept.name);
			icon = document.createElement("IMG");
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
			return new TreeItem([new TreeCell(html)], true);
		}

		function staff_item(staff) {
			var cells = [];
			var icon = document.createElement("IMG");
			icon.src = "/static/staff/staff_16.png";
			icon.style.paddingRight = '2px';
			icon.style.verticalAlign = 'bottom';
			cells.push(new TreeCell(icon));
			var html = document.createElement("SPAN");
			html.appendChild(document.createTextNode(staff.last_name));
			html.style.cursor = 'pointer';
			html.onclick = function() { location.href = '/dynamic/people/page/profile?people='+staff.people_id; }
			cells.push(new TreeCell(html));
			html = document.createElement("SPAN");
			html.appendChild(document.createTextNode(staff.first_name));
			html.style.cursor = 'pointer';
			html.onclick = function() { location.href = '/dynamic/people/page/profile?people='+staff.people_id; }
			cells.push(new TreeCell(html));
			var p = staff.get_last_position();
			html = document.createElement("SPAN");
			html.appendChild(document.createTextNode(p == null ? "" : p.position));
			cells.push(new TreeCell(html));
			html = document.createElement("SPAN");
			html.appendChild(document.createTextNode(p == null ? "" : p.start));
			cells.push(new TreeCell(html));
			return new TreeItem(cells);
		}

		function create_new_department() {
			input_dialog("/static/application/icon.php?main=/static/staff/department.png&small="+theme.icons_10.add+"&where=right_bottom","New Department","Name of the new department","",100,function(name){
				if (name.length == 0)
					return "Please enter a name";
				for (var i = 0; i < staff_dept.length; ++i)
					if (staff_dept[i].name.toLowerCase() == name.toLowerCase())
						return "This department already exists";
				return null;
			},function(name){
				if (!name) return;
				service.json("data_model","save_entity",{table:"PNDepartment",field_name:name},function(res){
					if (res && res.key) {
						var dept = {id:res.key,name:name};
						staff_dept.push(dept);
						staff_tree.removeItem(not_assigned);
						staff_tree.addItem(department_item(dept));
						staff_tree.addItem(not_assigned);
					}
				});
			});
		}

		function create_new_staff() {
			window.top.require("popup_window.js",function() {
				var p = new window.top.popup_window('New Staff', theme.build_icon("/static/staff/staff_16.png",theme.icons_10.add), "");
				var frame = p.setContentFrame("/dynamic/people/page/popup_create_people?types=staff&ondone=reload_staff");
				frame.reload_staff = function() {
					location.reload();
				};
				p.show();
			});
		}
		</script>
		<?php 
	}
	
}
?>