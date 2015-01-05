<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_interview_link_centers extends SelectionPage {
	
	public function getRequiredRights() { return array("manage_interview_center"); }
	
	public function executeSelectionPage() {
		$this->requireJavascript("section.js");
		theme::css($this, "grid.css");
		
		$q = SQLQuery::create()
			->select("ExamCenter")
			->join("ExamCenter", "InterviewCenterExamCenter", array("id"=>"exam_center"))
			->join("ExamCenter", "Applicant", array("id"=>"exam_center"))
			->groupBy("ExamCenter", "id")
			->field("InterviewCenterExamCenter", "interview_center", "linked_interview_center_id")
			->field("ExamCenter", "id", "id")
			->field("ExamCenter", "name", "name")
			->countOneField("Applicant", "people", "nb_applicants")
			;
		$exam_centers = $q->execute();
		
		$interview_centers = SQLQuery::create()
			->select("InterviewCenter")
			->field("id")
			->field("name")
			->execute();
		
		$q = SQLQuery::create()
			->select("Applicant")
			->whereNotNull("Applicant", "interview_center")
			->whereNotNull("Applicant", "interview_session")
			->groupBy("Applicant", "interview_session")
			->field("Applicant", "interview_center")
			->field("Applicant", "exam_center")
			->countOneField("Applicant", "people", "nb_assigned")
			// get date of session
			->join("Applicant", "InterviewSession", array("interview_session"=>"event"))
			;
		PNApplication::$instance->calendar->joinCalendarEvent($q, "InterviewSession", "event");
		$q->field("CalendarEvent", "start", "session_start");
		$q->field("CalendarEvent", "end", "session_end");
		$applicants_assigned = $q->execute();
		$now = time();
		for ($i = 0; $i < count($exam_centers); $i++) {
			$center_id = $exam_centers[$i]["id"];
			$nb_applicants_assigned_past = 0;
			$nb_applicants_assigned_future = 0;
			foreach ($applicants_assigned as $a) {
				if ($a["exam_center"] <> $center_id) continue;
				if ($a["session_start"] > $now) $nb_applicants_assigned_future += $a["nb_assigned"];
				else $nb_applicants_assigned_past += $a["nb_assigned"];
			}
			$exam_centers[$i]["nb_applicants_assigned_past"] = $nb_applicants_assigned_past;
			$exam_centers[$i]["nb_applicants_assigned_future"] = $nb_applicants_assigned_future;
		}
		?>
		<div style='vertical-align:top'>
			<div class='info_header'>
				<img src='<?php echo theme::$icons_16["help"];?>' style='verticala-lign:bottom'/>
				Select Exam Centers, then an Interview center, and click on the button to link them together.
			</div>
			<div id='section_exam_centers' 
				style='display:inline-block;margin:10px;vertical-align:top'
				icon='/static/selection/exam/exam_center_16.png'
				title='Available Exam Centers'
			>
				<table class='grid'><tbody id='exam_centers_table'>
					<tr><th></th><th>Exam Center</th><th>Applicants</th></tr>
				</tbody></table>
			</div>
			<button id='link_button' style='margin-top:50px' class='action' disabled='disabled' onclick='addLink();return false;'>
				<img src='<?php echo theme::$icons_16["link"];?>'/> Link
			</button>
			<div id='section_interview_centers' 
				style='display:inline-block;margin:10px;vertical-align:top'
				icon='/static/selection/interview/interview_16.png'
				title='Interview Centers'
			>
				<div id='interview_centers'></div>
			</div>
		</div>
		<script type='text/javascript'>
		var exam_centers = <?php echo json_encode($exam_centers);?>;
		
		var interview_centers = <?php echo json_encode($interview_centers);?>;
		
		var section_exam = sectionFromHTML('section_exam_centers');
		var section_interview = sectionFromHTML('section_interview_centers');

		var exam_rows = [];
		var interview_divs = [];

		var added_links = [];
		var removed_links = [];
		
		var selected_exam = [];
		var selected_interview = null;
		function refreshLinkButton() {
			var button = document.getElementById("link_button");
			button.disabled = selected_exam.length > 0 && selected_interview != null ? "" : "disabled";
		};

		function refreshSave() {
			if (added_links.length == 0 && removed_links.length == 0)
				window.pnapplication.dataSaved("LinkedExamWithInterviewCenters");
			else
				window.pnapplication.dataUnsaved("LinkedExamWithInterviewCenters");
		}

		function addLink() {
			var interview_div = null;
			for (var i = 0; i < interview_divs.length; ++i) {
				if (interview_divs[i].center.id == selected_interview) interview_div = interview_divs[i];
				// uncheck radio button
				interview_divs[i].radio.checked = "";
			}
			var tr_list = [];
			for (var i = 0; i < exam_rows.length; ++i) {
				var tr = exam_rows[i];
				if (selected_exam.contains(tr.center.id)) {
					tr_list.push(tr);
					// remove the row
					tr.parentNode.removeChild(tr);
					exam_rows.splice(i,1);
					i--;
				}
			}
			// add the links
			for (var i = 0; i < selected_exam.length; ++i) {
				// check it was not a removed link
				var found = false;
				for (var j = 0; j < removed_links.length; ++j)
					if (removed_links[j].exam_center == selected_exam[i] && removed_links[i].interview_center == selected_interview) {
						found = true;
						removed_links.splice(j,1);
						break;
					}
				if (!found)
					added_links.push({exam_center:selected_exam[i],interview_center:selected_interview});
				interview_div.addExamCenter(getExamCenter(selected_exam[i]));
			}
			refreshSave();
			// unselect all and disable link button
			selected_exam = [];
			selected_interview = null;
			refreshLinkButton();
			layout.changed(document.body);
		}
		function removeLink(exam_id, interview_id) {
			// check if it was an added link
			var found = false;
			for (var i = 0; i < added_links.length; ++i) {
				if (added_links[i].exam_center == exam_id && added_links[i].interview_center == interview_id) {
					found = true;
					added_links.splice(i,1);
					break;
				}
			}
			if (!found)
				removed_links.push({exam_center:exam_id,interview_center:interview_id});
			// add the row
			createExamRow(getExamCenter(exam_id));
			refreshSave();
		}

		function createExamRow(center) {
			var table = document.getElementById('exam_centers_table');
			var tr,td;
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			var cb = document.createElement("INPUT"); cb.type = 'checkbox';
			cb.onchange = function() {
				if (this.checked) selected_exam.push(center.id);
				else selected_exam.remove(center.id);
				refreshLinkButton();
			};
			td.appendChild(cb);
			tr.appendChild(td = document.createElement("TD"));
			td.appendChild(document.createTextNode(center.name));
			tr.appendChild(td = document.createElement("TD"));
			td.appendChild(document.createTextNode(center.nb_applicants));
			td.style.textAlign = 'center';
			tr.center = center;
			exam_rows.push(tr);
			layout.changed(table);
		}
		
		function getLinkedExamCenters(center_id) {
			var list = [];
			for (var i = 0; i < exam_centers.length; ++i) if (exam_centers[i].linked_interview_center_id == center_id) list.push(exam_centers[i]);
			return list;
		}
		function getExamCenter(id) {
			for (var i = 0; i < exam_centers.length; ++i) 
				if (exam_centers[i].id == id)
					return exam_centers[i];
			return null;
		}

		function addInterviewCenter(center) {
			var container = document.getElementById('interview_centers');
			var div = document.createElement("DIV"); container.appendChild(div);
			var radio = document.createElement("INPUT");
			radio.type = 'radio';
			radio.name = 'interview_center';
			radio.onchange = function() {
				if (this.checked) selected_interview = center.id;
				refreshLinkButton();
			};
			div.appendChild(radio);
			div.appendChild(document.createTextNode(center.name));
			div.addExamCenter = function(exam_center) {
				var exam_center_div = document.createElement("DIV");
				exam_center_div.style.marginLeft = "20px";
				exam_center_div.appendChild(document.createTextNode(" - "+exam_center.name+" "));
				var button = document.createElement("BUTTON");
				button.className = "flat small";
				button.innerHTML = "<img src='"+theme.icons_16.unlink+"'/>";
				button.exam_center = exam_center.id;
				button.interview_center = center.id;
				button.onclick = function() {
					var doit = function() {
						div.removeChild(exam_center_div);
						removeLink(button.exam_center, button.interview_center);
						layout.changed(div);
					};
					if (exam_center.nb_applicants_assigned_past > 0 || exam_center.nb_applicants_assigned_future) {
						if (exam_center.nb_applicants_assigned_past == 0)
							confirmDialog(exam_center.nb_applicants_assigned_future+" applicant(s) from this Exam Center are already scheduled for an interview session.<br/>If you unlink this Exam Center, those applicants will be automatically remove from the scheduled session.<br/>Are you sure you want to do this ?", function(yes){
								if (yes) doit();
							});
						else {
							errorDialog(exam_center.nb_applicants_assigned_past+" applicant(s) from this Exam Center already had their interview (assigned to an interview session in the past).<br/>You cannot unlink this Exam Center.<br/>If you really need to do it, you need to go to the Interview Center screen, and unlink the session.");
						}
					} else
						doit();
				};
				exam_center_div.appendChild(button);
				this.appendChild(exam_center_div);
				layout.changed(div);
			};
			var exam_centers = getLinkedExamCenters(center.id);
			for (var i = 0; i < exam_centers.length; ++i)
				div.addExamCenter(exam_centers[i]);
			div.radio = radio;
			div.center = center;
			interview_divs.push(div);
		}
		
		for (var i = 0; i < exam_centers.length; ++i)
			if (!exam_centers[i].linked_interview_center_id)
				createExamRow(exam_centers[i]);

		for (var i = 0; i < interview_centers.length; ++i)
			addInterviewCenter(interview_centers[i]);

		var popup = window.parent.getPopupFromFrame(window);
		popup.addFrameSaveButton(function() {
			var locker = lockScreen(null,"Saving links...");
			service.json("selection","interview/link",{add:added_links,remove:removed_links},function(res) {
				unlockScreen(locker);
				if (res) {
					window.pnapplication.dataSaved("LinkedExamWithInterviewCenters");
					<?php if (isset($_GET["onsaved"])) echo "window.frameElement.".$_GET["onsaved"]."();"?>
				}
			});
		});
		popup.addCloseButton();
		</script>
		<?php 
	}
	
}
?>