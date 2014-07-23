<?php
require_once("component/contact/ContactJSON.inc");
require_once("component/selection/SelectionInformationSessionJSON.inc");
function prepareDataAndSaveIS($data,$create){
	if($create && isset($data["id"]))
		unset($data["id"]);
	$fields_values_IS = SelectionInformationSessionJSON::InformationSessionTableData2DB($data);
	if(!$create){
		PNApplication::$instance->selection->saveIS($data["id"],$fields_values_IS);
	} else {
		$id = PNApplication::$instance->selection->saveIS(null,$fields_values_IS);
		return $id;
	}
}

class service_IS_save extends Service{
	public function getRequiredRights(){return array("manage_information_session");}
	public function inputDocumentation(){
		?>
		<ul>
			<li><code>data</code> {array} Information session data, coming from IS_profile.js</li>
			<li><code>event</code> {array} Calendar event object</li>
		</ul>
		<?php
	}
	public function outputDocumentation(){
		?>
		<ul>
			<li><code>false</code> {boolean} if an error occured</li>
			<li><code>array</code> id, date
				<ul>
					<li><code>id</code> id of the information session</li>
					<li><code>date</code> id of the calendar event linked to this information session, if set</li>
				</ul>
			</li>
		</ul>
		<?php
	}
	public function documentation(){
		echo 'Save an information session object. All the query are performed into a transaction in the case of any error happen';
	}
	public function execute(&$component,$input){
		if(!isset($input["event"]) || !isset($input["data"]))
			echo "false";
		else {
			$data = $input["data"];
			$event = $input["event"];
			$everything_ok = true;
			$add_event = false;
			$update_event = false;
			$remove_event = false;
			$insert_IS = false;
			$event_to_remove = null;
			$new_IS_id = null;
			
			//Update the name (geographic area may have changed)
			if($data["name"] == null || !PNApplication::$instance->selection->getOneConfigAttributeValue("give_name_to_IS"))
				$data["name"] = PNApplication::$instance->geography->getGeographicAreaText($data["geographic_area"]);
				
			if($data["id"] == -1 || $data["id"] == "-1"){
				//This is an insert
				$insert_IS = true;
				//In that case if data.date <> null, event must be added (not updated)
				if(isset($event["start"])&& $event["start"] != "null"){
					$add_event = true;
					$update_event = false;
					$remove_event = false;
				} else {
					$add_event = false;
					$update_event = false;
					$remove_event = false;
				}
			} else {
				if($event["start"] <> null && $event["start"] != "null"){
					//it can be an update or an add
					if($event["id"] == -1 || $event["id"] == "-1"){
						unset($event["id"]);
						$add_event = true;
						$update_event = false;
					} else {
						$add_event = false;
						$update_event = true;
					}
					$remove_event = false;
				} else {
					//it can be a remove or nothing
					$q = SQLQuery::create()->select("InformationSession")->field("date")->whereValue("InformationSession","id",$data["id"])
					->executeSingleValue();
					if($q <> null){
						$event_to_remove = $q;
						$remove_event = true;
					}
					$add_event = false;
					$update_event = false;
				}
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
				$data["date"] = $event["id"];
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
				$rows = ContactJSON::PartnersAndContactPoints2DB($data,"information_session");
				$rows_IS_partner = $rows[0];
				$rows_IS_contact_point = $rows[1];
				//No need to try catch, the method called handles this
				PNApplication::$instance->selection
					->savePartnersAndContactsPoints($data["id"],$rows_IS_partner,$rows_IS_contact_point,"InformationSession","information_session");
			}
				
			if(!$everything_ok || PNApplication::hasErrors()){
				SQLQuery::rollbackTransaction();
				echo "false";
			} else {
				SQLQuery::commitTransaction();
				echo "{id:".json_encode($data["id"]);
				echo ",date:".json_encode(@$data["date"]);
				echo "}";
			}
		}
	}
}	
?>