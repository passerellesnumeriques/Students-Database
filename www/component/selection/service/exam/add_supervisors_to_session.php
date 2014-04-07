<?php 
class service_exam_add_supervisors_to_session extends Service {
	
	public function get_required_rights() { return array("can_access_selection_data"); }
	public function documentation() {
		echo "Add supervisors (staff or custom) to an exam session";
	}
	public function input_documentation() {
		?>
		<ul>
			<li><code>session_id</code> number exam session ID</li>
			<li><code>staffs</code> NULL | array of staff ids to assign to this session</li>
			<li><code>custom</code> NULL | string custom supervisor field</li>
		</ul>
		<?php
	}
	public function output_documentation() {
		?>
		true if well performed, else false
		<?php
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		$session_id = $input["session_id"];
		$custom = @$input["custom"];
		$staffs = @$input["staffs"];
		SQLQuery::startTransaction();
		try {
			if($custom <> null){
				SQLQuery::create()
					->insert("ExamSessionSupervisorCustom", array("name" => $custom,"session" =>$session_id));
			}
			if($staffs <> null){
				$rows = array();
				foreach($staffs as $staff_id)
					array_push($rows, array("staff" => $staff_id, "session" => $session_id));
				SQLQuery::create()->insertMultiple("ExamSessionSupervisor", $rows);
			}
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(PNApplication::has_errors()){
			SQLQuery::rollbackTransaction();
			echo "false";
		} else {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>