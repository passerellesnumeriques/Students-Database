<?php
require_once("component/contact/ContactJSON.inc");
require_once("component/selection/SelectionJSON.inc");
function prepareDataAndSaveIS($data,$create){
	if($create && isset($data["id"]))
		unset($data["id"]);
	$fields_values_IS = SelectionJSON::InformationSessionTableData2DB($data);
	if(!$create){
		PNApplication::$instance->selection->saveIS($data["id"],$fields_values_IS);
	} else {
		$id = PNApplication::$instance->selection->saveIS(null,$fields_values_IS);
		return $id;
	}
}

class service_IS_save extends Service{
	public function get_required_rights(){return array("manage_exam_center");}
	public function input_documentation(){
		?>

		<?php
	}
	public function output_documentation(){
		?>

		<?php
	}
	public function documentation(){
		
	}
	public function execute(&$component,$input){
		if(!isset($input["data"]))
			echo "false";
		else {
			$data = $input["data"];
			$everything_ok = true;
			$insert_EC = false;
			$new_EC_id = null;
				
			if($data["id"] == -1 || $data["id"] == "-1"){
				//This is an insert
				$insert_EC = true;
			}
			//start the transaction
			SQLQuery::startTransaction();
			if($add_event && $everything_ok){
				try{
					if(isset($event["id"]))
						unset($event["id"]);
					$event["calendar_id"] = PNApplication::$instance->selection->getCalendarId();
					$event["organizer"] = "Selection";
					$event["title"] = PNApplication::$instance->geography->getGeographicAreaText($data["geographic_area"]);
					if (!$insert_IS) {
						$event["app_link"] = "/dynamic/selection/page/IS/profile?id=".$data["id"];
						$event["app_link_name"] = "This event is an Information Session: click to see it";
					}
					PNApplication::$instance->calendar->saveEvent($event);
					$event_id = $event["id"];
				} catch(Exception $e){
					$everything_ok = false;
					PNApplication::error($e);
				}
				$data["date"] = $event_id;
			} else if($update_event && $everything_ok){
				$event["title"] = PNApplication::$instance->geography->getGeographicAreaText($data["geographic_area"]);
				if (!$insert_IS) {
					$event["app_link"] = "/dynamic/selection/page/IS/profile?id=".$data["id"];
					$event["app_link_name"] = "This event is an Information Session: click to see it";
				}
				try{
					PNApplication::$instance->calendar->saveEvent($event);
				} catch(Exception $e){
					$everything_ok = false;
					PNApplication::error($e);
				}
			} else if($remove_event && $everything_ok) {
				try{
					PNApplication::$instance->calendar->removeEvent($event_to_remove,PNApplication::$instance->selection->getCalendarId());
					$data["date"] = null;
				} catch(Exception $e){
					$everything_ok = false;
					PNApplication::error($e);
				}
			}
			if($insert_IS && $everything_ok){
				/*create the IS to get the id*/
				try{
					$new_IS_id = prepareDataAndSaveIS($data,true);
					$data["id"] = $new_IS_id;
					if ($add_event) {
						$event["app_link"] = "/dynamic/selection/page/IS/profile?id=".$data["id"];
						$event["app_link_name"] = "This event is an Information Session: click to see it";
						PNApplication::$instance->calendar->saveEvent($event);
					}
				} catch (Exception $e){
					$everything_ok = false;
					PNApplication::error($e);
				}
			} else if($everything_ok){
				try{
					prepareDataAndSaveIS($data,false);
				} catch (Exception $e){
					$everything_ok = false;
					PNApplication::error($e);
				}
			}
			if($everything_ok){
				/*save the partners and contact_points*/
				try{
					prepareDataAndSavePartnersAndContactsPoints($data);
				} catch (Exception $e){
					$everything_ok = false;
					PNApplication::error($e);
				}
			}
				
			if(!$everything_ok || PNApplication::has_errors()){
				SQLQuery::rollbackTransaction();
				echo "false";
			} else {
				SQLQuery::commitTransaction();
				echo "{id:".json_encode($data["id"]);
				echo ",date:".json_encode($data["date"]);
				echo "}";
			}
		}
	}
}	
?>