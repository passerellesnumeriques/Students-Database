<?php 
class page_exam_link_is_with_exam_center extends Page {
	
	public function getRequiredRights() { return array("manage_exam_center"); }
	
	public function execute() {
		$this->requireJavascript("section.js");
		theme::css($this, "grid.css");
		
		require_once("component/selection/SelectionInformationSessionJSON.inc");
		$q = SQLQuery::create()
			->select("InformationSession")
			->join("InformationSession", "ExamCenterInformationSession", array("id"=>"information_session"))
			->field("ExamCenterInformationSession", "exam_center", "linked_exam_center_id")
			->join("InformationSession", "Applicant", array("id"=>"information_session"))
			->countOneField("Applicant", "people", "nb_applicants")
			->groupBy("InformationSession", "id")
			;
		SelectionInformationSessionJSON::InformationSessionSQL($q);
		$is_list = $q->execute();
		
		$centers = SQLQuery::create()
			->select("ExamCenter")
			->field("id")
			->field("name")
			->execute();
		$q = SQLQuery::create()
			->select("Applicant")
			->whereNotNull("Applicant", "exam_center")
			->whereNotNull("Applicant", "exam_session")
			->groupBy("Applicant", "exam_center")
			->field("Applicant", "exam_center")
			->field("Applicant", "information_session")
			->countOneField("Applicant", "people", "nb_assigned")
			// get date of session
			->join("Applicant", "InformationSession", array("information_session"=>"id"))
			;
		PNApplication::$instance->calendar->joinCalendarEvent($q, "InformationSession", "date");
		$q->field("CalendarEvent", "start", "session_start");
		$q->field("CalendarEvent", "end", "session_end");
		$applicants_assigned = $q->execute();
		// TODO check no results
		?>
		<div style='vertical-align:top'>
			<div class='info_header'>
				<img src='<?php echo theme::$icons_16["help"];?>' style='verticala-lign:bottom'/>
				Select Information Sessions, then an Exam center, and click on the button to link them together.
			</div>
			<div id='section_is' 
				style='display:inline-block;margin:10px;vertical-align:top'
				icon='/static/selection/is/is_16.png'
				title='Available Information Sessions'
			>
				<table class='grid'><tbody id='is_table'>
					<tr><th></th><th>Information Session</th><th>Applicants</th></tr>
				</tbody></table>
			</div>
			<button id='link_button' style='margin-top:50px' class='action' disabled='disabled' onclick='addLink();return false;'>
				<img src='<?php echo theme::$icons_16["link"];?>'/> Link
			</button>
			<div id='section_ec' 
				style='display:inline-block;margin:10px;vertical-align:top'
				icon='/static/selection/exam/exam_center_16.png'
				title='Exam Centers'
			>
				<div id='centers'></div>
			</div>
		</div>
		<script type='text/javascript'>
		var is_list = <?php echo SelectionInformationSessionJSON::InformationSessionsJSON($is_list);?>;
		<?php 
		$now = time();
		// add information to each IS
		for ($i = 0; $i < count($is_list); $i++) {
			echo "is_list[$i].nb_applicants = ".$is_list[$i]["nb_applicants"].";\n";
			echo "is_list[$i].linked_exam_center_id = ".json_encode($is_list[$i]["linked_exam_center_id"]).";\n";
			$nb_assigned_past = 0;
			$nb_assigned_future = 0;
			foreach ($applicants_assigned as $app) {
				if ($app["information_session"] <> $is_list[$i]["is_id"]) continue;
				if ($app["session_start"] > $now) $nb_assigned_future++;
				else $nb_assigned_past++;
			}
			echo "is_list[$i].nb_applicants_assigned_future = ".$nb_assigned_future.";\n";
			echo "is_list[$i].nb_applicants_assigned_past = ".$nb_assigned_past.";\n";
		}
		?>
		
		var centers = [<?php
		$first = true;
		foreach ($centers as $center) {
			if ($first) $first = false; else echo ",";
			echo "{id:".$center["id"].",name:".json_encode($center["name"])."}";
		} 
		?>];
		
		var section_is = sectionFromHTML('section_is');
		var section_ec = sectionFromHTML('section_ec');

		var is_rows = [];
		var exam_divs = [];

		var added_links = [];
		var removed_links = [];
		
		var selected_is = [];
		var selected_exam = null;
		function refreshLinkButton() {
			var button = document.getElementById("link_button");
			button.disabled = selected_is.length > 0 && selected_exam != null ? "" : "disabled";
		};

		function refreshSave() {
			if (added_links.length == 0 && removed_links.length == 0)
				window.pnapplication.dataSaved("LinkedISWithExamCenters");
			else
				window.pnapplication.dataUnsaved("LinkedISWithExamCenters");
		}

		function addLink() {
			var exam_div = null;
			for (var i = 0; i < exam_divs.length; ++i) {
				if (exam_divs[i].center.id == selected_exam) exam_div = exam_divs[i];
				// uncheck radio button
				exam_divs[i].radio.checked = "";
			}
			var tr_list = [];
			for (var i = 0; i < is_rows.length; ++i) {
				var tr = is_rows[i];
				if (selected_is.contains(tr.is.id)) {
					tr_list.push(tr);
					// remove the row
					tr.parentNode.removeChild(tr);
					is_rows.splice(i,1);
					i--;
				}
			}
			// add the links
			for (var i = 0; i < selected_is.length; ++i) {
				// check it was not a removed link
				var found = false;
				for (var j = 0; j < removed_links.length; ++j)
					if (removed_links[j].is == selected_is[i] && removed_links[i].exam == selected_exam) {
						found = true;
						removed_links.splice(j,1);
						break;
					}
				if (!found)
					added_links.push({is:selected_is[i],exam:selected_exam});
				exam_div.addIS(getIS(selected_is[i]));
			}
			refreshSave();
			// unselect all and disable link button
			selected_is = [];
			selected_exam = null;
			refreshLinkButton();
			layout.invalidate(document.body);
		}
		function removeLink(is_id, center_id) {
			// check if it was an added link
			var found = false;
			for (var i = 0; i < added_links.length; ++i) {
				if (added_links[i].is == is_id && added_links[i].exam == center_id) {
					found = true;
					added_links.splice(i,1);
					break;
				}
			}
			if (!found)
				removed_links.push({is:is_id,exam:center_id});
			// add the row
			createISRow(getIS(is_id));
			refreshSave();
		}

		function createISRow(is) {
			var table = document.getElementById('is_table');
			var tr,td;
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			var cb = document.createElement("INPUT"); cb.type = 'checkbox';
			cb.onchange = function() {
				if (this.checked) selected_is.push(is.id);
				else selected_is.remove(is.id);
				refreshLinkButton();
			};
			td.appendChild(cb);
			tr.appendChild(td = document.createElement("TD"));
			td.appendChild(document.createTextNode(is.name));
			tr.appendChild(td = document.createElement("TD"));
			td.appendChild(document.createTextNode(is.nb_applicants));
			td.style.textAlign = 'center';
			tr.is = is;
			is_rows.push(tr);
			layout.invalidate(table);
		}

		function getLinkedIS(center_id) {
			var list = [];
			for (var i = 0; i < is_list.length; ++i) if (is_list[i].linked_exam_center_id == center_id) list.push(is_list[i]);
			return list;
		}
		function getIS(id) {
			for (var i = 0; i < is_list.length; ++i) 
				if (is_list[i].id == id)
					return is_list[i];
			return;
		}

		function addCenter(center) {
			var container = document.getElementById('centers');
			var div = document.createElement("DIV"); container.appendChild(div);
			var radio = document.createElement("INPUT");
			radio.type = 'radio';
			radio.name = 'exam_center';
			radio.onchange = function() {
				if (this.checked) selected_exam = center.id;
				refreshLinkButton();
			};
			div.appendChild(radio);
			div.appendChild(document.createTextNode(center.name));
			div.addIS = function(is) {
				var is_div = document.createElement("DIV");
				is_div.style.marginLeft = "20px";
				is_div.appendChild(document.createTextNode(" - "+is.name+" "));
				var button = document.createElement("BUTTON");
				button.className = "flat small";
				button.innerHTML = "<img src='"+theme.icons_16.unlink+"'/>";
				button.is = is.id;
				button.exam = center.id;
				button.onclick = function() {
					var doit = function() {
						div.removeChild(is_div);
						removeLink(button.is, button.exam);
						layout.invalidate(div);
					};
					if (is.nb_applicants_assigned_past > 0 || is.nb_applicants_assigned_future) {
						if (is.nb_applicants_assigned_past == 0)
							confirm_dialog(is.nb_applicants_assigned_future+" applicant(s) from this Information Session are already scheduled for an exam session.<br/>If you unlink this Information Session, those applicants will be automatically remove from the scheduled session.<br/>Are you sure you want to do this ?", function(yes){
								if (yes) doit();
							});
						else {
							error_dialog(is.nb_applicants_assigned_past+" applicant(s) from this Information Session already had their exam (assigned to an exam session in the past).<br/>You cannot unlink this Information Session.<br/>If you really need to do it, you need to go to the Exam Center screen, and unlink the session.");
						}
					} else
						doit();
				};
				is_div.appendChild(button);
				this.appendChild(is_div);
				layout.invalidate(div);
			};
			var is = getLinkedIS(center.id);
			for (var i = 0; i < is.length; ++i)
				div.addIS(is[i]);
			div.radio = radio;
			div.center = center;
			exam_divs.push(div);
		}
		
		for (var i = 0; i < is_list.length; ++i)
			if (!is_list[i].linked_exam_center_id)
				createISRow(is_list[i]);

		for (var i = 0; i < centers.length; ++i)
			addCenter(centers[i]);

		var popup = window.parent.get_popup_window_from_frame(window);
		popup.addFrameSaveButton(function() {
			// TODO
		});
		popup.addCloseButton();
		</script>
		<?php 
	}
	
}
?>