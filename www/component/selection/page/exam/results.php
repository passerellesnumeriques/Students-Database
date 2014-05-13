<?php 
require_once("/../selection_page.inc");
class page_exam_results extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(){

	
	//DEBUG
	//echo date_default_timezone_get();
	//die();
	//
	/* Display table of results by Exam center, exams sessions , exam rooms .. */
	

	$q = SQLQuery::create()->select("ExamCenter")
			//->noWarning() // TODO
			->field("ExamCenter","name")
			->field("CalendarEvent","start")
			->field("CalendarEvent","end")
			->field("ExamCenterRoom","name","room_name")
			->countOneField("Applicant","applicant_id","applicants")
			->join("ExamCenter","ExamSession",array("id"=>"exam_center"))
			->join("ExamSession","Applicant",array("event"=>"exam_session"))
			->join("Applicant","ExamCenterRoom",array("exam_center_room"=>"id"));
	PNApplication::$instance->calendar->joinCalendarEvent($q, "ExamSession", "event");
	$exam_sessions=$q->groupBy("ExamSession","event")->groupBy("ExamCenterRoom","id")->execute();
 
	/* DIM TODO : improve this display (in case of no results) */
	if ($exam_sessions===null)
		echo " No result yet !";
	
	theme::css($this, "grid.css");
	theme::css($this, "section.css");
	
	/* DIM : Should we take into account if applicant is excluded or not?
	Also maybe a status field about the state of the exam session */
	
	
	/* Displaying the results on a table */
?>	
	
<!--	DIM : a few modifications from grid.css and section.css
	TODO : sort css  more neatly (specific css file ? or inside global.css ? ) -->
	<style>
	
	
	.section>.header{
		padding-left: 5px;
		border-bottom: 1px solid black;
	}
	.section>.header>h1{
		margin-left: 5px;
		margin-right: 5px;
		font-weight: bold;
		font-size: 11pt;
	}
	
	table.grid>tbody>tr>td {
		text-align: center;
	}
	
	table.grid>tbody>tr.selectable:hover{
	background-color: #FFF0D0;
	background: linear-gradient(to bottom, #FFF0D0 0%, #F0D080 100%);
	}
	</style>
	
	<div style="width: 60%; margin:10px;">
		<div class="section soft">
			<div class="header">
				<h1>Exam results</h1>	
			</div>
			<table class="grid" id="table_exam_results" style="width: 100%">
				<thead>
					<tr>
					      <th>Exam Session</th>
					      <th>Room</th>
					      <th>Applicants</th>
					</tr>
				</thead>
				<tbody>
			<?php
			//DEBUG
			//date_default_timezone_set ("Europe/London");
			$exam_center="";
			foreach($exam_sessions as $exam_session){ ?>
			<?php if ($exam_center<>$exam_session['name']){ // Group for a same exam center
				$exam_center=$exam_session['name'] ?>
				<tr>
				
					<th colspan="3" class="exam_center"><?php echo $exam_center?></th>
				</tr><?php } //end of if statement ?> 
				<tr class="selectable" style="cursor: pointer">
					 
					<td><?php echo date("Y.m.d.e",$exam_session['start']+8*3600)." ".date("h:i:a",$exam_session['start']+8*3600)." to ".date("h:i:a",$exam_session['end']+8*3600)?></td>
					<td><?php echo $exam_session['room_name'] ?></td>
					<td><?php echo $exam_session['applicants'] ?></td>
				</tr>
				<?php } // end of foreach statement ?>
				</tbody>
			</table>
		</div>
	</div>

<?php
	}
}
