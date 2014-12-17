<?php 
class page_student_grades extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		echo "<div style='width:100%;height:100%;overflow:hidden;display:flex;flex-direction:column;'>";
		
		if (isset($_GET["title"])) {
			echo "<div class='page_title' style='flex:none'>";
			echo "<img src='/static/transcripts/grades_32.png'/> Grades";
			echo "</div>";
		}
		
		$people_id = $_GET["people"];
		$can_see_grades = PNApplication::$instance->user_management->has_right("consult_students_grades");
		if (!$can_see_grades && $people_id != PNApplication::$instance->user_management->people_id) {
			PNApplication::error("Access denied");
			return;
		}
		
		$published_grades = SQLQuery::create()->select("PublishedTranscriptStudentSubjectGrade")->whereValue("PublishedTranscriptStudentSubjectGrade","people",$people_id)->execute();
		$current_grades = null;
		if ($can_see_grades) {
			$current_academic_period = PNApplication::$instance->curriculum->getCurrentAcademicPeriod();
			if ($current_academic_period <> null) {
				$student = PNApplication::$instance->students->getStudent($people_id);
				$batch_id = $student["batch"];
				$current_batch_period = PNApplication::$instance->curriculum->getBatchPeriodFromAcademicPeriod($batch_id, $current_academic_period["id"]);
				if ($current_batch_period <> null) {
					$subjects = PNApplication::$instance->curriculum->getSubjects($batch_id, $current_batch_period["id"], $student["specialization"]);
					if (count($subjects) > 0) {
						$categories = PNApplication::$instance->curriculum->getSubjectCategories();
						$subjects_ids = array();
						foreach ($subjects as $s) array_push($subjects_ids, $s["id"]);
						$current_grades = PNApplication::$instance->transcripts->getStudentGrades($people_id, $subjects_ids);
						$subjects_grading = SQLQuery::create()->select("CurriculumSubjectGrading")->whereIn("CurriculumSubjectGrading","subject",$subjects_ids)->execute();
					}
				}
			}
		}
		if (count($published_grades) == 0 && (!$can_see_grades || $current_grades == null)) {
			echo "<div class='info_box'>No transcript yet</div>";
		} else {
			theme::css($this, "print.css");
			?>
			<script type='text/javascript'>
				function buildPages(content) {
					if (content.offsetHeight < 780) {
						addClassName(content, "page_layout");
						return;
					}
					var knowledge = [];
					var page_header = null;
					var page_footer = null;
					var sections = [];
					for (var i = 0; i < content.childNodes.length; ++i) {
						if (content.childNodes[i].nodeType != 1) continue;
						if (hasClassName(content.childNodes[i], "page_layout_header"))
							page_header = content.childNodes[i];
						else if (hasClassName(content.childNodes[i], "page_layout_footer"))
							page_footer = content.childNodes[i];
						else if (hasClassName(content.childNodes[i], "page_layout_section"))
							sections.push(content.childNodes[i]);
					}
					var fixed = page_header ? getHeight(page_header,knowledge) : 0;
					fixed += page_footer ? getHeight(page_footer,knowledge) : 0;
					// first page
					var h = fixed;
					var i = 0;
					do {
						h += getHeight(sections[i],knowledge);
						i++;
					} while (i < sections.length && h+getHeight(sections[i],knowledge) < 760);
					content.style.position = "relative";
					var pages_numbers = [];
					var f = document.createElement("DIV");
					f.style.position = "absolute";
					f.style.bottom = "5px";
					f.style.right = "5px";
					content.appendChild(f);
					pages_numbers.push(f);
					addClassName(content, "page_layout");
					// next pages
					var next = content.nextSibling;
					while (i < sections.length) {
						var page = document.createElement("DIV");
						page.style.position = "relative";
						page.className = content.className;
						if (page_header) {
							var header = document.createElement("DIV");
							header.className = page_header.className;
							header.innerHTML = page_header.innerHTML;
							page.appendChild(header);
						}
						h = fixed;
						do {
							sections[i] = page.appendChild(sections[i]);
							h += getHeight(sections[i],knowledge);
							i++;
						} while (i < sections.length && h+getHeight(sections[i],knowledge) < 760);
						if (page_footer) {
							var footer = document.createElement("DIV");
							footer.className = page_footer.className;
							footer.innerHTML = page_footer.innerHTML;
							page.appendChild(footer);
						}
						f = document.createElement("DIV");
						f.style.position = "absolute";
						f.style.bottom = "5px";
						f.style.right = "5px";
						page.appendChild(f);
						pages_numbers.push(f);
						if (next) content.parentNode.insertBefore(page, next);
						else content.parentNode.appendChild(page);
					}
					for (var i = 0; i < pages_numbers.length; ++i) {
						pages_numbers[i].innerHTML = "Page "+(i+1)+" / "+(pages_numbers.length);
					}
				}
				function makePageLayout(container) {
					var pages = [];
					for (var i = 0; i < container.childNodes.length; ++i) {
						var page = container.childNodes[i];
						if (page.nodeType != 1) continue;
						if (page.nodeName == "DIV") pages.push(page);
					}
					for (var i = 0; i < pages.length; ++i)
						buildPages(pages[i]);
				}
			</script>
			<?php 
			$transcripts_ids = array();
			foreach ($published_grades as $pg) if (!in_array($pg["id"], $transcripts_ids)) array_push($transcripts_ids, $pg["id"]);
			$transcripts = SQLQuery::create()->select("PublishedTranscript")->whereIn("PublishedTranscript","id",$transcripts_ids)->execute();
			
			$periods_ids = array();
			foreach ($transcripts as $t) if (!in_array($t["period"], $periods_ids)) array_push($periods_ids, $t["period"]);
			$periods = PNApplication::$instance->curriculum->getBatchPeriodsById($periods_ids);
			
			require_once("component/transcripts/page/design.inc");
			echo "<div id='transcripts_tabs' style='display:flex;flex-direction:column;height:100%;padding:5px;'>";
			$script = "";
			if ($current_grades <> null) {
				$subjects_by_cat = array();
				foreach ($subjects as $s) {
					$grading = null;
					foreach ($subjects_grading as $sg) if ($sg["subject"] == $s["id"]) { $grading = $sg; break; }
					if ($grading == null || $grading["max_grade"] == null || $grading["passing_grade"] == null) continue;
					$grade = null;
					foreach ($current_grades as $g) if ($g["subject"] == $s["id"]) { $grade = $g["grade"]; break; }
					if ($grade == null) continue;
					$s["grading"] = $grading;
					$s["student_grade"] = $grade;
					if (!isset($subjects_by_cat[$s["category"]])) $subjects_by_cat[$s["category"]] = array();
					array_push($subjects_by_cat[$s["category"]], $s);
				}
				if (count($subjects_by_cat) == 0)
					$current_grades = null;
				else {
					$this->requireJavascript("typed_field.js");
					$this->requireJavascript("field_grade.js");
					theme::css($this, "grid.css");
					$systems = include("component/transcripts/GradingSystems.inc");
					if (isset($_COOKIE["grading_system"]))
						$grading_system = $_COOKIE["grading_system"];
					else {
						$d = PNApplication::$instance->getDomainDescriptor();
						$grading_system = $d["transcripts"]["default_grading_system"];
					}
					echo "<div title=\"Current grades for ".$current_batch_period["name"]."\" style='overflow:auto;flex:1 1 auto;'>";
					echo "<div style='display:flex;flex-direction:column;align-items:center;padding-top:5px;'>";
					echo "<div style='margin-bottom:5px'>";
					echo "Grading system: <select onchange='updateGradingSystem(this.options[this.selectedIndex].text, this.value);'>";
					foreach ($systems as $name=>$conf) echo "<option value='$conf'".($grading_system == $name ? " selected='selected'" : "").">$name</option>";
					echo "</select>";
					echo "</div>";
					echo "<table class='grid' style='border:1px solid black;box-shadow:2px 2px 2px 0px #808080;font-size:10pt;'><thead>";
					echo "<tr><th>Subject</th><th>Grade</th></tr>";
					echo "</thead><tbody>";
					$script .= "var grades_fields = [];var field;\n";
					foreach ($subjects_by_cat as $cat_id=>$list) {
						foreach ($categories as $cat) if ($cat["id"] == $cat_id) { $cat_name = $cat["name"]; break; }
						echo "<tr><td colspan=2 style='font-weight:bold;text-align:center;color:#602000'>".toHTML($cat_name)."</td></tr>";
						foreach ($list as $s) {
							echo "<tr>";
							echo "<td>".toHTML($s["code"]." - ".$s["name"])."</td>";
							$grade_id = $this->generateID();
							echo "<td id='$grade_id'></td>";
							$script .= "field = new field_grade(".floatval($s["student_grade"]).",false,{max:".floatval($s["grading"]["max_grade"]).",passing:".floatval($s["grading"]["passing_grade"]).",system:".json_encode($systems[$grading_system])."});";
							$script .= "document.getElementById('$grade_id').appendChild(field.getHTMLElement());";
							$script .= "field.fillWidth();";
							$script .= "grades_fields.push(field);\n";
							echo "</tr>";
						}
					}
					echo "</tbody></table>";
					echo "</div>";
					echo "</div>";
				}
			}
			foreach ($transcripts as $t) {
				$period = null;
				foreach ($periods as $p) if ($p["id"] == $t["period"]) { $period = $p; break; }
				echo "<div title=".json_encode($period["name"].", ".$t["name"],JSON_HEX_APOS)." style='text-align:center;overflow:auto;flex:1 1 auto;'>";
				//echo "<div style='margin:5px;border-radius:5px;padding:10px;background-color:white;display:inline-block;box-shadow: 2px 2px 2px 0px #808080;border:1px solid #C0C0C0;'>";
				$div_id = $this->generateID();
				echo "<div id='$div_id'>";
				generatePublishedTranscript($t["id"], $people_id);
				$script .= "makePageLayout(document.getElementById('$div_id'));";
				echo "</div>";
				//echo "</div>";
				echo "</div>";
			}
			echo "</div>";
			$this->requireJavascript("tabs.js");
			?>
			<script type='text/javascript'>
			<?php echo $script; ?>
			var t = new tabs('transcripts_tabs');
			t.header.style.flex = "none";
			t.content.style.flex = "1 1 auto";
			t.content.style.display = "flex";
			t.content.style.flexDirection = "row";
			function updateGradingSystem(name, system) {
				setCookie("grading_system",name,365*24*60,"/dynamic/transcripts/page/");
				for (var i = 0; i < grades_fields.length; ++i)
					grades_fields[i].setGradingSystem(system);
			}
			</script>
			<?php 
		}
		echo "</div>";
	}
	
}
?>