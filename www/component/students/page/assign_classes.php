<?php 
class page_assign_classes extends Page {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function execute() {
		$period_id = @$_GET["period"];
		if ($period_id == null) {
			$classes = SQLQuery::create()->select("AcademicClass")->where("id", $_GET["class"])->execute();
			$period_id = $classes[0]["period"];
		} else {
			$classes = SQLQuery::create()->select("AcademicClass")->where("period", $period_id)->execute();
		}
		$period = SQLQuery::create()->select("AcademicPeriod")->where("id", $period_id)->execute_single_row();
		$specializations = array();
		foreach ($classes as $cl) if ($cl["specialization"] <> null && !in_array($cl["specialization"], $specializations)) array_push($specializations, $cl["specialization"]);
		if (count($specializations) == 0)
			$specializations = array(array("id"=>0, "classes"=>$classes));
		else {
			$specializations = SQLQuery::create()->select("Specialization")->where_in("Specialization", "id", $specializations)->execute();
			foreach ($specializations as &$spe) {
				$spe["classes"] = array();
				foreach ($classes as $cl)
					if ($cl["specialization"] == $spe["id"])
						array_push($spe["classes"], $cl);
				unset($spe);
			}
		}
		$assigned_students = SQLQuery::create()
			->select("Student")
			->join("Student", "StudentClass", array("people"=>"people"))
			->join("StudentClass", "AcademicClass", array("class"=>"id"))
			->where_value("AcademicClass", "period", $period_id)
			->field("Student", "people", "people")
			->field("StudentClass", "class", "class")
			->execute();
		$students_period = SQLQuery::create()
			->select("Student")
			->where_value("Student", "batch", $period["batch"])
			->where("Student.exclusion_date IS NULL OR Student.exclusion_date > '".$period["start_date"]."'")
			->join("Student", "People", array("people"=>"id"))
			->field("Student", "specialization", "specialization")
			->field("Student", "people", "people")
			->field("People", "first_name", "first_name")
			->field("People", "last_name", "last_name")
			->execute();
		
		echo "<table class='all_borders' style='white-space:nowrap'>";
		echo "<tr><th colspan=3>Assignment of students to ".(isset($_GET["class"]) ? "class ".$classes[0]["name"] : "classes of period ".$period["name"])."</th></tr>";
		foreach ($specializations as &$spe) {
			if ($spe["id"] <> 0)
				echo "<tr><th colspan=3>Specialization ".$spe["name"]."</th></tr>";
			echo "<tr>";
			echo "<th>Non-assigned students</th>";
			echo "<th></th>";
			echo "<th>Class ".$spe["classes"][0]["name"]."</th>";
			echo "</tr>";
			echo "<tr>";
			echo "<td id='non_assigned_".$spe["id"]."' rowspan=".((count($spe["classes"])*2)-1)." valign=top></td>";
			echo "<td valign='middle'>";
				echo "<div class='button' onclick='assign(".$spe["id"].",".$spe["classes"][0]["id"].");' title='Assign'><img src='".theme::$icons_16["right"]."'/></div>";
				echo "<br/>";
				echo "<div class='button' onclick='unassign(".$spe["id"].",".$spe["classes"][0]["id"].");' title='Assign'><img src='".theme::$icons_16["left"]."'/></div>";
			echo "</td>";
			echo "<td id='assigned_".$spe["id"]."_".$spe["classes"][0]["id"]."' valign=top></td>";
			echo "</tr>";
			for ($i = 1; $i < count($spe["classes"]); $i++) {
				echo "<tr>";
				echo "<th></th>";
				echo "<th>Class ".$spe["classes"][$i]["name"]."</th>";
				echo "</tr>";
				echo "<tr>";
				echo "<td valign='middle'>";
					echo "<div class='button' onclick='assign(".$spe["id"].",".$spe["classes"][$i]["id"].");' title='Assign'><img src='".theme::$icons_16["right"]."'/></div>";
					echo "<br/>";
					echo "<div class='button' onclick='unassign(".$spe["id"].",".$spe["classes"][$i]["id"].");' title='Assign'><img src='".theme::$icons_16["left"]."'/></div>";
				echo "</td>";
				echo "<td id='assigned_".$spe["id"]."_".$spe["classes"][$i]["id"]."' valign=top></td>";
				echo "</tr>";
			}
		}
		echo "</table>";
		?>
		<script type='text/javascript'>
		var specializations = [<?php
		$first_spe = true;
		foreach ($specializations as &$spe) {
			if ($first_spe) $first_spe = false; else echo ",";
			echo "{id:".$spe["id"];
			echo ",students:[";
			$first_student = true;
			foreach ($students_period as $s) {
				if ($spe["id"] <> 0 && $s["specialization"] <> $spe["id"]) continue;
				if ($first_student) $first_student = false; else echo ",";
				echo "{";
				echo "people:".$s["people"];
				echo ",first_name:".json_encode($s["first_name"]);
				echo ",last_name:".json_encode($s["last_name"]);
				$c = null;
				foreach ($assigned_students as $as)
					if ($as["people"] == $s["people"]) {
						$c = $as["class"];
						break;
					}
				if ($c == null) $c = 0;
				echo ",original_class:".$c;
				echo ",assigned_class:".$c;
				echo "}";
			}
			echo "]";
			echo "}\n";
		} 
		?>];
		var classes = [<?php
		$first = true;
		foreach ($classes as $c) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$c["id"];
			echo ",name:".json_encode($c["name"]);
			echo "}";
		} 
		?>];
		for (var i = 0; i < specializations.length; ++i) {
			for (var j = 0; j < specializations[i].students.length; ++j) {
				var s = specializations[i].students[j];
				s.div = document.createElement("DIV");
				s.div.appendChild(s.cb = document.createElement("INPUT"));
				s.cb.type = 'checkbox';
				s.div.appendChild(document.createTextNode(s.first_name+" "+s.last_name));
				var e;
				if (s.original_class == 0)
					e = document.getElementById("non_assigned_"+specializations[i].id);
				else
					e = document.getElementById("assigned_"+specializations[i].id+"_"+s.original_class);
				if (e)
					e.appendChild(s.div);
			}
		}
		function assign(spe, cl) {
			for (var i = 0; i < specializations.length; ++i) {
				if (specializations[i].id != spe) continue;
				for (var j = 0; j < specializations[i].students.length; ++j) {
					var s = specializations[i].students[j];
					if (s.assigned_class != 0) continue;
					if (!s.cb.checked) continue;
					s.cb.checked = '';
					s.assigned_class = cl;
					s.div.parentNode.removeChild(s.div);
					document.getElementById("assigned_"+spe+"_"+cl).appendChild(s.div);
				}
			}
			if (window.parent.get_popup_window_from_frame) {
				var p = window.parent.get_popup_window_from_frame(window);
				if (p) p.resize();
			}
		}
		function unassign(spe, cl) {
			for (var i = 0; i < specializations.length; ++i) {
				if (specializations[i].id != spe) continue;
				for (var j = 0; j < specializations[i].students.length; ++j) {
					var s = specializations[i].students[j];
					if (s.assigned_class != cl) continue;
					if (!s.cb.checked) continue;
					s.cb.checked = '';
					s.assigned_class = 0;
					s.div.parentNode.removeChild(s.div);
					document.getElementById("non_assigned_"+spe).appendChild(s.div);
				}
			}
			if (window.parent.get_popup_window_from_frame) {
				var p = window.parent.get_popup_window_from_frame(window);
				if (p) p.resize();
			}
		}
		function save(onprogress,ondone) {
			var next=function(i,j) {
				if (i == specializations.length) { ondone(); return; }
				if (j == specializations[i].students.length) { next(i+1,0); return; }
				var s = specializations[i].students[j];
				if (s.original_class == s.assigned_class) { next(i,j+1); return; }
				if (s.assigned_class == 0) {
					var cl = null;
					for (var k = 0; k < classes.length; ++k)
						if (classes[k].id == s.original_class) { cl = classes[k]; break; }
					onprogress("Unassign "+s.first_name+" "+s.last_name+" from class "+cl.name);
				} else {
					var cl = null;
					for (var k = 0; k < classes.length; ++k)
						if (classes[k].id == s.assigned_class) { cl = classes[k]; break; }
					onprogress("Assign "+s.first_name+" "+s.last_name+" to class "+cl.name);
				}
				service.json("students","assign_class",{student:s.people,clas:s.assigned_class,period:<?php echo $period_id;?>},function(res){
					next(i,j+1);
				});
			};
			next(0,0);
		}
		</script>
		<?php 
	}
	
}
?>