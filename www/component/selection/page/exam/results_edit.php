<?php 
require_once("/../SelectionPage.inc");
class page_exam_results_edit extends SelectionPage {
	public function getRequiredRights() { return array(); }
	public function executeSelectionPage(){
			
		if (isset($_POST) && isset($_POST["input"])){
			$input = json_decode($_POST["input"], true);
		}
		else
			return; 
		
		theme::css($this, "section.css");
		
		$this->requireJavascript("grid.js");
		$this->requireJavascript("tabs.js");
		$this->addJavascript("/static/widgets/section/section.js");
		$this->onload("sectionFromHTML('applicant_info');"); 
		$this->onload("sectionFromHTML('exam_session_info');"); 
		$this->addJavascript("/static/selection/exam/results_edit.js");
		$this->requireJavascript("vertical_layout.js");
		$this->onload("new vertical_layout('main_container');");

	?>
	      <!-- TODO : css clean up (merge with the right css file)-->
		<style>
			ul.no_bullet{
				list-style-type:none;
			}
			.align_left {
				text-align:left;
			}
		</style>
	
		<!-- main structure of the results edit page -->
		<div id='main_container'>
			<div id="header_results" style="height:200px">
			      <div id="exam_session_info" title='Exam session' icon="/static/theme/default/icons_16/info.png" collapsable='true' css="soft" style="display:inline-block;margin:10px 0 0 10px;vertical-align: top;">
				<?php $this->createExamSessionInfoBox($input); ?>
			      </div>
			      <div id="applicant_info" title='Applicant' icon="/static/selection/applicant/applicant_16.png" collapsable='true' css="soft" style="display:inline-block;margin:10px 5px 0 0;vertical-align: top;">
				<?php $this->createApplicantInfoBox($input); ?>
			      </div>
			</div>
			<div layout="fill">
				<div id="subj_results" style="margin:10px;display:inline-block"></div>
			</div>
			<div id="footer_results" style="margin-right: 10px;">
			</div>
		</div>
	<?php
	}

	
	
	
	/*
	 * Generating html elements of Applicant Info Box
	 */
	private function createApplicantInfoBox(&$input)
	{	
		/* fields from people that we want to display */
		$fields=array("first_name","middle_name","khmer_first_name","khmer_last_name","last_name","sex","birthdate");
		
		?><img id='applicant_photo' style='margin:5px;width:50px;height:50px;display:none;float:left;'/>
		<table id='applicant_table' class="align_left" style='display: none'>
			<?php foreach ($fields as $field) {?>
			<tr>
				<th id="<?php echo $field ?>"></th><td></td>
			</tr>
		<?php } //end of foreach statement ?>
		</table>	
		<?php 
	}
	
	/*
	 * Generating html elements of Exam Session Info Box
	 */
	private function createExamSessionInfoBox(&$input)
	{
		/* fields that we want to display */
		$fields=array('Exam Center'=>"exam_center_name",'Exam Session'=>"session_name",'Room'=>"room_name");
		
	?>
		<table class="align_left">
			<?php foreach ($fields as $description=>$field) {
				if ($input[$field]==null) continue;?>
			<tr>
				<th><?php echo $description; ?></th><td><?php echo $input[$field];?></td>
			</tr>
		<?php } //end of foreach statement ?>
		<!--Putting some useful data into hidden fields-->
			<tr>
				<td id="session_id" style="display:none;">
					<?php echo htmlspecialchars($input["session_id"]); ?>
				</td>
				<td id="room_id" style="display:none;">
					<?php echo htmlspecialchars($input["room_id"]); ?>
				</td>
			</tr>
		</table>
	<?php	
	}
}
?>