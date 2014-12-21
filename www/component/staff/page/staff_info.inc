<?php 
function staff_info(&$page, $people_id, $container_id) {
	$departments = SQLQuery::create()->select("PNDepartment")->execute();
	if ($people_id <> -1)
		$positions = SQLQuery::create()->select("StaffPosition")->where("people",$people_id)->orderBy("StaffPosition", "start", false)->execute();
	else
		$positions = null;
	
	$table = DataModel::get()->getTable("StaffPosition");
	
	$can_edit = $people_id == -1 || PNApplication::$instance->user_management->has_right("manage_staff");
	
	require_once("component/data_model/page/utils.inc");
	
	$fct_name = $page->generateID();
	$display = DataModel::get()->getTableDataDisplay("StaffPosition");
	?>
	<script type='text/javascript'>
	function <?php echo $fct_name;?>() {
		var t=this;
		this.departments = [<?php
		$first = true;
		foreach ($departments as $d) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$d["id"];
			echo ",name:".json_encode($d["name"]);
			echo "}";
		}
		?>];
		this.positions = [<?php 
		if ($positions <> null) {
			$first = true;
			foreach ($positions as $p) {
				if ($first) $first = false; else echo ",";
				echo "{";
				echo "id:".$p["id"];
				echo ",position:".json_encode($p["position"]);
				echo ",department:".json_encode($p["department"]);
				echo ",start:".($p["start"] <> null ? json_encode($p["start"]) : "null");
				echo ",end:".($p["end"] <> null ? json_encode($p["end"]) : "null");
				echo "}";
			}
		}
		?>];
		this.people_id = <?php echo $people_id;?>;

		this.setPositions = function(positions) {
			while (t.table.childNodes.length > 0) t.table.removeChild(t.table.childNodes[0]);
			t.positions = positions;
			for (var i = 0; i < positions.length; ++i)
				t.addPosition(positions[i]);
		};
		
		this._create = function() {
			var container = document.getElementById('<?php echo $container_id;?>');
			var table = document.createElement("TABLE"); container.appendChild(table);
			var head = document.createElement("THEAD"); table.appendChild(head);
			var tr = document.createElement("TR"); head.appendChild(tr);
			var th;
			tr.appendChild(th = document.createElement("TH")); th.innerHTML = "Position";
			tr.appendChild(th = document.createElement("TH")); th.innerHTML = "Department";
			tr.appendChild(th = document.createElement("TH")); th.innerHTML = "Start Date";
			tr.appendChild(th = document.createElement("TH")); th.innerHTML = "End Date";
			tr.appendChild(th = document.createElement("TH"));
			table.appendChild(t.table = document.createElement("TBODY"));
			<?php if ($can_edit) { ?>
			var foot = document.createElement("TFOOT"); table.appendChild(foot);
			foot.appendChild(tr = document.createElement("TR"));
			tr.appendChild(th = document.createElement("TD"));
			th.colSpan = 4;
			var a = document.createElement("A");
			a.href = '#';
			a.style.fontStyle = 'italic';
			a.style.color = "#808080";
			a.innerHTML = "Add new position";
			a.onclick = function() { t.newPosition(); };
			th.appendChild(a);
			if (this.people_id > 0) {
				foot.appendChild(tr = document.createElement("TR"));
				tr.appendChild(th = document.createElement("TD"));
				th.colSpan = 4;
				var button = document.createElement("BUTTON");
				button.className = "action red";
				button.innerHTML = "Never been a PN Staff";
				th.appendChild(button);
				button.onclick = function() {
					popupFrame(null,"Remove Staff","/dynamic/people/page/remove_people_type?people="+t.people_id+"&type=staff&ontyperemoved=type_removed&onpeopleremoved=people_removed",null,null,null,function(frame,pop){
						frame.people_removed = function() {
							require("popup_window.js",function() {
								get_popup_window_from_frame(window).close();
							});
						};
						frame.type_removed = function() { location.reload(); };
					});
				};
			}
			<?php } ?>
			t.setPositions(t.positions);
		};

		this.newPosition = function() {
			var p = {id:-1,position:"",department:null,start:null,end:null};
			this.positions.push(p);
			this.addPosition(p);
		};

		this.addPosition = function(position) {
			var tr = document.createElement("TR");
			t.table.appendChild(tr);
			var td_position = document.createElement("TD");
			tr.appendChild(td_position);
			var check_valid = function(){};
			if (position.id == -1 && t.people_id != -1)
				check_valid = function() {
					if (position.position.trim() == "") return;
					if (position.department == null) return;
					if (position.start == null) return;
					var data = {
						table:"StaffPosition",
						field_position:position.position,
						field_department:position.department,
						field_start:position.start,
						field_end:position.end,
						field_people:t.people_id
					};
					if (position.id != -1) {
						data.key = position.id;
						data.lock = -1;
					};
					var locker = lock_screen(null, "Saving new staff position...");
					service.json("data_model","save_entity",data,function(res){
						unlock_screen(locker);
						if (res && res.key && position.id == -1) position.id = res.key;
					});
				};
			var fake_key = generateID();
			if (position.id == -1) {
				var field;
				<?php datamodel_datadisplay_new($page, $display->getDataDisplayByName("Position", null, null), null, "field", "fake_key");?>
				td_position.appendChild(field.getHTMLElement());
				field.onchange.addListener(function(field){
					position.position = field.getCurrentData();
					check_valid();
				});
			} else {
				window.top.datamodel.create_cell(window, "StaffPosition", null, "position", position.id, position.position, 'field_text', {max_length:100}, <?php echo $can_edit ? "true" : "false";?>, td_position, function(p){position.position=p;}, function(f){f.fillWidth();});
			}
			var td_department = document.createElement("TD");
			tr.appendChild(td_department);
			if (position.id == -1) {
				var field;
				<?php datamodel_datadisplay_new($page, $display->getDataDisplayByName("Department", null, null), null, "field", "fake_key");?>
				td_department.appendChild(field.getHTMLElement());
				field.onchange.addListener(function(field){
					var s = field.getCurrentData();
					position.department = s == null || s.length == 0 ? null : parseInt(s);
					check_valid();
				});
			} else {
				var values = [];
				for (var i = 0; i < t.departments.length; ++i)
					values.push([t.departments[i].id,t.departments[i].name]);
				window.top.datamodel.create_cell(window, "StaffPosition", null, "department", position.id, position.department, 'field_enum', {possible_values:values,can_be_null:true}, <?php echo $can_edit ? "true" : "false";?>, td_department, function(d){position.department=d;}, function(f){f.fillWidth();});
			}
			var td_start = document.createElement("TD");
			tr.appendChild(td_start);
			if (position.id == -1) {
				var field;
				<?php datamodel_datadisplay_new($page, $display->getDataDisplayByName("Start", null, null), null, "field", "fake_key");?>
				td_start.appendChild(field.getHTMLElement());
				field.onchange.addListener(function(field){
					position.start = field.getCurrentData();
					check_valid();
				});
			} else {
				window.top.datamodel.create_cell(window, "StaffPosition", null, "start", position.id, position.start, 'field_date', {}, <?php echo $can_edit ? "true" : "false";?>, td_start, function(d){position.start=d;});
			}
			var td_end = document.createElement("TD");
			tr.appendChild(td_end);
			if (position.id == -1) {
				var field;
				<?php datamodel_datadisplay_new($page, $display->getDataDisplayByName("End", null, null), null, "field", "fake_key");?>
				td_end.appendChild(field.getHTMLElement());
				field.onchange.addListener(function(field){
					position.end = field.getCurrentData();
					check_valid();
				});
			} else {
				window.top.datamodel.create_cell(window, "StaffPosition", null, "end", position.id, position.end, 'field_date', {can_be_null:true}, <?php echo $can_edit ? "true" : "false";?>, td_end, function(d){position.end=d;});
			}
			<?php if ($can_edit) { ?>
			var td_remove = document.createElement("TD");
			tr.appendChild(td_remove);
			var remove = document.createElement("BUTTON");
			remove.className = "flat icon";
			remove.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
			remove.onclick = function() {
				if (t.positions.length == 1) { alert("You must keep at least one position"); return; }
				confirmDialog("Remove this position ?",function(yes) {
					if (!yes) return;
					t.positions.remove(position);
					tr.parentNode.removeChild(tr);
					if (position.id > 0)
						service.json("data_model", "remove_row", {table:"StaffPosition",row_key:position.id}, function(res) {
						});
						
				});
			};
			td_remove.appendChild(remove);
			<?php } ?>
		};

		this._create();
	}
	</script>
	<?php
	return $fct_name;
}
?>