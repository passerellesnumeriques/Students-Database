<?php
require_once ("component/selection/SelectionJSON.inc");
require_once("/../selection_page.inc");
class page_exam_import_subject extends selection_page {
	
	public function get_required_rights() {return array("manage_exam_subject");}
	
	public function execute_selection_page(&$page) {
		/* Check the rights */
		$can_update = PNApplication::$instance->selection->canManageExamSubjectQuestions();
		if(!$can_update[0]){
			echo "<div style ='font-color:red;'>".$can_update[1]."</div>";
		} else {
			$all_names = SelectionJSON::getAllExamSubjectNames();
			$correct_answer = PNApplication::$instance->selection->getOneConfigAttributeValue("set_correct_answer");
			$choices = PNApplication::$instance->selection->getOneConfigAttributeValue("set_number_choices");
			
			/** Lock the exam table because this page will perform some checks on the
			 * exam names, so must be sure that no one is updating it;
			 */
			// require_once("component/data_model/DataBaseLock.inc");
			// $campaign_id = PNApplication::$instance->selection->getCampaignId();
			// require_once("component/data_model/Model.inc");
			// $table = DataModel::get()->getTable("Exam_subject");
			// $locked_by = null;
			// $lock_id = null;
			// $lock_id = DataBaseLock::lockTable($table->getSQLNameFor($campaign_id), $locked_by);
			// if($lock_id == null & $locked_by <> null){
				// PNApplication::error($locked_by." is already managing the exams subjects for this campaign");
				// return;
			// } else {
				// DataBaseLock::generateScript($lock_id);
			// }
			//TODO: lock table?
			require_once("component/data_import/page/import_data.inc");
			$data_list = array();
			$questions = DataModel::get()->getTable("Exam_subject_question");
			$display = $questions->getDisplayHandler(null);
			$data_list = array_merge($data_list, $display->getDisplayableData());	
			$fixed_data = array();
			$prefilled_data = array();

			// import_data($page, $icon, $title, $data_list, $fixed_data, $prefilled_data, $create_button, $create_function);
			import_data(
				$page,
				"/static/application/icon.php?main=/static/selection/exam/exam_32.png&small=".theme::$icons_16["add"]."&where=right_bottom",
				"Import Exam Subject",
				$data_list,
				$fixed_data,
				$prefilled_data,
				"<img src='".theme::make_icon("/static/selection/exam/exam_16.png",theme::$icons_10["add"])."'/> Import Exam",
				"finishImport"
			);
			?>
			<script type='text/javascript'>						
			
			function import_exam_subject(){
				var t = this;
				t.step_1 = null; //contains the instance of the import_exam_subject_part_1 object
				t.step_2 = null; //contains the instance of the import_exam_subject_part_2 object

				/**
				 * Get the user input from the first step
				 * @returns {Object} infos with two attributes: <ul><li>{String} name</li><li>{Array} questions_by_part</li></ul>
				 */
				t.getExamInfos = function(){
					var infos = {};
					infos.name = t.step_1.res.name;
					infos.questions_by_part = t.step_2.res;
					return infos;
				};

				/**
				 * Method called when the user presses Ok button during the first step
				 * Close the step 1 popup and create the step 2 instance
				 */
				t._onOkPressStep1 = function(){
					t.step_1.pop.close();
					t.step_2 = new import_exam_subject_part_2(t.step_1.res.name,t.step_1.res.parts);
				};

				/**
				 * Method called when the user wants to step back to first popup
				 * Close and delete the step_2 popup and open the first one (with the same values)
				 */
				t._onBackToStep1 = function(){
					t.step_2.pop.close();
					delete t.step_2;
					t.step_2 = null;
					t.step_1.pop.show();
				};

				/**
				 * Method called at the end of the step 2 when user clicks on Ok button
				 */
				t._okPressStep2 = function(){
					t.step_2.pop.close();
					//TODO: add button "back"?
				};

				/**
				 * Create and manage the step 2 popup, based on the step 1 data
				 * @param {String} exam_name
				 * @param {array} parts_number
				 * The parameters come from the import_exam_subject_part_1#res attributes
				 */
				function import_exam_subject_part_2(exam_name, parts_number){
					var t2 = this;
					t2.table = null;
					t2.res = [];
					t2.inputs = [];
					t2.tr_error = null;

					/**
					 * Initiate the process and create the popup_window object
					 * If the step 1 instance still exists, it is deleted
					 */
					t2._init = function(){
						if(t.step_1 != null){
							delete step_1;
							step_1 = null;
						}
						t2.table = document.createElement("table");						
						t2.pop = new popup_window(
							"Import an exam subject - 2/3",//build_icon: function(main,small,where)
							theme.build_icon("/static/selection/exam/exam_16.png",theme.icons_10.add,"right_bottom"),
							t2.table,
							true
						);
						t2.pop.addButton("<img src = '"+theme.icons_16.left+"'style='vertical-align:bottom'/> Back", "back",t._onBackToStep1);
						t2.pop.addButton("<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> Ok", 'ok', t._okPressStep2);
						t2._setContent();
						t2.pop.show();
					};

					/**
					 * Method called on each user input
					 * If any input error is detected, the last popup row is updated with a red error message
					 * and the ok button is disabled
					 * If no error, message is deleted and Ok button is enabled
					 */
					t2._updateErrorRow = function(){
						if(!t2._hasError()){
							if(t2.tr_error != null){
								t2.table.removeChild(t2.tr_error);
								delete t2.tr_error;
								t2.tr_error = null;
							}
							t2.pop.enableButton("ok");
							//update res
							for(var i = 0; i < t2.inputs.length; i++)
								t2.res[i] = t2.inputs[i].value;
							
						} else {
							if(t2.tr_error == null){
								t2.tr_error = document.createElement("tr");
								td_error = document.createElement("td");
								td_error.colSpan = 2;
								td_error.innerHTML = "<img src = '"+theme.icons_16.error+"'/> You cannot have any empty part";
								td_error.style.color = "red";
								t2.tr_error.appendChild(td_error);
								t2.table.appendChild(t2.tr_error);
								t2.pop.disableButton("ok");
							}
							//else nothing to do
						}
					};

					/**
					 * Check that there is no error into the inputs
					 * @returns {Boolean} true if any error is detected
					 */
					t2._hasError = function(){
						for(var i = 0; i < t2.inputs.length; i++){
							if(t2.inputs[i].value == null || t2.inputs[i].value == 0)
							return true;
						}
						return false;
					};

					/**
					 * Create the table displayed in the popup
					 * One input is created for each part declared at first step
					 */
					t2._setContent = function(){
						var th = document.createElement("th");
						th.innerHTML = "Number of question for each part";
						th.colSpan = 2;
						t2.table.appendChild((document.createElement("tr")).appendChild(th));
						for(var i = 1; i <= parts_number; i++){
							var td_title = document.createElement("td");
							var td_input = document.createElement("td");
							t2.inputs[i-1] = document.createElement("input");
							t2.inputs[i-1].type = "text";
							autoresize_input(t2.inputs[i-1],5);
							t2.inputs[i-1].oninput = function(){								
									if(this.value == null || this.value == "")
										this.value = null;
									else if(isNaN(this.value))
										this.value = null;
									else
										this.value = parseInt(this.value);
									t2._updateErrorRow();								
							};
							t2.inputs[i-1].onkeypress = function(e) {
								var ev = getCompatibleKeyEvent(e);
								if (ev.isEnter && !t2.pop.getIsDisabled("ok"))
									t2.pop.pressButton('ok');
							};
							td_title.innerHTML = "Part "+i;
							td_input.appendChild(t2.inputs[i-1]);
							var tr = document.createElement("tr");
							tr.appendChild(td_title);
							tr.appendChild(td_input);
							t2.table.appendChild(tr);
						}
						t2._updateErrorRow();
					};									
					
					t2._init();
				}					

				/**
				 * Create and manage the step 2 popup
				 */
				function import_exam_subject_part_1(){
					var t1 = this;
					/**
					 * res attribute contains the user inputs
					 */
					t1.res = {};
					t1.res.name = null;
					t1.res.parts = null;
					t1.errors = {};
					t1.errors.name = "Name is empty";
					t1.errors.parts = "Parts number is empty";
					
					t1.all_names = <?php echo "[";
						$first = true;
						foreach($all_names as $name){
							if(!$first)
								echo ", ";
							$first = false;
							echo json_encode($name);
						}
						echo "]";
					?>

					/**
					 * Create the popup_window and add the buttons
					 */
					t1._init = function(){
						t1.table = document.createElement("table");
						t1._setContent();
						t1.pop = new popup_window(
							"Import an exam subject - 1/3",
							theme.build_icon("/static/selection/exam/exam_16.png",theme.icons_10.add,"right_bottom"),
							t1.table,
							true
						);
						t1.pop.addOkCancelButtons(t._onOkPressStep1,t1._onCancel);
						t1._updateErrorStatus(); //initiate the popup with an error message (empty inputs)
						t1.pop.show();
					};

					/**
					 * Check if the given name is free or not
					 * @param {String} text the new given name
					 * @returns {Boolean} free true if the new name is free
					 */
					t1._freeName = function(text){
						var free = true;
						for(var i = 0; i < t1.all_names.length; i++){
							if(t1.all_names[i].uniformFirstLetterCapitalized() == text.uniformFirstLetterCapitalized()){
								free = false;
								break;
							}
						}
						return free;
					};

					/**
					 * Method called when the user clicks on cancel button
					 * Set the location to the exam main page
					 */
					t1._onCancel = function(){
						t1.pop.pressButton("ok");
						location.assign("/dynamic/selection/page/exam/main_page");
					};

					/**
					 * Set the content of the popup window
					 * Two inputs are generated and the error list is put at the bottom
					 */
					t1._setContent = function(){
						var th_name = document.createElement("th");
						th_name.innerHTML = "Enter the name of the new exam";
						var tr_name_1 = document.createElement("tr");
						tr_name_1.appendChild(th_name);
						tr_name_2 = document.createElement("tr");
						var td_name = document.createElement("td");
						td_name.style.textAlign = "center";
						var input_name = document.createElement("input");
						input_name.type = "text";
						new autoresize_input(input_name, 5);
						input_name.oninput = function(){
							t1.res.name = null;
							if(!this.value.checkVisible())
								t1.errors.name = "Name is empty";
							else if(!t1._freeName(this.value))
								t1.errors.name = this.value.uniformFirstLetterCapitalized()+" already exists in the database";
							else {
								t1.errors.name = null;
								t1.res.name = this.value.uniformFirstLetterCapitalized();
							}
							t1._updateErrorStatus();
						};
						input_name.onkeypress = function(e) {
							var ev = getCompatibleKeyEvent(e);
							if (ev.isEnter && !t1.pop.getIsDisabled("ok"))
								t1.pop.pressButton('ok');
						};
						td_name.appendChild(input_name);
						tr_name_2.appendChild(td_name);
						t1.table.appendChild(tr_name_1);
						t1.table.appendChild(tr_name_2);
						
						var tr_parts_1 = document.createElement("tr");
						var tr_parts_2 = document.createElement("tr");
						var th_parts = document.createElement("th");
						th_parts.innerHTML = "Enter the number of parts";
						tr_parts_1.appendChild(th_parts);
						var td_parts = document.createElement("td");
						td_parts.style.textAlign = "center";
						var input_parts = document.createElement("input");
						input_parts.type = "text";
						new autoresize_input(input_parts,5);
						input_parts.oninput = function(){
							t1.res.parts = null;
							if(!this.value.checkVisible())
								t1.errors.parts = "Parts number is empty";
							else if(isNaN(this.value))
								t1.errors.parts = "Parts number is not a number";
							else if(parseInt(this.value) == 0)
								t1.errors.parts = "Your exam must have at least one part";
							else {
								t1.errors.parts = null;
								t1.res.parts = parseInt(this.value);
							}
							t1._updateErrorStatus();
						};
						input_parts.onkeypress = function(e) {
							var ev = getCompatibleKeyEvent(e);
							if (ev.isEnter && !t1.pop.getIsDisabled("ok"))
								t1.pop.pressButton('ok');
						};
						td_parts.appendChild(input_parts);
						tr_parts_2.appendChild(td_parts);
						t1.table.appendChild(tr_parts_1);
						t1.table.appendChild(tr_parts_2);
						t1.tr_errors = null;
					};

					/**
					 * Remove the tr errors from the table and delete it
					 */
					t1._resetTrErrors = function(){
						if(t1.tr_errors != null){
							t1.table.removeChild(t1.tr_errors);
							delete t1.tr_errors;
							t1.tr_errors = null;
						}
					};

					/**
					 * Method called on each user input
					 * If an error is detected, the error message displayed is updated and ok button is disabled
					 * else it is removed and ok button is enabled
					 */
					t1._updateErrorStatus = function(){
						if(t1.errors.name == null && t1.errors.parts == null){								
							t1._resetTrErrors();								
							t1.pop.enableButton("ok");
						}
						else {
							t1._resetTrErrors();
							t1.tr_errors = document.createElement("tr");
							var td_errors = document.createElement("td");
							var ul = document.createElement("ul");
							for(i in t1.errors){
								if(t1.errors[i] != null){
									var li = document.createElement("li");
									li.innerHTML = t1.errors[i];
									li.style.color = "red";
									ul.appendChild(li);
								}
							}
							td_errors.appendChild(ul);
							t1.tr_errors.appendChild(td_errors);
							t1.table.appendChild(t1.tr_errors);
							t1.pop.disableButton("ok");
						}
					};

					/**
					 * 
					 */
					t1._init();
					
					
				}
				require(["autoresize_input.js","popup_window.js"],function(){
						t.step_1 = new import_exam_subject_part_1();
				});				
			}
			var start_import = new import_exam_subject();

			/**
			 * Method when user clicks on import exam button on step 3
			 * This method performs the last checks in the imported data
			 * @param {Array} questions given by the import_data method
			 * @param {String} locker the id of the lock_screen set by import_data method
			 */
			function finishImport(questions,locker){				
				var t = this;
				t.exam_infos = start_import.getExamInfos();
				// alert(service.generateInput(t.exam_infos));
				t.errors = {};
				t.errors.number_question = null;

				/**
				 * Retrieve the willing data from the questions array
				 * @param {String} category
				 * @param {String} data
				 * @param {Object} question, a row of questions object
				 */
				t._getData = function(category, data, question) {
					for (var i = 0; i < question.length; ++i)
						if (question[i].data.category == category && question[i].data.name == data)
							return question[i].value;
				};

				/**
				 * Start the process
				 */
				t._init = function(){
					//check the number of question imported matches
					var total_questions = 0;
					for(var i = 0; i < t.exam_infos.questions_by_part.length; i++)
						total_questions = total_questions + parseInt(t.exam_infos.questions_by_part[i]);
					if(total_questions != questions.length)
						t.errors.number_question = "The number of question imported ("+questions.length+") does not match with the one expected ("+total_questions+")";
					//check the max_score parameters are numbers > 0
					var err_msg = "";
					for(var i = 0; i < questions.length; i++){
						if(!t._checkMaxScore(t._getData("Exam Subject Question","Max Score",questions[i]))){
							if(err_msg == "")
								err_msg += "The following questions haven't got a <b>number > 0</b> as a score:<br/><ul>";
							var displayable_index = i+1;
							err_msg += "<li>Question "+displayable_index+"</li>";
						}							
					}
					if(err_msg != ""){
						err_msg += "</ul>";
						t.errors.max_score = err_msg;						
					}
					
					if(t.errors.max_score != null || t.errors.number_question != null){
						unlock_screen(locker);
						if(t.errors.number_question != null)
							error_dialog(t.errors.number_question);
						if(t.errors.max_score != null)
							error_dialog(t.errors.max_score);
					} else
						t._end();
				};

				/**
				 * Finish the import: create an exam_subject object and call the service exam/save_subject
				 * If the save exam was properly performed, location is updated to exam/subject page
				 */
				t._end = function(){
					//create an exam object
					var new_parts = [];
					var subject_max_score = 0;
// 					subject.id = -1;
// 					subject.name = t.exam_infos.name;
// 					subject.max_score = 0; //TODO update
// 					subject.parts = [];
					var index = 0;
					for(var i = 0; i < t.exam_infos.questions_by_part.length; i++){
						var part_questions = [];
						part_max_score = 0;
						for(var j = 1; j <= t.exam_infos.questions_by_part[i]; j++){
// 							var q = {};
// 							q.id = -1;
// 							q.index = j;//index in the part
// 							q.max_score = t._getData("Exam Subject Question","Max Score",questions[index]);
// 							q.correct_answer = t._getData("Exam Subject Question","Correct Answer",questions[index]);
// 							q.choices = t._getData("Exam Subject Question","Choices",questions[index]);
							var q = new ExamSubjectQuestion(
									-1,
									j,
									t._getData("Exam Subject Question","Correct Answer",questions[index]),
									t._getData("Exam Subject Question","Choices",questions[index])
									);
							index++;
							part_max_score = part_max_score + parseFloat(q.max_score);
							subject_max_score = subject_max_score + parseFloat(q.max_score);
							part_questions.push(q);
						}
// 						var part = {};
// 						part.id = -1;
// 						part.index = i+1;
// 						part.name = "";
// 						part.max_score = part_max_score;
// 						part.questions = subject_questions;
						var part = new ExamSubjectPart(
									-1,
									i+1,
									"",
									part_max_score,
									part_questions
								);
// 						subject.parts.push(part);
						new_parts.push(part);
					}
					var subject = new ExamSubject(-1,t.exam_infos.name,subject_max_score,new_parts);
					//save					
					service.json("selection","exam/save_subject",{exam:subject},function(res){
						unlock_screen(locker);
						if(!res)
							error_dialog("An error occured, importation failed");
						else
							location.assign("/dynamic/selection/page/exam/exam_subject?id="+res.id);
					});
				};

				/**
				 * Check that the max_score inputs are valid
				 * @param {String | Number} score
				 * @returns {Boolean} true if the score format is valid 
				 */
				t._checkMaxScore = function(score){
					if(score == null || score == "")
						return false;
					else if(isNaN(score))
						return false;
					else
						return true;
				};
				//TODO check the other parameters (correct answer, choices)?
				
				require("exam_objects.js",function(){
					t._init();
				});
				
			}
				
			</script>
			<?php
		}
	}
	
}

?>