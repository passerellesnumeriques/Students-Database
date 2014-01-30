<?php 
class page_student_grades extends Page {
	
	public function get_required_rights() { return array("consult_students_grades"); }
	
	public function execute() {
		?>
		<div style='background-color:#ffffa0;border-bottom:1px solid #e0e0ff;padding:5px;font-family:Verdana'>
			<img src='<?php echo theme::$icons_16["info"];?>' style='vertical-align:bottom'/>
			This screen is only a very beginning, and will be better soon...
		</div>
		
		<?php 
		// get student classes
		$classes = SQLQuery::create()
			->select("StudentClass")
			->whereValue("StudentClass", "people", $_GET["people"])
			->join("StudentClass", "AcademicClass", array("class"=>"id"))
			->join("AcademicClass","AcademicPeriod", array("period"=>"id"))
			->orderBy("AcademicPeriod", "start_date", true)
			->field("AcademicPeriod", "id", "period_id")
			->field("AcademicPeriod", "name", "period_name")
			->field("AcademicClass", "name", "class_name")
			->field("AcademicClass", "specialization", "spe_id")
			->execute();
		
		// create a table, for each period/class
		echo "<table id='grades_table'>";
		foreach ($classes as $c) {
			echo "<tr><th colspan=4 class='period_separator'></th></tr>";
			echo "<tr><th colspan=4 class='period_title'>";
			echo htmlentities("Period ".$c["period_name"]." (Class ".$c["class_name"].")");
			echo "</th></tr>";
			echo "<tr>";
				echo "<th style='background-color:#C0C0C0;border:1px solid black;border-bottom:0px'>Subject Code</th>";
				echo "<th style='background-color:#C0C0C0;border:1px solid black;border-left:0px;border-bottom:0px'>Subject Name</th>";
				echo "<th style='background-color:#C0C0C0;border:1px solid black;border-left:0px;border-bottom:0px'>Weight</th>";
				echo "<th style='background-color:#C0C0C0;border:1px solid black;border-left:0px;border-bottom:0px'>Grade</th>";
			echo "</tr>";
			// get the list of subjects
			$subjects = SQLQuery::create()
				->select("CurriculumSubject")
				->whereValue("CurriculumSubject", "period", $c["period_id"])
				->whereValue("CurriculumSubject", "specialization", $c["spe_id"])
				->join("CurriculumSubject", "CurriculumSubjectCategory", array("category"=>"id"))
				->join("CurriculumSubject", "CurriculumSubjectGrading", array("id"=>"subject"))
				->field("CurriculumSubject", "id", "subject_id")
				->field("CurriculumSubject", "code", "subject_code")
				->field("CurriculumSubject", "name", "subject_name")
				->field("CurriculumSubjectCategory", "id", "category_id")
				->field("CurriculumSubjectCategory", "name", "category_name")
				->field("CurriculumSubjectGrading", "weight", "weight")
				->field("CurriculumSubjectGrading", "max_grade", "max_grade")
				->field("CurriculumSubjectGrading", "passing_grade", "passing_grade")
				->execute();
			// extract list of categories, and list of subjects' ids
			$categories = array();
			$subjects_ids = array();
			foreach ($subjects as $s) {
				if (!isset($categories[$s["category_id"]])) $categories[$s["category_id"]] = $s["category_name"];
				array_push($subjects_ids, $s["subject_id"]);
			}
			// get grades of student
			if (count($subjects_ids) > 0)
				$grades = SQLQuery::create()
					->select("StudentSubjectGrade")
					->whereValue("StudentSubjectGrade", "people", $_GET["people"])
					->whereIn("StudentSubjectGrade", "subject", $subjects_ids)
					->execute();
			else
				$grades = array();
			// build the table
			$total_period = 0;
			$weights_period = 0;
			foreach ($categories as $cat_id=>$cat_name) {
				echo "<tr><th colspan=4 style='background-color:#C0C0F0;border:1px solid black;border-bottom:0px'>";
				echo htmlentities($cat_name);
				echo "</th></tr>";
				$total_cat = 0;
				$weights_cat = 0;
				foreach ($subjects as $s) {
					if ($s["category_id"] <> $cat_id) continue;
					echo "<tr>";
						echo "<td style='border-top:1px solid black;border-left:1px solid black;border-right:1px solid black'>".htmlentities($s["subject_code"])."</td>";
						echo "<td style='border-top:1px solid black;border-right:1px solid black'>".htmlentities($s["subject_name"])."</td>";
						echo "<td style='border-top:1px solid black;border-right:1px solid black;text-align:center'>".$s["weight"]."</td>";
						// look for the grade of the student
						$grade = null;
						foreach ($grades as $g) if ($g["subject"] == $s["subject_id"]) { $grade = $g["grade"]; break; }
						echo "<td style='border-top:1px solid black;border-right:1px solid black;text-align:center;background-color:".$this->grade_color($grade, $s["max_grade"], $s["passing_grade"])."'>";
						if ($grade === null)
							echo "<i>No grade</i>";
						else
							echo $grade." / ".$s["max_grade"];
						echo "</td>";
						if ($grade === null) {
							$total_cat = null;
							$total_period = null;
						} else {
							if ($total_cat !== null) {
								$total_cat += $grade*100/$s["max_grade"]*$s["weight"];
								$weights_cat += $s["weight"];
							}
						if ($total_period !== null) {
								$total_period += $grade*100/$s["max_grade"]*$s["weight"];
								$weights_period += $s["weight"];
							}
						}
					echo "</tr>";
				}
				echo "<tr><td colspan=3 style='text-align:left;font-weight:bold;background-color:#FFFFA0;border:1px solid black;border-bottom:0px'>";
				echo "Average ".htmlentities($cat_name);
				echo "</th>";
				echo "<td style='text-align:center;border:1px solid black;border-left:0px;border-bottom:0px;background-color:".$this->grade_color($total_cat !== null && $weights_cat > 0 ? $total_cat/$weights_cat : null, 100, 50)."'>";
				if ($total_cat !== null && $weights_cat > 0)
					echo round($total_cat/$weights_cat,2)." %";
				echo "</td>";
			}
			echo "<tr><td colspan=3 style='text-align:left;font-weight:bold;background-color:#F0F080;border:1px solid black;border-bottom:0px'>";
			echo "Average on Period ".htmlentities($c["period_name"]);
			echo "</th>";
			echo "<td style='text-align:center;border:1px solid black;border-left:0px;border-bottom:0px;background-color:".$this->grade_color($total_period !== null && $weights_period > 0 ? $total_period/$weights_period : null, 100, 50)."'>";
			if ($total_period !== null && $weights_period > 0)
				echo round($total_period/$weights_period, 2)." %";
			echo "</td>";
		}
		echo "</table>";
		?>
		<style type='text/css'>
		#grades_table {
			border-spacing: 0px;
		}
		#grades_table .period_title {
			background-color: #E0E0FF;
		}
		</style>
		<script type='text/javascript'>
		function init_grades_table() {
			var titles = document.getElementsByClassName('period_title');
			for (var i = 0; i < titles.length; ++i) {
				titles[i].style.border = "1px solid black";
				titles[i].style.borderBottom = "0px";
				setBorderRadius(titles[i], 5, 5, 5, 5, 0, 0, 0, 0);
			}
			var footers = document.getElementsByClassName('period_separator');
			if (footers.length > 0) {
				var list = [];
				for (var i = 0; i < footers.length; ++i) 
					if (footers[i].parentNode.previousSibling != null) 
						list.push(footers[i].parentNode.previousSibling);
				footers = list;
				var table = footers[0].parentNode.parentNode;
				footers.push(table.childNodes[table.childNodes.length-1]); // add the last row
				for (var i = 0; i < footers.length; ++i) {
					for (var j = 0; j < footers[i].childNodes.length; ++j) {
						footers[i].childNodes[j].style.borderBottom = "1px solid black";
						var left = j == 0 ? 5 : 0;
						var right = j == footers[i].childNodes.length-1 ? 5 : 0;
						setBorderRadius(footers[i].childNodes[j], 0, 0, 0, 0, left, left, right, right);
					}
				}
			}
		}
		init_grades_table();
		</script>
		<?php 
	}

	private function grade_color($grade, $max, $passing) {
		if ($grade === null) return "#C0C0C0";
		if ($grade < $passing) return "#FF4040";
		if ($grade < $passing+($max-$passing)/5) return "#FFA040";
		return "#40FF40";
	}
	
}
?>