<?php 
require_once("/../SelectionPage.inc");
class page_exam_results_edit extends SelectionPage {
	public function getRequiredRights() { return array(); }
	public function executeSelectionPage(){
		
		if (isset($_POST) && isset($_POST["input"]))
			$input = json_decode($_POST["input"], true);
		else
			return; // TODO : error handling ?
		
		theme::css($this, "section.css");
		$this->addJavascript("/static/widgets/section/section.js");
		$this->onload("sectionFromHTML('applicant_info');");
		$this->onload("sectionFromHTML('exam_session_info');"); 
		//$this->addJavascript("/static/selection/exam/results_edit.js");
	?>
	      <!--TODO : css clean up-->
		<style>
			table
			{
				text-align:left;
				display: inline-block;
			}
	
			td{
				padding: 0 50px;
				
			}
		</style>
	
		<!-- main structure of the results edit page -->
		<div id="header_results">
		      <div id="exam_session_info" title='Exam session' icon="/static/theme/default/icons_16/info.png" collapsable='true' css="soft" style="display:inline-block;margin:10px 0 0 10px;width:500px;vertical-align: top;">
			<?php $this->createExamSessionInfoBox($input); ?>
		      </div>
		      <div id="applicant_info" title='Applicant' icon="/static/selection/applicant/applicant_16.png" collapsable='true' css="soft" style="display:inline-block;margin:10px 5px 0 0;width:300px;vertical-align: top;">
			<?php $this->createApplicantInfoBox($input); ?>
		      </div>
		</div>
		<div id="main_results" style="margin:10px;max-height:500px;overflow: auto;">
			<p style="background-color: white;height:300px;margin:10px 0 0 10px;">
				Here we'll put the table for answers seizure
			</p>
		</div>
		<div id="footer_results" style="margin-right: 10px;">
			<button class="action" style="float:right;">SAVE</button>
			<button class="action" style="float:right;">CANCEL</button>
		</div>
	<?php
	}
	
	/*
	 * Generating html elements of Applicant Info Boxn
	 */
	private function createApplicantInfoBox(&$input)
	{
		$people_id=$input["people_id"];
		
		/* Get people informations */
		$people=PNApplication::$instance->people->getPeople($people_id);
		
		/* fields from people that we want to display */
		$fields=array('First Name'=>"first_name",'Middle Name'=>"middle_name",'Last Name'=>"last_name",'Gender'=>"sex",'Birth'=>"birth");
		
		?><img src='/dynamic/people/service/picture?people=<?php echo $people_id;?>' style='margin:5px;width:50px;height:50px'/>
		<table>
			<?php foreach ($fields as $description=>$field) {
				if ($people[$field]==null) continue;?>
			<tr>
				<th><?php echo $description; ?></th><td><?php echo $people[$field];?></td>
			</tr>
		<?php } //end of foreach statement ?>
		</table>	
		<?php 
	}
	
	/*
	 *Generating html elements of Exam Session Info Box
	 */
	private function createExamSessionInfoBox(&$input)
	{
		/* Get applicant_id */
		$q = SQLQuery::create()->select("Applicant")
					->field("Applicant","applicant_id")
					->where("people",$input["people_id"]);
		$input["applicant_id"]=$q->executeSingleField()[0];

		/* fields that we want to display */
		$fields=array('Applicant Id'=>"applicant_id",'Exam Center'=>"exam_center_name",'Exam Session'=>"session_name",'Room'=>"room_name");
		
	?>
		<table>
			<?php foreach ($fields as $description=>$field) {
				if ($input[$field]==null) continue;?>
			<tr>
				<th><?php echo $description; ?></th><td><?php echo $input[$field];?></td>
			</tr>
		<?php } //end of foreach statement ?>
		</table>
	<?php	
	}
}
?>