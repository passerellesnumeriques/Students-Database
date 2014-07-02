<?php 
require_once("/../SelectionPage.inc");
class page_exam_results extends SelectionPage {
	public function getRequiredRights() { return array(); }
	public function executeSelectionPage(){
			
		theme::css($this, "grid.css");
		theme::css($this, "section.css");

		$this->requireJavascript("horizontal_layout.js");
		$this->requireJavascript("address_text.js");
		$this->onload("new horizontal_layout('horizontal_split',true);");
		$this->addJavascript("/static/widgets/section/section.js");
		$this->onload("sectionFromHTML('sessions_listDiv');");
		$this->onload("sectionFromHTML('session_infoDiv');");
		$this->onload("sectionFromHTML('session_applicantsDiv');"); 
		$this->addJavascript("/static/selection/exam/results.js");
		$this->requireJavascript("data_list.js");
		$this->onload("createDataList(".$this->component->getCampaignId().");");
	?>
	
			<!--TODO : css cleanup (= merge it with the right css file) -->
		<style>
			table.grid>tbody>tr>td {
				text-align: center;
			}
			table.grid>tbody>tr.clickable_row:hover{
			background-color: #FFF0D0;
			background: linear-gradient(to bottom, #FFF0D0 0%, #F0D080 100%);
			}
			
			table.grid>tbody>tr.selectedRow{
			background-color: #FFF0D0;
			background: linear-gradient(to bottom, #FFF0D0 0%, orange 100%);
			}
		</style>
			
	<!-- main structure of the exam results page -->
	<div id='horizontal_split' style="margin:10px;">
		<div id="sessions_with_button" style="display: inline-block;vertical-align: top;" layout="fill">
			<div style ="max-height: 300px;overflow: auto">
			      <div id = "sessions_listDiv" title='Exam sessions list' icon="/static/calendar/calendar_16.png" collapsable='true' css="soft">
				<?php $this->createTableSessionsList();?>
			      </div>
			</div>
			<div id="sessions_buttonsDiv" style="display:table;width:100%;height:28px;background-color:rgb(229,190,212);border-radius:3px;">
				<div id="buttonEditWrapper" style="display:table-cell;;vertical-align:middle;text-align: center;">
					<button id="edit_notes" class="action" style="display:inline" >EDIT NOTES</button>
				</div>
			</div>
		</div>
		<div id = "session_infoDiv" title='Exam session informations' icon="/static/theme/default/icons_16/info.png" collapsable='true' style='display:inline-block;vertical-align: top;' css="soft">
				<div id="session_info_locationDiv" style='padding-left:5px;'></div>
		</div>
	</div>
		

	<!--List of applicants-->		
	<div id = "session_applicantsDiv" title='Applicants list' icon="/static/selection/applicant/applicants_16.png" collapsable='true' css="soft" style="width:500px;margin: 20px 0 0 10px;" >
	       <div id="session_applicants_listDiv"></div>
	</div>

	<?php
	}
	/*
	 * Generate html Table element displaying Sessions List (grouped by Exam Center)
	 */
	private function createTableSessionsList()
		{
			$q = SQLQuery::create()->select("ExamCenter")
					->field("ExamCenter","name")
					->field("CalendarEvent","start")
					->field("CalendarEvent","end")
					->field("ExamCenterRoom","name","room_name")
					->field("ExamCenterRoom","id","room_id")
					->countOneField("Applicant","applicant_id","applicants")
					->join("ExamCenter","ExamSession",array("id"=>"exam_center"))
					->whereNotNull("ExamSession","event")
					->join("ExamSession","Applicant",array("event"=>"exam_session"))
					->field("ExamSession","event","session_id")
					->join("Applicant","ExamCenterRoom",array("exam_center_room"=>"id"));
			PNApplication::$instance->calendar->joinCalendarEvent($q, "ExamSession", "event");
			$exam_sessions=$q->groupBy("ExamSession","event")->groupBy("ExamCenterRoom","id")->execute();

		?>
			<table class="grid" id="table_exam_results" style="width: 100%">
				<thead>
					<tr>
					      <th>Exam Session</th>
					      <th>Room</th>
					      <th>Applicants</th>
					      <th>Status</th>					      
					</tr>
				</thead>
				<tbody>
			<?php
			$exam_center="";
			foreach($exam_sessions as $exam_session){
				$session_name=date("Y.m.d",$exam_session['start'])." (".date("h:i:a",$exam_session['start'])." to ".date("h:i:a",$exam_session['end']).")";
				if ($exam_center<>$exam_session['name']){ // Group for a same exam center
				       $exam_center=$exam_session['name'] ?>
				       <tr class="exam_center_row" >
					       <th colspan="4" ><?php echo $exam_center?></th>
				       </tr><?php } //end of if statement ?> 
					<tr  class="clickable_row" style="cursor: pointer" session_id="<?php echo $exam_session['session_id'];?>" room_id="<?php echo $exam_session['room_id'];?>" > 
						<td><?php echo $session_name ?></td>
						<td><?php echo $exam_session['room_name'] ?></td>
						<td><?php echo $exam_session['applicants'] ?></td>
						<td><?php echo 'TODO..' ?></td>
					</tr>
				<?php } // end of foreach statement ?>
				</tbody>
			</table>
		<?php
		}
}
?>