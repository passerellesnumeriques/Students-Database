<?php
require_once("component/selection/page/SelectionPage.inc");
require_once("component/selection/SelectionJSON.inc");
class page_exam_subjects extends SelectionPage {
	
	public function getRequiredRights() {return array("see_exam_subject");}
	
	public function executeSelectionPage() {
		theme::css($this, "section.css");
		require_once("component/selection/SelectionExamJSON.inc");
		$q = SQLQuery::create()->select("ExamSubject");
		SelectionExamJSON::ExamSubjectSQL($q);
		$subjects = $q->execute();
		$can_edit = PNApplication::$instance->user_management->has_right("manage_exam_subject");
		?>
		<div style='width:100%;height:100%;overflow:hidden;display:flex;flex-direction:column'>
			<div class='page_title' style='flex:none;padding:3px 10px'>
				<img src='/static/selection/exam/exam_clip_art.png'/>
				Written Exam Subjects
			</div>
			<div style="padding:10px;overflow:hidden;flex:1 1 auto;display:flex;flex-direction:row">
				<div id='subjects' class='section_block' style="display:inline-block;background-color:white;overflow-y:auto;margin-right:10px;width:200px;flex:none">
					<span id='no_subject' style='display:none;position:absolute;'>
						<i>No subject defined yet</i>
					</span>
				</div>
				<div class='section_block' style="display:inline-block;overflow:hidden;flex:1 1 auto;display:flex;flex-direction:column;border: 1px solid #C0C0C0;padding-top:0px;padding-bottom:0px;">
					<iframe id='subject_frame' style='flex:1 1 auto;border:none' src='/static/application/message.html?padding=5&text=<?php echo urlencode("Select a subject to display it here");?>'>
					</iframe>
					<div class='page_footer' id='subject_footer' style='visibility:hidden;flex:none;background-color:white;<?php if (!$can_edit) echo "display:none;"?>'>
						<button class="action" id='save_button'><img src='<?php echo theme::$icons_16['save'];?>'/> Save</button>
						<button class="action" id='cancel_button'><img src='<?php echo theme::$icons_16['cancel'];?>'/> Cancel modifications</button>
						<?php if (PNApplication::$instance->selection->getOneConfigAttributeValue("set_correct_answer")) { ?>
						<button class="action" id='import_answers_from_clickers'><img src='<?php echo theme::make_icon("/static/selection/exam/sunvote_16.png", theme::$icons_10["_import"]);?>'/> Import answers from Clickers</button>
						<button class="action" id='export_answers_to_clickers'><img src='<?php echo theme::make_icon("/static/selection/exam/sunvote_16.png", theme::$icons_10["_export"]);?>'/> Export answers to Clickers</button>
						<?php } ?>
					</div>
				</div>
			</div>
			<div class="page_footer" style="flex:none;<?php if (!$can_edit) echo "display:none;"?>">
				<?php if ($can_edit) {?>
				<button class='action green' onclick="newSubject();"><img src='<?php echo theme::make_icon("/static/selection/exam/subject_white.png",theme::$icons_10['add'],"right_bottom");?>'/> New Subject</button>
				<button class='action' onclick="copySubject();"><img src='<?php echo theme::$icons_16['copy'];?>'/> Copy a subject from previous campaign</button>
				<?php } ?>
			</div>
		</div>
		<style type='text/css'>
		.subject_div {
		}
		.subject_box {
			margin: 5px;
			padding: 5px;
			text-align: center;
			border: 1px solid rgba(0,0,0,0);
			border-radius: 5px;
			cursor: pointer;
			width: 150px;
		}
		.subject_box.selected {
			border: 1px solid #F0D080;
			background-color: #FFF0D0;
			background: linear-gradient(to bottom, #FFF0D0 0%, #F0D080 100%);
		}
		.subject_box:hover {
			border: 1px solid #F0D080;
		}
		.subject_box .subject_name {
			font-size: 12pt;
			font-weight: bold;
			color: black;
		}
		.subject_box .subject_points {
			font-size: 10pt;
			color: #606060;
		}
		.subject_actions_container {
		}
		.subject_actions_container>button {
			display: block;
			margin: 5px 0px;
		}
		</style>
		<script type='text/javascript'>
		var subjects = <?php echo SelectionExamJSON::ExamSubjectsJSON($subjects); ?>;
		var subjects_controls = [];
		var selected_index = -1;

		function SubjectControl(subject) {
			var t=this;
			this.subject = subject;
			this._init = function() {
				var container = document.getElementById('subjects');
				this.div = document.createElement("DIV"); container.appendChild(this.div);
				this.div.className = "subject_div";
				this.box = document.createElement("DIV"); this.div.appendChild(this.box);
				this.box.className = "subject_box";
				this.box.innerHTML = "<img src='/static/selection/exam/exam_subject_48.png'/><br/>";
				var name = document.createElement("DIV"); this.box.appendChild(name);
				name.className = "subject_name";
				name.appendChild(document.createTextNode(subject.name));
				window.top.datamodel.registerCellSpan(window, "ExamSubject", "name", subject.id, name);
				var points = document.createElement("DIV"); this.box.appendChild(points);
				points.className = "subject_score";
				var nb_points = document.createElement("SPAN");
				nb_points.innerHTML = subject.max_score;
				points.appendChild(nb_points);
				window.top.datamodel.registerCellSpan(window, "ExamSubject", "max_score", subject.id, nb_points);
				points.appendChild(document.createTextNode(" point(s)"));
				this.box.onclick = function() {
					var frame = document.getElementById('subject_frame');
					new LoadingFrame(frame);
					frame.ready = function() {
						showSubjectActions(subject);
						this.ready = null;
					};
					frame.src = "/dynamic/selection/page/exam/subject?id="+subject.id+"&onready=ready";
					this.className = "subject_box selected";
					selected_index = subjects.indexOf(subject);
					for (var i = 0; i < subjects_controls.length; ++i)
						if (subjects_controls[i] != t) subjects_controls[i].box.className = "subject_box";
				};
				this.actions_container = document.createElement("DIV");
				this.actions_container.className = "subject_actions_container";
				this.div.appendChild(this.actions_container);

				<?php if ($can_edit) { ?>
				var remove_button = document.createElement("BUTTON");
				this.actions_container.appendChild(remove_button);
				remove_button.className = "action red";
				remove_button.innerHTML = "<img src='"+theme.icons_16.remove_white+"'/> Remove this subject";
				// TODO disable remove button if already some grades, or eligibility rules associated to it
				remove_button.onclick = function() {
					confirm_dialog("Are you sure you want to remove this exam ?",
						function(answer){
							if(!answer) return;
							var locker = lock_screen(null,"Removing subject...");
							service.json("selection","exam/remove_subject",{id:subject.id},function(res){
								unlock_screen(locker);
								if(!res)
									error_dialog("An error occured");
								else {
									if (selected_index == subjects.indexOf(subject)) {
										var frame = document.getElementById('subject_frame');
										getIFrameWindow(frame).pnapplication.cancelDataUnsaved();
										frame.src = "/static/application/message.html?padding=5&text=<?php echo urlencode("Select a subject to display it here");?>";
										selected_index = -1;
									}
									t.div.parentNode.removeChild(t.div);
									subjects.remove(subject);
									subjects_controls.remove(t);
								}
							});
						}
					);
				};
				<?php } ?>
				require("animation.js",function() {
					animation.appearsOnOver(t.div, [t.actions_container]);
				});
			};
			this._init();
		}

		function newSubject() {
			var frame = document.getElementById('subject_frame');
			new LoadingFrame(frame);
			frame.ready = function() {
				showSubjectActions(null);
				this.ready = null;
			};
			frame.src = "/dynamic/selection/page/exam/subject?id=-1&onready=ready";
		}

		function copySubject() {
			require("popup_window.js",function() {
				var pop = new popup_window(
					"Create Exam Subject",
					theme.build_icon("/static/selection/exam/exam_subject_16.png",theme.icons_10.add,"right_bottom"),
					""
				);
				pop.setContentFrame("/dynamic/selection/page/exam/copy_subject");
				pop.onclose = function() {
					location.reload();
				};
				pop.show();
			});
		}

		function showSubjectActions(subject) {
			document.getElementById('subject_footer').style.visibility = "visible";
			<?php if ($can_edit) {?>
			var save_button = document.getElementById('save_button');
			save_button.disabled = "disabled";
			var cancel_button = document.getElementById('cancel_button');
			cancel_button.disabled = "disabled";
			var frame = document.getElementById('subject_frame');
			var win = getIFrameWindow(frame);
			win.pnapplication.autoDisableSaveButton(save_button);
			win.pnapplication.autoDisableSaveButton(cancel_button);
			save_button.onclick = function() {
				var locker = lock_screen(null, "Saving subject...");
				win.save(function(subj) {
					unlock_screen(locker);
					if (subj == null) return; // error case
					if (subject == null) {
						// new subject
						if (subjects.length == 0) {
							var no_subject = document.getElementById('no_subject');
							no_subject.style.position = "absolute";
							no_subject.style.display = "none";
						}
						selected_index = subjects.length;
						subjects.push(subj);
						subjects_controls.push(new SubjectControl(subj));
						subject = subj;
					} else {
						// updated subject
						for (var i = 0; i < subjects.length; ++i) {
							if (subjects[i].id == subj.id) {
								subjects[i].name = subj.name;
								window.top.datamodel.cellChanged("ExamSubject", "name", subj.id, subj.name);
								subjects[i].max_score = subj.max_score;
								window.top.datamodel.cellChanged("ExamSubject", "max_score", subj.id, subj.max_score);
							}
						}
					}
				});
			};
			cancel_button.onclick = function() {
				if (subject == null) {
					// new subject, let's restart
					win.pnapplication.cancelDataUnsaved();
					document.getElementById('subject_frame').src = "/static/application/message.html?padding=5&text=<?php echo urlencode("Select a subject to display it here");?>";
					selected_index = -1;
				} else {
					win.pnapplication.cancelDataUnsaved();
					win.location.reload();
				}
			};
			<?php if (PNApplication::$instance->selection->getOneConfigAttributeValue("set_correct_answer")) { ?>
			<?php } ?>
			require("upload.js");
			var import_button = document.getElementById('import_answers_from_clickers');
			import_button.onclick = function(ev) {
				var upl = new upload("/dynamic/selection/service/exam/import_exam_answers_from_sunvote", false, false);
				var popup = null;
				upl.ondone = function(outputs, errors, warnings) {
					popup.close();
					if (outputs.length == 0 || !outputs[0] || !outputs[0].questions) return;
					win.importQuestionsInfo(outputs[0].questions);
				};
				upl.addUploadPopup("/static/selection/exam/sunvote_16.png", "Import Exam Answers From Clickers System", function(pop) { popup = pop; });
				upl.openDialog(ev, ".xls,.xlsx");
			};
			var export_button = document.getElementById('export_answers_to_clickers');
			export_button.onclick = function(ev) {
				if (win.answers.length == 1)
					postToDownload("/dynamic/selection/service/exam/export_exam_answers_to_sunvote", {subject:win.subject.id,version_index:0});
				else {
					var options = [];
					for (var i = 0; i < win.answers.length; ++i) options.push([i,String.fromCharCode("A".charCodeAt(0)+i)]);
					select_dialog(null,"Subject Version","For which version of the subject do you want to export ?",null,options,function(version_index) {
						postToDownload("/dynamic/selection/service/exam/export_exam_answers_to_sunvote", {subject:win.subject.id,version_index:version_index});
					});
				}
			};
			<?php } ?>
		}

		if (subjects.length == 0) {
			var no_subject = document.getElementById('no_subject');
			no_subject.style.position = "static";
			no_subject.style.display = "";
		} else {
			for (var i = 0; i < subjects.length; ++i)
				subjects_controls.push(new SubjectControl(subjects[i]));
		}
		</script>
		<?php 
	}
}
?>