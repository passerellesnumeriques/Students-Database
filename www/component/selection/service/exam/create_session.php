<?php
class service_exam_create_session extends Service{
	public function get_required_rights(){return array("manage_exam_center");}
	public function input_documentation(){
		?><ul>
		<li><code>event</code> CalendarObject event</li>
		<li><code>EC_id</code> exam center ID</li>
		</ul>
		<?php
	}
	public function output_documentation(){
		echo "<code>event_id</code> {Number} the event id of the session created";
	}
	public function documentation(){
		echo "Create an exam session from its event";
	}
	public function execute(&$component,$input){
		if(isset($input["event"])&& isset($input["EC_id"])){
			if(isset($input["event"]["id"]))
				unset($input["event"]["id"]);
			if(isset($input["event"]["uid"]))
				unset($input["event"]["id"]);
			$id = $component->createExamSession($input["event"], $input["EC_id"]);
			if(!$id) echo "false";
			else echo '{event_id:'.json_encode($id)."}";
		} else 
			echo "false";
	}
}	
?>