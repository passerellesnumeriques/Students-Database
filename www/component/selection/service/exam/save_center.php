<?php
require_once("component/contact/ContactJSON.inc");
require_once("component/selection/SelectionJSON.inc");

class service_exam_save_center extends Service{
	public function get_required_rights(){return array("manage_exam_center");}
	public function input_documentation(){
		?>
		<code>data</code> ExamCenterData JSON object
		<?php
	}
	public function output_documentation(){
		?>
		<ul>
		<li>ExamCenterData JSON object if well saved</li>
		<li>false if any error occured</li>
		</ul>
		<?php
	}
	public function documentation(){
		echo "Save the main data linked to an ExamCenter (location, partners, name, rooms);";
	}
	public function execute(&$component,$input){
		if(!isset($input["data"]))
			echo "false";
		else {
			$data = $input["data"];
			$id = ($data["id"] == -1 || $data["id"] == "-1") ? null : $data["id"];
			//Check that the geographic area field is unique
			$unique = SQLQuery::create()
				->select("ExamCenter")
				->field("ExamCenter","geographic_area")
				->whereValue("ExamCenter", "geographic_area", $data["geographic_area"]);
			if($id <> null)
				$unique->whereNotIn("ExamCenter", "id", array($id));
			$unique = $unique->executeSingleField();
			if($unique <> null){
				PNApplication::error("An exam center already exists in this geographic area");
				return;
			}				
			//Prepare the data
			if($data["name"] == null || !PNApplication::$instance->selection->getOneConfigAttributeValue("give_name_to_exam_center"))
				$data["name"] = PNApplication::$instance->geography->getGeographicAreaText($data["geographic_area"]);
			$rows_center = SelectionJSON::ExamCenter2DB($data, $id);
			$rows_partnership = ContactJSON::PartnersAndContactPoints2DB($data, "exam_center");
			$rows_partners = $rows_partnership[0];
			$rows_contacts_points = $rows_partnership[1];
			$rooms_ids_to_remove = array();
			$rooms_to_update = array();
			$rooms_to_insert = array();
			if($id != null){
				$rooms_existing = SQLQuery::create()
					->bypassSecurity()
					->select("ExamCenterRoom")
					->field("ExamCenterRoom","id")
					->whereValue("ExamCenterRoom", "exam_center", $id)
					->executeSingleField();
				if($rooms_existing <> null){
					$rooms_to_update_ids = array();
					$rooms_to_update_by_id = array();
					foreach ($data["rooms"] as $room){
						if($room["id"] != -1 && $room["id"] != "-1"){
							array_push($rooms_to_update_ids, $room["id"]);
							array_push($rooms_to_update, SelectionJSON::ExamCenterRoom2DB($room, $room["id"], $id));
						}
					}
					foreach ($rooms_existing as $room_id){
						if(!in_array($room_id, $rooms_to_update_ids))
							array_push($rooms_ids_to_remove, $room_id);
					}
				}
			}
			foreach ($data["rooms"] as $room){
				if($room["id"] == -1 || $room["id"] == "-1")
					array_push($rooms_to_insert, SelectionJSON::ExamCenterRoom2DB($room, null, $id));
			}
			$r = PNApplication::$instance->selection->saveExamCenter(
						$id,
						$rows_center,
						$rows_partners,
						$rows_contacts_points,
						$rooms_ids_to_remove,
						$rooms_to_update,
						$rooms_to_insert
					 );
			if($r == false)
				echo "false";
			else {
				echo SelectionJSON::ExamCenterFromID($r);
			}
		}
	}
}	
?>