<?php 
class page_assign_specializations extends Page {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function execute() {
		$batch_id = $_GET["batch"];
		$batch_name = SQLQuery::create()->select("StudentBatch")->field("name")->whereValue("StudentBatch", "id", $batch_id)->executeSingleValue();
		require_once("component/people/PeopleJSON.inc");
		$q = SQLQuery::create()
			->select("Student")
			->whereValue("Student", "batch", $batch_id)
			->field("Student", "specialization", "specialization")
			;
		PNApplication::$instance->people->joinPeople($q, "Student", "people");
		PeopleJSON::PeopleSQL($q);
		$students = $q->execute();
		$specializations = SQLQuery::create()
			->select("AcademicPeriodSpecialization")
			->join("AcademicPeriodSpecialization", "AcademicPeriod", array("period"=>"id"))
			->whereValue("AcademicPeriod", "batch", $batch_id)
			->field("AcademicPeriodSpecialization", "specialization", "BATCH_SPE_ID")
			->join("AcademicPeriodSpecialization", "Specialization", array("specialization"=>"id"))
			->field("Specialization", "name", "BATCH_SPE_NAME")
			->groupBy("Specialization", "id")
			->execute();
		
		$this->require_javascript("assign_peoples.js");
		$this->require_javascript("vertical_layout.js");
		$this->require_javascript("header_bar.js");
		?>
		<div id='page_container' style='width:100%;height:100%'>
			<div id='page_header' title="Assignment of specialization for students of batch <?php echo htmlentities($batch_name);?>">
				<div class='button' onclick='save();'>
					<img src='<?php echo theme::$icons_16["save"];?>'/>
					Save
				</div>
			</div>
			<div id='assign_container' layout='fill'>
			</div>
		</div>
		<script type='text/javascript'>
		new header_bar('page_header','small');
		new vertical_layout('page_container');
		var assign = new assign_peoples('assign_container');
		<?php foreach ($specializations as $spe) echo "assign.addPossibleAssignment(".$spe["BATCH_SPE_ID"].",".json_encode($spe["BATCH_SPE_NAME"]).");\n";?>
		<?php foreach ($students as $s) echo "assign.addPeople(".PeopleJSON::People($q, $s).",".json_encode($s["specialization"]).");\n";?>
		function save() {
			var lock = lock_screen(null,"");
			var peoples = assign.getPeoples();
			var next = function(index) {
				if (index == peoples.length) {
					unlock_screen(lock);
					return;
				}
				var original = assign.getOriginalAssignment(peoples[index].id);
				var current = assign.getNewAssignment(peoples[index].id);
				if (original == current) {
					next(index+1);
					return;
				}
				if (current == null)
					set_lock_screen_content(lock, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Unassigning specialization from "+peoples[index].first_name+" "+peoples[index].last_name);
				else
					set_lock_screen_content(lock, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Assigning specialization "+assign.getAssignmentName(current)+" to "+peoples[index].first_name+" "+peoples[index].last_name);
				service.json("students", "assign_specialization", {student:peoples[index].id,specialization:current}, function(res) {
					next(index+1);
				});
			};
			next(0);
		}
		</script>
		<?php 
		/*
		?>
		<table class='all_borders' style='white-space:nowrap'>
		<tr><th colspan=3>Assignment of specialization for students of batch <?php echo htmlentities($batch_name);?></th></tr>
		<tr>
			<th>Non-assigned students</th>
			<th></th>
			<th>Students assigned to <?php echo $specializations[0]["BATCH_SPE_NAME"];?></th>
		</tr>
		<tr>
			<td rowspan=<?php echo (count($specializations)-1)*2+1;?> id='non_assigned' valign=top></td>
			<td valign='middle'>
				<div class='button' onclick="assign('<?php echo $specializations[0]["BATCH_SPE_ID"];?>');" title='Assign'><img src='<?php echo theme::$icons_16["right"];?>'/></div>
				<br/>
				<div class='button' onclick="unassign('<?php echo $specializations[0]["BATCH_SPE_ID"];?>');" title='Unassign'><img src='<?php echo theme::$icons_16["left"];?>'/></div>
			</td>
			<td id='assigned_<?php echo $specializations[0]["BATCH_SPE_ID"];?>' valign=top></td>
		</tr>
		<?php
		for ($i = 1; $i < count($specializations); $i++) {
			echo "<tr><th></th><th>Students assigned to ".htmlentities($specializations[$i]["BATCH_SPE_NAME"])."</th></tr>";
			echo "<tr>";
			echo "<td valign='middle'>";
				echo "<div class='button' onclick='assign(\"".$specializations[$i]["BATCH_SPE_ID"]."\");' title='Assign'><img src='".theme::$icons_16["right"]."'/></div>";
				echo "<br/>";
				echo "<div class='button' onclick='unassign(\"".$specializations[$i]["BATCH_SPE_ID"]."\");' title='Assign'><img src='".theme::$icons_16["left"]."'/></div>";
			echo "</td>";
			echo "<td id='assigned_".$specializations[$i]["BATCH_SPE_ID"]."' valign=top></td>";
			echo "</tr>";
		} 
		?>
		</table>
		<script type='text/javascript'>
		var students = [<?php
		$first = true;
		foreach ($students as $student) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "people:".$student["people"];
			echo ",first_name:".json_encode($student["first_name"]);
			echo ",last_name:".json_encode($student["last_name"]);
			echo ",original_spe:".json_encode($student["specialization"]);
			echo ",assigned_spe:".json_encode($student["specialization"]);
			echo ",can_change:";
			if ($student["specialization"] == null)
				echo "true";
			else {
				$classes = SQLQuery::create()
					->select("StudentClass")
					->whereValue("StudentClass", "people", $student["people"])
					->join("StudentClass", "AcademicClass", array("class"=>"id"))
					->whereValue("AcademicClass", "specialization", $student["specialization"])
					->execute();
				if (count($classes) == 0)
					echo "true";
				else
					echo "false";
			}
			echo "}";
		} 
		?>];
		var specializations = [<?php
		$first = true;
		foreach ($specializations as $spe) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$spe["BATCH_SPE_ID"];
			echo ",name:".json_encode($spe["BATCH_SPE_NAME"]);
			echo "}";
		} 
		?>];
		for (var i = 0; i < students.length; ++i) {
			var s = students[i];
			s.div = document.createElement("DIV");
			s.div.appendChild(s.cb = document.createElement("INPUT"));
			s.cb.type = 'checkbox';
			if (!s.can_change)
				s.cb.disabled = 'disabled';
			s.div.appendChild(document.createTextNode(s.first_name+" "+s.last_name));
			var e;
			if (s.original_spe == null)
				e = document.getElementById("non_assigned");
			else
				e = document.getElementById("assigned_"+s.original_spe);
			e.appendChild(s.div);
		}
		function assign(spe_id) {
			for (var i = 0; i < students.length; ++i) {
				var s = students[i];
				if (s.assigned_spe != null) continue;
				if (!s.cb.checked) continue;
				s.cb.checked = '';
				s.assigned_spe = spe_id;
				s.div.parentNode.removeChild(s.div);
				document.getElementById("assigned_"+spe_id).appendChild(s.div);
			}
			if (window.parent.get_popup_window_from_frame) {
				var p = window.parent.get_popup_window_from_frame(window);
				if (p) p.resize();
			}
		}
		function unassign(spe_id) {
			for (var i = 0; i < students.length; ++i) {
				var s = students[i];
				if (s.assigned_spe != spe_id) continue;
				if (!s.cb.checked) continue;
				s.cb.checked = '';
				s.assigned_spe = null;
				s.div.parentNode.removeChild(s.div);
				document.getElementById("non_assigned").appendChild(s.div);
			}
			if (window.parent.get_popup_window_from_frame) {
				var p = window.parent.get_popup_window_from_frame(window);
				if (p) p.resize();
			}
		}
		function save(onprogress, ondone) {
			var next = function(i) {
				if (i == students.length) {
					ondone();
					return;
				}
				var s = students[i];
				if (s.assigned_spe == s.original_spe) { next(i+1); return; }
				var spe = null;
				if (s.assigned_spe != null)
					for (var j = 0; j < specializations.length; ++j)
						if (specializations[j].id == s.assigned_spe) { spe = specializations[j]; break; }
				if (spe != null)
					onprogress("Assign "+s.first_name+" "+s.last_name+" to specialization "+spe.name);
				else
					onprogress("Unassign "+s.first_name+" "+s.last_name);
				service.json("students", "assign_specialization", {student:s.people,specialization:s.assigned_spe}, function(res) {
					next(i+1);
				});
			}
			next(0);
		}
		</script>
		<?php */
	}
	
}
?>