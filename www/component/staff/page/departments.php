<?php 
class page_departments extends Page {
	
	public function getRequiredRights() { return array("manage_staff"); }
	
	public function execute() {
		$departments = SQLQuery::create()->select("PNDepartment")->orderBy("PNDepartment","name",true)->execute();
		$default_roles = SQLQuery::create()->select("PNDepartmentDefaultRoles")->execute();
		$roles = PNApplication::$instance->user_management->getRoles();
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_text.js");
		$this->requireJavascript("field_list_of_fixed_values.js");
		$this->requireJavascript("editable_cell.js");
		$this->requireJavascript("editable_field.js");
		?>
		<style type='text/css'>
		table, th, td {
			border: 1px solid black;
		}
		table {
			border-collapse: collapse;
			border-spacing: 0px;
		}
		th {
			background-color: #D0D0FF;
		}
		.row_footer {
			background-color: #D0D0D0;
		}
		th, td {
			padding: 3px;
		}
		</style>
		<div style='background-color:white;padding:10px;'>
			<table>
				<tr id='row_header'><th>Department Name</th><th>Default Roles</th></tr>
				<tr id='row_footer'><td colspan=2 align=center>
					<button class='action green' onclick='newDepartment();'><img src='<?php echo theme::$icons_16["add_white"];?>'/> New Department</button>
				</td></tr>
			</table>
		</div>
		<script type='text/javascript'>
		var departments = <?php echo json_encode($departments);?>;
		var default_roles = <?php echo json_encode($default_roles);?>;
		var roles = <?php echo json_encode($roles);?>;

		function addDepartment(department) {
			var tr = document.createElement("TR");
			var td = document.createElement("TD");
			new editable_cell(td, "PNDepartment", "name", department.id, "field_text", {max_length:100,can_ben_null:false}, department.name, 
				function(new_name) {
					new_name = new_name.trim();
					for (var i = 0; i < departments.length; ++i)
						if (departments[i].id != department.id && departments[i].name.isSame(new_name)) {
							alert("A department already exists with this name");
							return department.name;
						}
					department.name = new_name;
					return new_name;
				}
			);
			tr.appendChild(td);
			var remove = document.createElement("BUTTON");
			remove.className = "flat small_icon";
			remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
			remove.marginLeft = "5px";
			remove.title = "Remove this department";
			remove.onclick = function() {
				require("popup_window.js");
				service.customOutput("data_model", "get_remove_confirmation_content", {table:"PNDepartment",row_key:department.id}, function(html) {
					require("popup_window.js", function() {
						var div = document.createElement("DIV");
						div.innerHTML = html;
						var p = new popup_window("Confirmation to remove department", theme.icons_16.question, div);
						p.addYesNoButtons(function() {
							p.freeze();
							service.json("data_model", "remove_row", {table:"PNDepartment",row_key:department.id}, function(res) {
								if (!res) { p.unfreeze(); return; }
								departments.remove(department);
								p.close();
								tr.parentNode.removeChild(tr);
								layout.changed(document.body);
							});
						});
						p.show(); 
					});
				});
			};
			td.appendChild(remove);
			
			td = document.createElement("TD");
			var list = [];
			for (var i = 0; i < default_roles.length; ++i)
				if (default_roles[i].department == department.id) list.push(default_roles[i].user_role);
			var possible_values = [];
			for (var i = 0; i < roles.length; ++i)
				possible_values.push([roles[i].id,roles[i].name]);
			var field_roles = new field_list_of_fixed_values(list,true,{possible_values:possible_values});
			td.appendChild(field_roles.getHTMLElement());
			field_roles.onchange.addListener(function() {
				var locker = lockScreen(null, "Saving roles...");
				service.json("staff", "save_department_roles", {department:department.id, roles:field_roles.getCurrentData()}, function(res) {
					unlockScreen(locker);
				});
			});
			tr.appendChild(td);

			var t = document.getElementById('row_footer');
			t.parentNode.insertBefore(tr, t);
			layout.changed(t.parentNode);
		}

		for (var i = 0; i < departments.length; ++i)
			addDepartment(departments[i]);

		function newDepartment() {
			inputDialog(theme.build_icon("/static/staff/department.png",theme.icons_10.add),"New Department","Name of the new department","",100,function(name){
				if (name.length == 0)
					return "Please enter a name";
				for (var i = 0; i < departments.length; ++i)
					if (departments[i].name.isSame(name))
						return "This department already exists";
				return null;
			},function(name){
				if (!name) return;
				service.json("data_model","save_entity",{table:"PNDepartment",field_name:name},function(res){
					if (res && res.key) {
						var dept = {id:res.key,name:name};
						departments.push(dept);
						addDepartment(dept);
					}
				});
			});
		}
		</script>
		<?php 
	}
	
}
?>