<?php 
class service_exam_get_available_supervisors_for_session extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	public function documentation() {
		echo "Get all the staff not assigned to a given exam session";
	}
	public function inputDocumentation() {
		echo "<code>session_id</code> exam session ID";
	}
	public function outputDocumentation() {
		?>
		JSON array of Staff objects
		<?php
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		require_once 'component/staff/StaffJSON.inc';
		if(isset($input["session_id"])){
			//Get the supervisors already assigned
			$already = SQLQuery::create()
				->select("ExamSessionSupervisor")
				->field("ExamSessionSupervisor","staff")
				->whereValue("ExamSessionSupervisor","session",$input["session_id"])
				->executeSingleField();
			$available = SQLQuery::create();
			PNApplication::$instance->staff->selectStaffTable($available);
			StaffJSON::StaffSQL($available);
			if($already <> null){
				$available->whereNotIn("Staff","people",$already);
			}
			$available = $available->execute();
			echo '[';
			if($available <> null){
				$first = true;
				foreach ($available as $staff){
					if(!$first) echo ", ";
					$first = false;
					echo StaffJSON::Staff(null,$staff);
				}
			}
			echo "]";
		} else 
			echo "false";
	}
	
}
?>