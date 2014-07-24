<?php 
require_once("/../SelectionPage.inc");
class page_exam_eligibility_rules extends SelectionPage {
	public function getRequiredRights() { return array("see_exam_subject"); }
	public function executeSelectionPage(){
		$this->requireJavascript("section.js");

		// get subjects
		$subjects = SQLQuery::create()->select("ExamSubject")->execute();
		// get all subjects' parts
		$subject_parts = SQLQuery::create()->select("ExamSubjectPart")->execute();
		// get all extracts and their associated exam parts
		$extracts = SQLQuery::create()->select("ExamSubjectExtract")->execute();
		if (count($extracts) > 0) {
			$extract_parts = SQLQuery::create()->select("ExamSubjectExtractParts")->execute();
			foreach ($extracts as &$e) {
				$e["parts"] = array();
				for ($i = 0; $i < count($extract_parts); $i++) {
					if ($extract_parts[$i]["extract"] <> $e["id"]) continue;
					array_push($e["parts"], $extract_parts[$i]);
					array_splice($extract_parts, $i, 1);
					$i--;
				}
			}
		}
		
		?>
		<div style='width:100%;height:100%;overflow:hidden;display:flex;flex-direction:column;'>
			<div class='page_title' style='flex:none'>
				Eligibility rules for written exams
			</div>
			<div id='rules_page_content' style="padding:10px;overflow:hidden;flex:1 1 auto">
				<div 
					id='subjects_section'
					title='Subjects'
					collapsable='true'
				>
					<?php foreach ($subjects as $subject) { ?>
					<div style='display:inline-block;text-align:center;margin:10px;'>
						<div style="border:1px solid rgba(0,0,0,0);border-radius:5px;padding:5px;cursor:pointer" onmouseover="this.style.border='1px solid #F0D080';" onmouseout="this.style.border='1px solid rgba(0,0,0,0)';" onclick="popup_frame('/static/selection/exam/exam_subject_16.png', 'Exam Subject', '/dynamic/selection/page/exam/subject?id=<?php echo $subject["id"];?>&readonly=true');">
							<img src='/static/selection/exam/exam_subject_48.png'/><br/>
							<span style='font-size:12pt;font-weight:bold'><?php echo htmlentities($subject["name"]);?></span><br/>
						</div>
						<button class='action' onclick="extractSubject(<?php echo $subject["id"];?>);">Extract...</button>
					</div>
					<?php } ?>
				</div>
				<div 
					id='rules_section'
					title='Eligibility Rules'
				>
					<table style='width:100%'>
						<tr>
							<td valign="middle" align="center">
								<table style='border:1px solid black;'>
									<tr><th colspan=2 style='border-bottom:1px solid black;font-size:12pt'>Minimum Grades</th></tr>
									<tr><th>Subject</th><th>Grade</th></tr>
									<?php 
									/*
									foreach ($topics as $topic) {
										echo "<tr>";
										echo "<td>".htmlentities($topic["name"])."</td>";
										echo "<td><input type='text' size=4 onchange=''/> / ".$topic["max_score"]."</td>";
										echo "</tr>";
									}
									*/
									?>
								</table>
							</td>
							<td>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="page_footer">
			</div>
		</div>
		<script type='text/javascript'>
		var subjects_section = sectionFromHTML('subjects_section');
		var rules_section = sectionFromHTML('rules_section');

		function extractSubject(subject_id) {
			popup_frame(null,'Extract Parts from Subject','/dynamic/selection/page/exam/subject_extract?subject='+subject_id);
		}
		</script>
		<?php 
	}	
}
?>