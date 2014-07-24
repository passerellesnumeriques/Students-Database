<?php 
require_once("/../SelectionPage.inc");
class page_exam_subject extends SelectionPage {
	public function getRequiredRights() { return array("see_exam_subject"); }

	public function executeSelectionPage(){
		$id = isset($_GET["id"]) ? intval($_GET["id"]) : -1;
		$campaign_id = isset($_GET["campaign_id"]) ? intval($_GET["campaign_id"]) : null;
		$readonly = isset($_GET["readonly"]) ? $_GET["readonly"] : false;
		
		//Get rights from steps
		$from_steps = PNApplication::$instance->selection->getRestrictedRightsFromStepsAndUserManagement("manage_exam", "manage_exam_subject", "manage_exam_subject", "manage_exam_subject");
		if($from_steps[1])
			PNApplication::warning($from_steps[2]);
		$can_add = $from_steps[0]["add"];
		$can_remove = $from_steps[0]["remove"];
		$can_edit = $from_steps[0]["edit"];
		// TODO replace the step thing
		
		if (!$can_edit) $readonly = true;
	
		$display_correct_answers = PNApplication::$instance->selection->getOneConfigAttributeValue("set_correct_answer");
		$multiple_versions = PNApplication::$instance->selection->getOneConfigAttributeValue("use_subject_version");
		$current_campaign = PNApplication::$instance->selection->getCampaignId();
	
		if ($display_correct_answers) {
			if ($id > 0) {
				$versions = SQLQuery::create()->select("ExamSubjectVersion")->whereValue("ExamSubjectVersion","exam_subject",$id)->field("id")->executeSingleField();
				$answers = SQLQuery::create()->select("ExamSubjectAnswer")->whereIn("ExamSubjectAnswer","exam_subject_version", $versions)->execute();
			} else {
				$versions = array(-1);
				$answers = array();
			}
		}
		
		$db_lock = null;
		if(!$readonly && $id > 0) {
			$locked_by = null;
			$db_lock = $this->performRequiredLocks("ExamSubject",$id,null,$current_campaign, $locked_by);
			if($db_lock == null) {
				echo "<div class='warning_box'><img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> This subject is currently edited by ".htmlentities($locked_by)."</div>";
				$readonly = true;
			}
		}
		
		require_once("component/selection/SelectionExamJSON.inc");
		$this->requireJavascript("input_utils.js");
		$this->requireJavascript("exam_objects.js");
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_decimal.js");
		?>
		<div style="width:100%;height:100%;overflow:auto;text-align:center">
		<div style="display:inline-block;text-align:left;">
		<div style="background-color:white;display:flex;flex-direction:column;border: 1px solid #A0A0A0; box-shadow: 2px 2px 2px 0px #808080; border-radius: 3px; margin: 5px;padding:5px;">
			<div style="flex:none;margin:5px;">
				<div style='display:inline-block;width:120px'>
					<img src='/static/application/logo.png' style='vertical-align:top'/>
				</div>
				<div style='display:inline-block;width:450px;vertical-align:top;'>
					<div style='font-size:22pt;'>PASSERELLES NUMÉRIQUES</div>
					<div style='font-size:18pt;'>Qualifying Exam</div>
					<div id='subject_name' style='font-size:18pt'></div>
				</div>
			</div>
			<div class='subject_numbers' style="flex:none;margin:5px">
				<span id="nb_parts"></span> - 
				<span id="nb_questions"></span> - 
				<span id="max_score"></span>
			</div>
			<?php if ($multiple_versions) {?>
			<div style="margin:5px;">
				Version(s) of the subject: <span id='versions'></span>
				<?php if (!$readonly) {?>
				<button class='flat small_icon' style='vertical-align:middle' title='Add a version' onclick='newVersion();'><img src='<?php echo theme::$icons_10["add"];?>'/></button>
				<?php }?>
			</div>
			<?php }?>
			<?php if ($display_correct_answers && !$readonly) {?>
			<div style="margin:5px">
				Default number of choices: <select id='default_nb_choices'>
				<option value='2'>2</option>
				<option value='3'>3</option>
				<option value='4'>4</option>
				<option value='5' selected='selected'>5</option>
				<option value='6'>6</option>
				<option value='7'>7</option>
				<option value='8'>8</option>
				</select>
			</div>
			<?php }?>
			<table><tbody id="table"></tbody></table>
			<?php if (!$readonly) {?>
			<div style="margin:5px">
				<button class='action' onclick='newPart();'><img src='<?php echo theme::$icons_16["add"];?>'/> New part</button>
			</div>
			<?php } ?>
		</div>
		</div>
		</div>
		<style type='text/css'>
		.subject_numbers {
			font-weight: bold;
			font-size: 12pt;
		}
		tr.part {
			font-size: 12pt;
			font-weight: bold;
		}
		tr.questions_header {
			font-size: 10pt;
			font-weight: bold;
		}
		tr.question {
			font-size: 10pt;
		}
		</style>
		<script type = "text/javascript">
		var readonly = <?php echo $readonly ? "true" : "false"; ?>;
		var display_correct_answers = <?php echo $display_correct_answers ? "true" : "false";?>;
		var multiple_versions = <?php echo $multiple_versions ? "true" : "false";?>;
		var subject = <?php
		if ($id > 0)
			echo SelectionExamJSON::ExamSubjectFullJSON($id);
		else
			echo "new ExamSubject(-1, '', 0, [], [-1])";
		?>;

		var answers = [<?php
		if ($display_correct_answers) {
			$first_version = true;
			foreach ($versions as $version_id) {
				if ($first_version) $first_version = false; else echo ",";
				echo "[";
				$first_answer = true;
				foreach ($answers as $a) {
					if ($a["exam_subject_version"] <> $version_id) continue;
					if ($first_answer) $first_answer = false; else echo ",";
					echo "{q:".$a["exam_subject_question"].",a:".json_encode($a["answer"])."}";
				}
				echo "]";
			}
		} 
		?>];

		function getAnswer(version_id, question_id) {
			var version_index = subject.versions.indexOf(version_id);
			for (var i = 0; i < answers[version_index].length; ++i)
				if (answers[version_index][i].q == question_id)
					return answers[version_index][i].a;
			return null;
		}
		function setAnswer(version_id, question_id, answer) {
			var version_index = subject.versions.indexOf(version_id);
			for (var i = 0; i < answers[version_index].length; ++i)
				if (answers[version_index][i].q == question_id) {
					if (answer == answers[version_index][i].a) return;
					answers[version_index][i].a = answer;
					pnapplication.dataUnsaved('subject');
					return;
				}
			// no answer yet
			answers[version_index].push({q:question_id,a:answer});
			pnapplication.dataUnsaved('subject');
		}
		
		if (subject.id <= 0) pnapplication.dataUnsaved('subject');
		
		var subject_name = document.getElementById('subject_name');
		if (readonly)
			subject_name.appendChild(document.createTextNode(subject.name));
		else {
			var input = document.createElement("INPUT");
			input.type = "text";
			input.style.fontSize = "18pt";
			input.value = subject.name;
			inputDefaultText(input,"Exam Name");
			input.onchange = function() {
				subject.name = input.getValue();
				pnapplication.dataUnsaved('subject');
			};
			inputAutoresize(input,40);
			if (subject.id > 0)
				window.top.datamodel.inputCell(input, "ExamSubject", "name", subject.id);
			subject_name.appendChild(input);
		}

		var parts = [];
		var new_part_id_counter = -1;
		var new_question_id_counter = -1;
		var new_version_id_counter = -2;
		
		function ExamPartControl(index) {
			this.part = subject.parts[index];
			var table = document.getElementById("table");
			this.tr = document.createElement("TR");
			this.tr.className = "part";
			if (index == parts.length) {
				table.appendChild(this.tr);
				parts.push(this); 
			} else {
				table.insertBefore(this.tr, parts[index].tr);
				parts.splice(index,0,this);
			}
			var td;
			this.tr.appendChild(td = document.createElement("TD"));
			td.style.whiteSpace = "nowrap";
			td.style.paddingTop = "15px";
			td.colSpan = (readonly ? 2 : 4)+(display_correct_answers ? subject.versions.length : 0);
			this.part_number = document.createElement("SPAN");
			td.appendChild(this.part_number);
			td.appendChild(document.createTextNode(" - "));
			if (readonly) {
				this.name_input = document.createElement("SPAN");
				this.name_input.appendChild(document.createTextNode(this.part.name));
				td.appendChild(this.name_input);
			} else {
				this.name_input = document.createElement("INPUT");
				this.name_input.type = "text";
				this.name_input.value = this.part.name;
				inputDefaultText(this.name_input,"Part Name");
				this.name_input.part_control = this;
				this.name_input.onchange = function() {
					this.part_control.part.name = this.getValue();
					pnapplication.dataUnsaved('subject');
				};
				inputAutoresize(this.name_input,20);
				if (this.part.id > 0)
					window.top.datamodel.inputCell(this.name_input, "ExamSubjectPart", "name", this.part.id);
				td.appendChild(this.name_input);
			}
			td.appendChild(document.createTextNode(" - "));
			this.nb_questions = document.createElement("SPAN");
			td.appendChild(this.nb_questions);
			td.appendChild(document.createTextNode(" - "));
			this.max_score = document.createElement("SPAN");
			td.appendChild(this.max_score);
			if (!readonly) {
				var t=this;
				this.button_remove = document.createElement("BUTTON");
				this.button_remove.className = "flat icon";
				this.button_remove.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
				this.button_remove.title = "Remove this part and all its questions";
				this.button_remove.onclick = function() {
					confirm_dialog("Are you sure you want to remove this part and all its questions ?", function(yes) {
						if (!yes) return;
						t.remove();
					});
				};
				td.appendChild(this.button_remove);
				this.button_up = document.createElement("BUTTON");
				this.button_up.className = "flat icon";
				this.button_up.innerHTML = "<img src='"+theme.icons_16.up+"'/>";
				this.button_up.title = "Move this part before";
				this.button_up.onclick = function() {
					t.moveUp();
				};
				td.appendChild(this.button_up);
				this.button_down = document.createElement("BUTTON");
				this.button_down.className = "flat icon";
				this.button_down.innerHTML = "<img src='"+theme.icons_16.down+"'/>";
				this.button_down.title = "Move this part after";
				this.button_down.onclick = function() {
					t.moveDown();
				};
				td.appendChild(this.button_down);
			}
			this.tr_title = document.createElement("TR");
			this.tr_title.className = "questions_header";
			var th;
			if (!readonly)
				this.tr_title.appendChild(th = document.createElement("TH"));
			this.tr_title.appendChild(th = document.createElement("TH"));
			th.innerHTML = "Question";
			th.style.textAlign = "right";
			th.style.verticalAlign = "bottom";
			this.tr_title.appendChild(th = document.createElement("TH"));
			th.innerHTML = "Points";
			th.style.textAlign = "center";
			th.style.verticalAlign = "bottom";
			if (display_correct_answers) {
				if (!multiple_versions) {
					this.tr_title.appendChild(th = document.createElement("TH"));
					th.innerHTML = "Answer";
					th.style.textAlign = "center";
				} else {
					for (var i = 0; i < subject.versions.length; ++i) {
						var th = document.createElement("TH");
						th.style.textAlign = "center";
						this.tr_title.appendChild(th);
					}
				}
			}
			if (!readonly)
				this.tr_title.appendChild(th = document.createElement("TH"));
			table.insertBefore(this.tr_title, this.tr.nextSibling);
			
			this.questions = [];
			for (var i = 0; i < this.part.questions.length; ++i)
				new QuestionControl(this, i);

			this.remove = function() {
				while (this.questions.length > 0)
					this.questions[0].remove();
				table.removeChild(this.tr_title);
				table.removeChild(this.tr);
				subject.parts.splice(parts.indexOf(this),1);
				parts.remove(this);
				pnapplication.dataUnsaved('subject');
				update();
			};
			this.moveDown = function() {
				var index = parts.indexOf(this);
				var next = index < parts.length-2 ? parts[index+2].tr : null;
				table.insertBefore(this.tr, next);
				table.insertBefore(this.tr_title, next);
				for (var i = 0; i < this.questions.length; ++i)
					table.insertBefore(this.questions[i].tr, next);
				parts.splice(index,1);
				parts.splice(index+1,0,this);
				subject.parts.splice(index,1);
				subject.parts.splice(index+1,0,this.part);
				pnapplication.dataUnsaved('subject');
				update();
			};
			this.moveUp = function() {
				var index = parts.indexOf(this);
				var next = parts[index-1].tr;
				table.insertBefore(this.tr, next);
				table.insertBefore(this.tr_title, next);
				for (var i = 0; i < this.questions.length; ++i)
					table.insertBefore(this.questions[i].tr, next);
				parts.splice(index,1);
				parts.splice(index-1,0,this);
				subject.parts.splice(index,1);
				subject.parts.splice(index-1,0,this.part);
				pnapplication.dataUnsaved('subject');
				update();
			};
		}

		function QuestionControl(part, index) {
			this.question = part.part.questions[index];
			var table = document.getElementById("table");
			this.tr = document.createElement("TR");
			this.tr.className = "question";
			if (index == part.questions.length) {
				table.insertBefore(this.tr, part.questions.length == 0 ? part.tr_title.nextSibling : part.questions[part.questions.length-1].tr.nextSibling);
				part.questions.push(this); 
			} else {
				table.insertBefore(this.tr, part.questions[index].tr);
				part.questions.splice(index,0,this);
			}
			var td;
			var t=this;
			if (!readonly) {
				td  = document.createElement("TD");
				td.style.paddingLeft = "25px";
				td.style.textAlign = "right";
				var insert_before = document.createElement("BUTTON");
				insert_before.className = "flat small_icon";
				insert_before.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
				insert_before.title = "Insert a question before this one";
				insert_before.onclick = function() {
					var index = part.questions.indexOf(t);
					var q = new ExamSubjectQuestion(new_question_id_counter--, index, 1, "mcq_single", document.getElementById('default_nb_choices').value);
					part.part.questions.splice(index,0,q);
					new QuestionControl(part, index);
					pnapplication.dataUnsaved('subject');
					update();
				};
				td.appendChild(insert_before);
				this.tr.appendChild(td);
			}
			this.question_num = document.createElement("TD");
			this.question_num.style.textAlign = "right";
			this.question_num.style.whiteSpace = "nowrap";
			this.tr.appendChild(this.question_num);
			td = document.createElement("TD");
			td.style.textAlign = "center";
			td.style.whiteSpace = "nowrap";
			this.tr.appendChild(td);
			if (readonly) {
				td.innerHTML = this.question.max_score.toFixed(2)+(this.question.max_score > 1 ? "pts" : "pt");
			} else {
				this.field_score = new field_decimal(this.question.max_score,true,{integer_digits:3,decimal_digits:2,min:0,max:100,can_be_null:false});
				td.appendChild(this.field_score.getHTMLElement());
				td.appendChild(document.createTextNode(" point(s)"));
				this.field_score.onchange.add_listener(function(f) {
					var pts = parseFloat(f.getCurrentData());
					if (isNaN(pts)) pts = 0;
					t.question.max_score = pts;
					pnapplication.dataUnsaved('subject');
					update();
				});
			}
			if (!readonly) {
				td = document.createElement("TD");
				td.style.whiteSpace = "nowrap";
				this.button_up = document.createElement("BUTTON");
				this.button_up.className = "flat small_icon";
				this.button_up.innerHTML = "<img src='"+theme.icons_10.up+"'/>";
				this.button_up.title = "Move this question before";
				this.button_up.onclick = function() {
					var index = part.questions.indexOf(t);
					part.part.questions.splice(index,1);
					part.part.questions.splice(index-1,0,t.question);
					part.questions.splice(index,1);
					part.questions.splice(index-1,0,t);
					table.insertBefore(t.tr, t.tr.previousSibling);
					pnapplication.dataUnsaved('subject');
					update();
				};
				this.button_up.style.marginRight = "3px";
				td.appendChild(this.button_up);
				this.button_down = document.createElement("BUTTON");
				this.button_down.className = "flat small_icon";
				this.button_down.innerHTML = "<img src='"+theme.icons_10.down+"'/>";
				this.button_down.title = "Move this question after";
				this.button_down.onclick = function() {
					var index = part.questions.indexOf(t);
					part.part.questions.splice(index,1);
					part.part.questions.splice(index+1,0,t.question);
					part.questions.splice(index,1);
					part.questions.splice(index+1,0,t);
					table.insertBefore(t.tr, t.tr.nextSibling.nextSibling);
					pnapplication.dataUnsaved('subject');
					update();
				};
				this.button_down.style.marginRight = "3px";
				td.appendChild(this.button_down);
				var button_remove = document.createElement("BUTTON");
				button_remove.className = "flat small_icon";
				button_remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
				button_remove.title = "Remove this question";
				button_remove.onclick = function() {
					if (part.questions.length > 1)
						t.remove();
					else
						confirm_dialog("This is the last question of this part. If you remove it, the part will be removed as well. Do you want to do this ?", function(yes) {
							if (!yes) return;
							part.remove();
						}); 
				};
				button_remove.style.marginRight = "3px";
				td.appendChild(button_remove);
				var insert_after = document.createElement("BUTTON");
				insert_after.className = "flat small_icon";
				insert_after.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
				insert_after.title = "Add a question after this one";
				insert_after.onclick = function() {
					var index = part.questions.indexOf(t)+1;
					var q = new ExamSubjectQuestion(new_question_id_counter--, index, 1, "mcq_single", document.getElementById('default_nb_choices').value);
					part.part.questions.splice(index,0,q);
					new QuestionControl(part, index);
					pnapplication.dataUnsaved('subject');
					update();
				};
				insert_after.style.marginRight = "3px";
				td.appendChild(insert_after);
				this.tr.appendChild(td);
			}
			this.answers = [];
			if (display_correct_answers)
				for (var i = 0; i < subject.versions.length; ++i)
					this.answers.push(new AnswerControl(this, i));

			this.remove = function() {
				for (var i = 0; i < answers.length; ++i)
					for (var j = 0; j < answers[i].length; ++j)
						if (answers[i][j].q == t.question.id) {
							answers[i].splice(j,1);
							break;
						}
				table.removeChild(this.tr);
				part.part.questions.splice(part.questions.indexOf(this),1);
				part.questions.remove(this);
				pnapplication.dataUnsaved('subject');
				update();
			};
		}

		function AnswerControl(question, version_index) {
			this.version_id = subject.versions[version_index];
			this.td = document.createElement("TD");
			this.td.style.whiteSpace = "nowrap";
			this.td.style.paddingRight = "5px";
			if (readonly)
				question.tr.appendChild(this.td);
			else
				question.tr.insertBefore(this.td, question.tr.childNodes[question.tr.childNodes.length-1]);
			var answer = getAnswer(this.version_id, question.question.id);
			switch (question.question.type) {
			case "mcq_single":
				var nb_answers = parseInt(question.question.type_config);
				var id = generateID();
				for (var i = 0; i < nb_answers; ++i) {
					var cb = document.createElement("INPUT");
					cb.type = "radio";
					cb.name = id;
					cb.value = String.fromCharCode("A".charCodeAt(0)+i);
					if (readonly) cb.disabled = "disabled";
					this.td.appendChild(cb);
					this.td.appendChild(document.createTextNode(String.fromCharCode("A".charCodeAt(0)+i)));
					if (cb.value == answer) cb.checked = "checked";
					var t=this;
					cb.onchange = function() {
						setAnswer(t.version_id, question.question.id, this.value);
					};
				}
				this.addControls = function() {
					this.plus = document.createElement("BUTTON");
					this.plus.className = "flat small_icon";
					this.plus.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
					this.plus.title = "Add an answer";
					this.plus.onclick = function() {
						if (nb_answers >= 8) { alert("You cannot have more than 8 answers!"); return; }
						nb_answers++;
						question.question.type_config = nb_answers;
						pnapplication.dataUnsaved('subject');
						while (question.answers.length > 0)
							question.answers[0].remove();
						for (var i = 0; i < subject.versions.length; ++i)
							question.answers.push(new AnswerControl(question,i));
					};
					this.td.appendChild(this.plus);
					this.minus = document.createElement("BUTTON");
					this.minus.className = "flat small_icon";
					this.minus.innerHTML = "<img src='"+theme.icons_10.minus+"'/>";
					this.minus.title = "Remove an answer";
					this.minus.onclick = function() {
						if (nb_answers < 3) { alert("You cannot have less than 2 answers!"); return; }
						nb_answers--;
						question.question.type_config = nb_answers;
						pnapplication.dataUnsaved('subject');
						while (question.answers.length > 0)
							question.answers[0].remove();
						for (var i = 0; i < subject.versions.length; ++i)
							question.answers.push(new AnswerControl(question,i));
					};
					this.td.appendChild(this.minus);
				};
				this.removeControls = function() {
					this.td.removeChild(this.plus);
					this.plus = null;
					this.td.removeChild(this.minus);
					this.minus = null;
				};
				break;
			}

			this.remove = function() {
				this.td.parentNode.removeChild(this.td);
				var i = question.answers.indexOf(this);
				question.answers.splice(i,1);
				if (i == 0 && question.answers.length > 0)
					question.answers[0].addControls();
			};

			if (version_index == 0 && !readonly) {
				if (question.answers.length > 0)
					question.answers[0].removeControls();
				this.addControls();
			}
		}
		
		function buildTable() {
			for (var i = 0; i < subject.parts.length; ++i)
				new ExamPartControl(i);
		}
		
		// Update total numbers, questions index...
		function update() {
			// versions
			if (multiple_versions) {
				var span = document.getElementById('versions');
				span.removeAllChildren();
				for (var i = 0; i < subject.versions.length; ++i) {
					if (i > 0) span.appendChild(document.createTextNode(","));
					span.appendChild(document.createTextNode(String.fromCharCode("A".charCodeAt(0)+i)));
					if (subject.versions.length > 1 && !readonly) {
						var button = document.createElement("BUTTON");
						button.className = "flat small_icon";
						button.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
						button.title = "Remove version "+String.fromCharCode("A".charCodeAt(0)+i);
						button.version_index = i;
						button.onclick = function() {
							removeVersion(this.version_index);
						};
						span.appendChild(button);
					}
				}
			}
			// total number of parts
			document.getElementById('nb_parts').innerHTML = (subject.parts.length)+" part"+(subject.parts.length > 1 ? "s" : "");
			var total_score = 0;
			var q_total = 0;
			for (var part_i = 0; part_i < subject.parts.length; ++part_i) {
				// part index
				subject.parts[part_i].index = part_i+1;
				parts[part_i].part_number.innerHTML = "Part "+(part_i+1);
				parts[part_i].nb_questions.innerHTML = subject.parts[part_i].questions.length+" question"+(subject.parts[part_i].questions.length > 1 ? "s" : "");
				// versions titles
				if (display_correct_answers && multiple_versions) {
					for (var i = 0; i < subject.versions.length; ++i)
						parts[part_i].tr_title.childNodes[i+(readonly ? 2 : 3)].innerHTML = "Version "+String.fromCharCode("A".charCodeAt(0)+i);
				}
				// score
				var part_score = 0;
				for (var q = 0; q < subject.parts[part_i].questions.length; ++q) {
					q_total++;
					part_score += subject.parts[part_i].questions[q].max_score;
					total_score += subject.parts[part_i].questions[q].max_score;
					subject.parts[part_i].questions[q].index = q+1;
					parts[part_i].questions[q].question_num.innerHTML = "Question "+q_total;
					if (!readonly) {
						if (parts[part_i].questions[q].field_score)
							parts[part_i].questions[q].field_score.input.tabIndex = q_total;
						if (q > 0) {
							parts[part_i].questions[q].button_up.style.visibility = "visible";
							parts[part_i].questions[q].button_up.style.disabled = "";
						} else {
							parts[part_i].questions[q].button_up.style.visibility = "hidden";
							parts[part_i].questions[q].button_up.style.disabled = "disabled";
						}
						if (q < subject.parts[part_i].questions.length-1) {
							parts[part_i].questions[q].button_down.style.visibility = "visible";
							parts[part_i].questions[q].button_down.style.disabled = "";
						} else {
							parts[part_i].questions[q].button_down.style.visibility = "hidden";
							parts[part_i].questions[q].button_down.style.disabled = "disabled";
						}
					}
				}
				subject.parts[part_i].max_score = part_score;
				parts[part_i].max_score.innerHTML = part_score.toFixed(2)+" point"+(part_score > 1 ? "s" :"");
				if (!readonly) {
					if (part_i == 0) {
						parts[part_i].button_up.style.visibility = "hidden";
						parts[part_i].button_up.style.disabled = "disabled";
					} else {
						parts[part_i].button_up.style.visibility = "visible";
						parts[part_i].button_up.style.disabled = "";
					}
					if (part_i == subject.parts.length-1) {
						parts[part_i].button_down.style.visibility = "hidden";
						parts[part_i].button_down.style.disabled = "disabled";
					} else {
						parts[part_i].button_down.style.visibility = "visible";
						parts[part_i].button_down.style.disabled = "";
					}
				}
			}
			// total numbers
			document.getElementById('nb_questions').innerHTML = q_total+" question"+(q_total > 1 ? "s" : "");
			document.getElementById('max_score').innerHTML = total_score.toFixed(2)+" point"+(total_score > 1 ? "s" : "");
			subject.max_score = total_score;
		}

		function newPart() {
			var part = new ExamSubjectPart(new_part_id_counter--, subject.parts.length+1, "", 1, [
			    new ExamSubjectQuestion(new_question_id_counter--, 1, 1, "mcq_single", display_correct_answers ? document.getElementById('default_nb_choices').value : "4")
			]);
			subject.parts.push(part);
			var p = new ExamPartControl(subject.parts.length-1);
			update();
			pnapplication.dataUnsaved('subject');
			p.name_input.focus();
		}

		function newVersion() {
			pnapplication.dataUnsaved('subject');
			subject.versions.push(new_version_id_counter--);
			answers.push([]);
			// add column in part titles and questions
			if (display_correct_answers) {
				for (var i = 0; i < parts.length; ++i) {
					parts[i].tr.childNodes[0].colSpan = 4+subject.versions.length;
					var th = document.createElement("TH");
					th.style.whiteSpace = "nowrap";
					th.style.textAlign = "center";
					th.style.verticalAlign = "bottom";
					parts[i].tr_title.insertBefore(th,parts[i].tr_title.childNodes[parts[i].tr_title.childNodes.length-1]);
					for (var j = 0; j < parts[i].questions.length; ++j)
						parts[i].questions[j].answers.push(new AnswerControl(parts[i].questions[j], subject.versions.length-1));
				}
			}
			// update
			update();
		}

		function removeVersion(version_index) {
			pnapplication.dataUnsaved('subject');
			subject.versions.splice(version_index,1);
			answers.splice(version_index,1);
			if (display_correct_answers) {
				for (var i = 0; i < parts.length; ++i) {
					parts[i].tr.childNodes[0].colSpan = 4+subject.versions.length;
					parts[i].tr_title.removeChild(parts[i].tr_title.childNodes[3+version_index]);
					for (var j = 0; j < parts[i].questions.length; ++j)
						parts[i].questions[j].answers[version_index].remove();
				}
			}
			update();
		}

		buildTable();
		update();

		function save(onsaved) {
			if (subject.name.trim().length == 0) {
				alert("Please enter a name for this subject");
				onsaved(null);
				return;
			}
			for (var i = 0; i < subject.parts.length; ++i)
				if (subject.parts[i].name.trim().length == 0) {
					alert("Please enter a name for the part number "+(i+1));
					onsaved(null);
					return;
				}
			service.json("selection","exam/save_subject",{exam:subject,answers:answers},function(res) {
				if (!res || !res.exam) { onsaved(null); return; }
				subject = res.exam;
				answers = res.answers;
				parts = [];
				pnapplication.dataSaved('subject');
				var table = document.getElementById("table");
				table.removeAllChildren();
				buildTable();
				update();
				onsaved(subject);
			});
		}

		<?php
		if (isset($_GET["onready"]))
			echo "window.frameElement.".$_GET["onready"]."();"; 
		?>

		window.onuserinactive = function() {
			if (subject.id > 0) {
				var u = new URL(location.href);
				u.params.readonly = "true";
				location.href = u.toString();
			}
		};
		</script>
		<?php
	}
}

