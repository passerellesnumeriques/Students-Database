<?php
/**
 * Create a menu coumponed of three parts:
 * - create a subject from scratch
 * - create a subject by importing an excel file
 * - create a subject from previous campaigns
 */
require_once("/../SelectionPage.inc");
class page_exam_create_subject extends SelectionPage {
	
	public function getRequiredRights() {}
	
	public function executeSelectionPage() {
		/* Check the rights */
		if(!PNApplication::$instance->user_management->has_right("manage_exam_subject",true))
			echo "<div style='font-color:red;'>You are not allowed to add any exam subject</div>'";
		else {
			echo "<div id = 'create_subject' style='background-color:white'></div>";
			$other_campaigns = false;
			$all_campaigns = PNApplication::$instance->selection->getCampaigns();
			/* remove the current campaign */
			$index = null;
			$current_campaign = PNApplication::$instance->selection->getCampaignId();
			for($i=0; $i < count($all_campaigns); $i++){
				if($all_campaigns[$i]["id"] == $current_campaign){
					$index = $i;
					break;
				}
			}
			array_splice($all_campaigns,$index,1);
			
			$old_exams = array();
			if(count($all_campaigns) > 0){
				$other_campaigns = true;
				$old_exams = $this->getOldExamSubjects($all_campaigns,$current_campaign);
			}
			
			?>
			<script type = 'text/javascript'>
				var create_exam = function(){
					var t = this;
					/**
					 * Start the process
					 */
					t._init = function(){
// 						t.container = document.createElement("div");
						t.container = document.getElementById("create_subject");
						t._setContainer();
// 						t.pop = new popup_window(
// 									"Create an Exam Subject",
// 									theme.build_icon("/static/selection/exam/exam_16.png",theme.icons_10.add,"right_bottom"),
// 									t.container,
// 									true
// 								);
// 						t.pop.show();
					};

					/**
					 * Get all the other campaigns to set the list of the old exams
					 */
					t.other_campaigns = <?php echo json_encode($other_campaigns);?>;
					t.old_exams = null;
					<?php
					if($other_campaigns){
						echo "t.old_exams = [";
						$first_exam = true;
						foreach($old_exams as $campaign_name => $exam){
							if(count($exam) > 0){
								if(!$first_exam)
									echo ", ";
								$first_exam = false;
								echo "{campaign_id:".json_encode($exam[0]);
								echo ", campaign_name:".json_encode($campaign_name);
								echo ", exams:[";
								$first = true;
								for($i = 1; $i < count($exam); $i++){
									if(!$first)
										echo ", ";
									$first = false;
									echo "{name:".json_encode($exam[$i]["name"]);
									echo ", id:".json_encode($exam[$i]["id"])."}";
								}
								echo "]}";
							}
						}
						echo "];";
					} else echo "t.old_exams = []";
					?>
					/**
					 * Create the menu inside the container
					 */
					t._setContainer = function(){						
						var ul = document.createElement("ul");
						t.container.appendChild(ul);
						//create from scratch row
						var li1 = document.createElement("li");
						li1.innerHTML = "Create a subject from scratch";
						var b_from_zero = t._createButton("<b>Go!</b>");
						b_from_zero.onclick = function(){
							location.assign("/dynamic/selection/page/exam/subject");
						};
						li1.appendChild(b_from_zero);
						ul.appendChild(li1);
						
						//create by importing questions file
						var li3 = document.createElement("li");
						li3.innerHTML = "Create by importing an Excel questions file";
						var b_import = t._createButton("<b>Go!</b>");
						b_import.onclick = function(){
							location.assign("/dynamic/selection/page/exam/import_subject");
						};
						li3.appendChild(b_import);
						ul.appendChild(li3);
						
						//create from previous exams
						if(t.old_exams.length > 0){
							var li2 = document.createElement("li");
							li2.innerHTML = "Create starting from an other campaign subject";
							var ul2 = document.createElement("ul");
							for(var i = 0; i < t.old_exams.length; i++){
								var li = document.createElement('li');
								li.innerHTML = t.old_exams[i].campaign_name.uniformFirstLetterCapitalized();
								var ul_exam = document.createElement("ul");
								for(var j = 0; j < t.old_exams[i].exams.length; j++){
									var li_exam = document.createElement("li");
									li_exam.appendChild(t._createButton(
										t.old_exams[i].exams[j].name,
										true,
										t.old_exams[i].campaign_id,
										t.old_exams[i].exams[j].id
									));
									ul_exam.appendChild(li_exam);
									// alert(j);
								}
								li.appendChild(ul_exam);
								// alert(i);
								ul2.appendChild(li);
							}
							li2.appendChild(ul2);
							ul.appendChild(li2);
						}						
					};

					/**
					 * Create a button item, into a div element
					 * @param {string | HRML} content to put into the div
					 * @param {boolean} from previous true if it is a button linked to a subjet from a previous campaign
					 * @param {number} campaign_id only used when from_previous == true. The id of the campaign that the button shall refer to
					 * @param {number} campaign_id only used when from_previous == true. The id of the subject that the button shall refer to
					 */
					t._createButton = function(content, from_previous, campaign_id, exam_id){
						var div = document.createElement("BUTTON");
						//div.className = "button";
						div.innerHTML = content;
						if(from_previous){
							div.campaign_id = campaign_id;
							div.exam_id = exam_id;
							div.onclick = function(){
								location.assign("/dynamic/selection/page/exam/subject?id="+this.exam_id+"&campaign_id="+this.campaign_id);
							};
						}
						return div;
					};
					
// 					require("popup_window.js",function(){
// 						t._init();
// 					});
					t._init();
				}
				create_exam();
			</script>
			<?php
		}
	}
	
	/**
	 * Get the old exam subjects (id and name) from previous campaigns
	 * @param array $all_campaigns array of the campaigns ids
	 * @param number $current_campaign id of the current campaign to exclude from the query performed
	 * @return array 	array("campaign_name" => array(
	 * 							[0]: campaign_id
	 * 							["name"]:exam_name
	 * 							["id"]:exam_id 
	 * 						))
	 */
	public function getOldExamSubjects($all_campaigns,$current_campaign){
		$old_exams = array();
		foreach($all_campaigns as $c){
			$old_exams[$c["name"]] = array();
			$first = true;
			SQLQuery::setSubModel("SelectionCampaign", $c["id"]);
			$exams = SQLQuery::create()
						->select("ExamSubject")
						->field("ExamSubject","id","id")
						->field("ExamSubject","name","name")
						->execute();
			if(isset($exams[0]["id"])){
				// the first attribute is the campaign id
				$old_exams[$c["name"]][0] = $c["id"];
				foreach($exams as $exam)
					array_push($old_exams[$c["name"]],array("name"=>$exam["name"],"id"=>$exam["id"]));
			}
		}
		//reset the current sub model
		SQLQuery::setSubModel("SelectionCampaign", $current_campaign);
		return $old_exams;
	}
	
}
?>