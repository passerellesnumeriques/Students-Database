<?php 
function staff_info(&$page, $people_id, $container_id) {
	$departments = SQLQuery::create()->select("PNDepartment")->execute();
	if ($people_id <> -1)
		$positions = SQLQuery::create()->select("StaffPosition")->where("people",$people_id)->order_by("StaffPosition", "start", false)->execute();
	else
		$positions = null;
	
	$table = DataModel::get()->getTable("StaffPosition");
	
	require_once("component/data_model/page/utils.inc");
	
	$fct_name = $page->generate_id();
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
				echo ",department:".$p["department"];
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
			table.appendChild(t.table = document.createElement("TBODY"));
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
			t.setPositions(t.positions);
		};

		this.newPosition = function() {
			if (this.people_id == -1) {
				var p = {id:-1,position:"",department:null,start:null,end:null};
				this.positions.push(p);
				this.addPosition(p);
			} else {
				service.json("data_model","save_entity",{table:"StaffPosition",field_position:"Enter Job Position"},function(res){
					if (!res) return;
					var p = {id:res.id,position:"Enter Job Position",department:null,start:null,end:null};
					t.positions.push(p);
					t.addPosition(p);
				});
			}
		};

		this.addPosition = function(position) {
			var tr = document.createElement("TR");
			t.table.appendChild(tr);
			var td_position = document.createElement("TD");
			tr.appendChild(td_position);
			var fake_key = generate_id();
			if (position.id == -1) {
				var field;
				<?php datamodel_datadisplay_new($page, $table->getDataDisplay("Staff", "Position"), null, "field", "fake_key");?>
				td_position.appendChild(field.getHTMLElement());
				field.onchange.add_listener(function(field){
					position.position = field.getCurrentData();
				});
			} else {
				require("editable_cell.js",function(){
					new editable_cell(td_position, "StaffPosition", "position", position.id, 'field_text', {max_length:100}, position.position);
				});
			}
			var td_department = document.createElement("TD");
			tr.appendChild(td_department);
			if (position.id == -1) {
				var field;
				<?php datamodel_datadisplay_new($page, $table->getDataDisplay("Staff", "Department"), null, "field", "fake_key");?>
				td_department.appendChild(field.getHTMLElement());
				field.onchange.add_listener(function(field){
					var s = field.getCurrentData();
					position.department = s == null || s.length == 0 ? null : parseInt(s);
				});
			} else {
				require("editable_cell.js",function(){
					var values = [];
					for (var i = 0; i < t.departments.length; ++i)
						values.push([t.departments[i].id,t.departments[i].name]);
					new editable_cell(td_department, "StaffPosition", "department", position.id, 'field_enum', {possible_values:values,can_be_empty:true}, position.department);
				});
			}
			var td_start = document.createElement("TD");
			tr.appendChild(td_start);
			if (position.id == -1) {
				var field;
				<?php datamodel_datadisplay_new($page, $table->getDataDisplay("Staff", "Position Start"), null, "field", "fake_key");?>
				td_start.appendChild(field.getHTMLElement());
				field.onchange.add_listener(function(field){
					position.start = field.getCurrentData();
				});
			} else {
				require("editable_cell.js",function(){
					new editable_cell(td_start, "StaffPosition", "start", position.id, 'field_date', {}, position.start);
				});
			}
			var td_end = document.createElement("TD");
			tr.appendChild(td_end);
			if (position.id == -1) {
				var field;
				<?php datamodel_datadisplay_new($page, $table->getDataDisplay("Staff", "Position End"), null, "field", "fake_key");?>
				td_end.appendChild(field.getHTMLElement());
				field.onchange.add_listener(function(field){
					position.end = field.getCurrentData();
				});
			} else {
				require("editable_cell.js",function(){
					new editable_cell(td_end, "StaffPosition", "position", position.id, 'field_date', {}, position.end);
				});
			}
		};

		this._create();
	}
	</script>
	<?php
	return $fct_name;
}
?>