<?php 
require_once("/../SelectionPage.inc");
class page_exam_eligibility_rules extends SelectionPage {
	public function getRequiredRights() { return array("see_exam_subject"); }
	public function executeSelectionPage(){
		$this->requireJavascript("vertical_layout.js");
		$this->onload("new vertical_layout('rules_page_container');");
		$this->requireJavascript("section.js");
		
		require_once("component/selection/SelectionExamJSON.inc");
		$q = SQLQuery::create()->select("ExamSubject");
		SelectionExamJSON::ExamSubjectSQL($q);
		$subjects = $q->execute();
		
		// TODO improve, use JSON...
		$topics = SQLQuery::create()->select("ExamTopicForEligibilityRule")->execute();
		$full_subject = SQLQuery::create()->select("ExamTopicFullSubject")->execute();
		$subjects_parts = SQLQuery::create()->select("ExamSubjectPart")->execute();
		$topics_parts = SQLQuery::create()->select("ExamPartTopic")->execute();
		// group topics parts per subject
		$splitted_subjects = array();
		foreach ($topics_parts as $tp) {
			// get corresponding subject part
			$subject_part = null;
			foreach ($subjects_parts as $sp) if ($sp["id"] == $tp["exam_subject_part"]) { $subject_part = $sp; break; }
			if (!isset($splitted_subjects[$subject_part["exam_subject"]]))
				$splitted_subjects[$subject_part["exam_subject"]] = array("topic_id"=>$tp["exam_topic_for_eligibility_rule"],"parts"=>array());
			array_push($splitted_subjects[$subject_part["exam_subject"]]["parts"], $subject_part);
		}
		
		?>
		<div id='rules_page_container' style='width:100%;height:100%;overflow:hidden'>
			<div class='page_title'>
				Eligibility rules for written exams
			</div>
			<div layout="fill" id='rules_page_content' style="padding:10px;overflow:hidden">
				<div 
					id='subjects_section'
					title='Subjects'
					collapsable='true'
				>
					<?php
					foreach ($subjects as $subject) {
						echo "<div style='display:inline-block;text-align:center;margin:10px'>";
						echo "<img src='/static/selection/exam/exam_subject_48.png'/><br/>";
						echo "<span style='font-size:12pt;font-weight:bold'>".htmlentities($subject["subject_name"])."</span><br/>";
						echo "<button class='action' onclick=\"splitSubject(".$subject['subject_id'].")\">Split...</button>";
						echo "</div>";
					}
					?>
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
									foreach ($topics as $topic) {
										echo "<tr>";
										echo "<td>".htmlentities($topic["name"])."</td>";
										echo "<td><input type='text' size=4 onchange=''/> / ".$topic["max_score"]."</td>";
										echo "</tr>";
									}
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

		var subjects_parts = [
		<?php
		$first_subject = true;
		foreach ($subjects as $subject) {
			if ($first_subject) $first_subject = false; else echo ",";
			echo "{subject_id:".$subject["subject_id"];
			echo ",parts:[";
			$first_part = true;
			foreach ($subjects_parts as $sp) {
				if ($sp["exam_subject"] <> $subject["subject_id"]) continue;
				if ($first_part) $first_part = false; else echo ",";
				echo "{id:".$sp["id"];
				echo ",name:".json_encode($sp["name"]);
				echo ",index:".$sp["index"];
				echo "}";
			}
			echo "]}";
		} 
		?>
		];
		
		function splitSubject(id) {
			var content = document.createElement("DIV");
			content.style.height = "300px";
			var subject_parts = null;
			for (var i = 0; i < subjects_parts.length; ++i) if (subjects_parts[i].subject_id == id) { subject_parts = subjects_parts[i].parts; break; }
			if (subject_parts == null) { alert("This subject is empty!"); return; }
			require(["popup_window.js","assign_elements.js"],function() {
				var pop = new popup_window("Split subject", null, content);
				var a = new assign_elements(content, null, null, function(part,span) {
					span.appendChild(document.createTextNode("Part "+part.index+" - "+part.name));
				}, function(a) {
					for (var i = 0; i < subject_parts.length; ++i) {
						// TODO pre-assign
						a.addElement(subject_parts[i],null,true);
					}
				});
				pop.addButton("Add a new sub-set...", "new_subset", function() {
					// TODO
				});
				pop.show();
			});
		}
		</script>
		<?php 
	}	
}
?>