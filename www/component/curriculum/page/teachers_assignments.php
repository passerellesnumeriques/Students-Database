<?php 
class page_teachers_assignments extends Page {
	
	public function get_required_rights() { return array("consult_curriculum"); }
	
	public function execute() {
		$academic_period_id = @$_GET["period"];
		if ($academic_period_id == null) {
			// by default, get the current one
			$today = date("Y-m-d", time());
			$academic_period = SQLQuery::create()
				->select("AcademicPeriod")
				->where("`start` <= '".$today."'")
				->where("`end` >= '".$today."'")
				->executeSingleRow();
			if ($academic_period == null) {
				// next one
				$academic_period = SQLQuery::create()
					->select("AcademicPeriod")
					->where("`start` >= '".$today."'")
					->orderBy("AcademicPeriod","start")
					->limit(0, 1)
					->executeSingleRow();
				if ($academic_period == null) {
					// last one
					$academic_period = SQLQuery::create()
						->select("AcademicPeriod")
						->orderBy("AcademicPeriod","start", false)
						->limit(0, 1)
						->executeSingleRow();
				}
			}
			$academic_period_id = $academic_period["id"];
		}
		$years = SQLQuery::create()->select("AcademicYear")->execute();
		$periods = SQLQuery::create()->select("AcademicPeriod")->orderBy("AcademicPeriod","start")->execute();
		
		$batch_periods = SQLQuery::create()
			->select("BatchPeriod")
			->whereValue("BatchPeriod","academic_period",$academic_period_id)
			->join("BatchPeriod", "StudentBatch", array("batch"=>"id"))
			->fieldsOfTable("BatchPeriod")
			->field("StudentBatch","name","batch_name")
			->orderBy("StudentBatch","start_date")
			->execute();
		$batch_periods_ids = array();
		foreach ($batch_periods as $bp) array_push($batch_periods_ids, $bp["id"]);
		
		$subjects = SQLQuery::create()
			->select("CurriculumSubject")
			->whereIn("CurriculumSubject","period",$batch_periods_ids)
			->execute();
		
		$categories_ids = array();
		$specializations_ids = array();
		foreach ($subjects as $s) {
			if (!in_array($s["category"], $categories_ids)) array_push($categories_ids, $s["category"]);
			if ($s["specialization"] <> null && !in_array($s["specialization"], $specializations_ids)) array_push($specializations_ids, $s["specialization"]);
		}
		if (count($categories_ids) == 0)
			$categories = array();
		else
			$categories = SQLQuery::create()
				->select("CurriculumSubjectCategory")
				->whereIn("CurriculumSubjectCategory", "id", $categories_ids)
				->execute();
		if (count($specializations_ids) == 0)
			$specializations = array();
		else
			$specializations = SQLQuery::create()
				->select("Specialization")
				->whereIn("Specialization", "id", $specializations_ids)
				->execute();
		
		theme::css($this, "section.css");
		?>
		<div class='page_title'>
			<img src='/static/curriculum/teacher_assign_32.png'/>
			Teachers Assignments
		</div>
		<div class='page_section_title' style='background-color:white'>
			Academic Period: <select onchange="if (this.value == <?php echo $academic_period_id;?>) return; location.href='?period='+this.value;">
			<?php
			foreach ($periods as $period) {
				echo "<option value='".$period["id"]."'";
				if ($period["id"] == $academic_period_id) echo " selected='selected'";
				$year = $this->getAcademicYear($period["year"], $years);
				echo ">Academic Year ".htmlentities($year["name"]).", ".htmlentities($period["name"]);
				echo "</option>";
			} 
			?>
			</select>
		</div>
		<table style='width:100%'><tr>
		<td valign=top><div style='display:inline-block;background-color:white' class='section'>
		<?php 
		foreach ($batch_periods as $bp) {
			echo "<div class='page_section_title'>";
			echo "Batch ".htmlentities($bp["batch_name"]).", ".htmlentities($bp["name"]);
			echo "</div>";
			$bp_subjects = array();
			$spes = array();
			foreach ($subjects as $s) {
				if ($s["period"] <> $bp["id"]) continue;
				array_push($bp_subjects, $s);
				if (!isset($spes[$s["specialization"]]))
					$spes[$s["specialization"]] = array();
				if (!in_array($s["category"], $spes[$s["specialization"]]))
					array_push($spes[$s["specialization"]], $s["category"]);
			}
			foreach ($spes as $spe_id=>$cat_ids) {
				if ($spe_id <> null)
					echo "<div class='page_section_title2'><img src='/static/curriculum/curriculum_16.png'/> Specialization ".htmlentities($this->getSpeName($spe_id, $specializations))."</div>";
				?>
				<table>
					<tr>
						<th>Code</th>
						<th>Subject Description</th>
						<th>Hours</th>
						<th>Teachers Assigned</th>
					</tr>
				<?php 
				foreach ($cat_ids as $cat_id) {
					echo "<tr>";
					echo "<td colspan=4 style='color:#602000;font-weight:bold'>";
					echo "<img src='/static/curriculum/subjects_16.png' style='vertical-align:bottom'/> ".htmlentities($this->getCategoryName($cat_id, $categories));
					echo "</td>";
					echo "</tr>";
					foreach ($bp_subjects as $subject) {
						if ($subject["specialization"] <> $spe_id) continue;
						if ($subject["category"] <> $cat_id) continue;
						echo "<tr>";
						echo "<td style='padding-left:20px'>";
						echo "<img src='/static/curriculum/subject_16.png' style='vertical-align:bottom'/> ";
						echo htmlentities($subject["code"]);
						echo "</td>";
						echo "<td>";
						echo htmlentities($subject["name"]);
						echo "</td>";
						echo "</tr>";
					}
				}
				?>
				</table>
				<?php 
			}
		}
		?>
		</div></td>
		<td valign=top align=right><div style='display:inline-block;background-color:white' class='section'>
		TODO: list of teachers
		</div></td>
		</tr></table>
		<?php 
	}
	
	private function getAcademicYear($id, $years) {
		foreach ($years as $y) if ($y["id"] == $id) return $y;
	}
	private function getSpeName($id, $spes) {
		foreach ($spes as $s) if ($s["id"] == $id) return $s["name"];
	}
	private function getCategoryName($id, $categories) {
		foreach ($categories as $c) if ($c["id"] == $id) return $c["name"];
	}
	
}
?>