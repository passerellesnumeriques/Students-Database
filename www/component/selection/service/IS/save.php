<?php
require_once("component/contact/ContactJSON.inc");
function prepareDataAndSaveIS($data,$create){
	$fields_values_IS = array(
		"date" => $data["date"],
		"postal_address" => $data["address"],
		"fake_organization" => $data["fake_organization"],
		"number_boys_expected" => $data["number_boys_expected"],
		"number_boys_real" => $data["number_boys_real"],
		"number_girls_expected" => $data["number_girls_expected"],
		"number_girls_real" => $data["number_girls_real"],
		"name" => $data["name"],
		);
	if(!$create){
		PNApplication::$instance->selection->saveIS($data["id"],$fields_values_IS);
	} else {
		$id = PNApplication::$instance->selection->saveIS(null,$fields_values_IS);
		return $id;
	}
}

function prepareDataAndSavePartnersAndContactsPoints($data){	
	$rows_IS_partner = array();
	$rows_IS_contact_point = array();
	foreach($data["partners"] as $p){
		array_push($rows_IS_partner,array(
			"information_session" => $data["id"],
			"organization" => $p["organization"],
			"host" => $p["host"],
			"host_address" => $p["host_address"]
		));
		if(isset($p["contact_points_selected"]) && count($p["contact_points_selected"]) > 0){
			foreach($p["contact_points_selected"] as $people){
				array_push($rows_IS_contact_point,array(
					"information_session" => $data["id"],
					"organization" => $p["organization"],
					"people" => $people
				));
			}
		}
	}
	PNApplication::$instance->selection->removeAllISPartners($data["id"]);
	PNApplication::$instance->selection->removeAllISContactPoints($data["id"]);
	if(count($rows_IS_partner) > 0)
		PNApplication::$instance->selection->insertISPartners($rows_IS_partner);
	if(count($rows_IS_contact_point) > 0)
		PNApplication::$instance->selection->insertISContactPoints($rows_IS_contact_point);
}

class service_IS_save extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){
	
	}
	public function output_documentation(){
	
	}
	public function documentation(){
	}
	public function execute(&$component,$input){
		if(!isset($input["event"]) || !isset($input["address"]) || !isset($input["data"]))
			echo "false";
		else {
			$data = $input["data"];
			$address = $input["address"]; // TODO here it must be a PostalAddress, then no need to convert it to database format...
			$event = $input["event"];
			$everything_ok = true;
			$add_event = false;
			$add_address = false;
			$update_address = false;
			$update_event = false;
			$remove_address = false;
			$remove_event = false;
			$insert_IS = false;
			$create_fake_organization = false;
			$address_to_remove = null;
			$event_to_remove = null;
			$new_IS_id = null;
			
			if($data["id"] == -1 || $data["id"] == "-1"){
				//This is an insert
				$insert_IS = true;
				$create_fake_organization = true;
				//In that case, if data.address or data.date <> null, they must be added (not updated)
				if($data["address"] <> null && $data["address"] != "null"){
					unset($address["id"]);
					$add_address = true;
					$update_address = false;
					$remove_address = false;
				} else {
					$add_address = false;
					$update_address = false;
					$remove_address = false;
				}
				if(isset($event["start"]) && $event["start"] <> null && $event["start"] != "null"){
					$add_event = true;
					$update_event = false;
					$remove_event = false;
				} else {
					$add_event = false;
					$update_event = false;
					$remove_event = false;
				}
			} else {
				//This is an update
				$insert_IS = false;
				$create_fake_organization = false; //the fake organization must be created at the same time as the information session
				if($data["address"] <> null && $data["address"] != "null"){
					//it can be an update or an add
					if($data["address"] == -1 || $data["address"] == "-1"){
						$add_address = true;
						$update_address = false;
					} else {
						$add_address = false;
						$update_address = true;
					}
					$remove_address = false;
				} else {
					//it can be a remove or nothing
					$q = SQLQuery::create()->select("Information_session")->field("postal_address")->where_value("Information_session","id",$data["id"])
							->execute_single_value();
					if($q <> null){
						$address_to_remove = $q;
						$remove_address = true;
					}
					$add_address = false;
					$update_address = false;
				}
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
					$q = SQLQuery::create()->select("Information_session")->field("date")->where_value("Information_session","id",$data["id"])
							->execute_single_value();
					if($q <> null){
						$event_to_remove = $q;
						$remove_event = true;
					}
					$add_event = false;
					$update_event = false;
				}
			}
			//start the transaction
			SQLQuery::start_transaction();
			if($create_fake_organization){
				try{
					$fake_organization = SQLQuery::create()->insert("Organization", array(
					"name" => "fake_for_IS",
					"creator" => "Selection",
					"fake" => true
					));
				} catch(Exception $e){
					$everything_ok = false;
					PNApplication::error($e);
				}
				$data["fake_organization"] = $fake_organization;
			}
			if($add_event && $everything_ok){
				try{
					if(isset($event["id"]))
						unset($event["id"]);
					$event["calendar_id"] = PNApplication::$instance->selection->getCalendarId();
					$event["organizer"] = "Selection";
					// var_dump($event);
					PNApplication::$instance->calendar->saveEvent($event);
					$event_id = $event["id"];
				} catch(Exception $e){
					$everything_ok = false;
					PNApplication::error($e);
				}
				$data["date"] = $event_id;
			} else if($update_event && $everything_ok){
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
			if($add_address && $everything_ok){
				try{
					/* Unset address id in case it exists (address coming from a partner and then customized*/
					if(isset($address["id"]))
						unset($address["id"]);
					$address_id = PNApplication::$instance->contact
						->addAddressToOrganization($data["fake_organization"],$address,true);
				} catch(Exception $e){
					$everything_ok = false;
					PNApplication::error($e);
				}
				$data["address"] = $address_id;
			} else if($update_address && $everything_ok){
				try{
					/* Unset address id in case it exists (address coming from a partner and then customized*/
					if(isset($address["id"]))
						unset($address["id"]);
					SQLQuery::create()->update_by_key("Postal_address",$data["address"],$address);
				} catch(Exception $e){
					$everything_ok = false;
					PNApplication::error($e);
				}
			} else if($remove_address && $everything_ok) {
				try{
					SQLQuery::create()->bypass_security()->remove_key("Postal_address",$address_to_remove);
					$data["address"] = null;
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
					//update the fake_organization_name with the good name
					SQLQuery::create()->bypass_security()->update_by_key("Organization",$data["fake_organization"],array("name" => "fake_for_IS_".$data["id"]));
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
				// /*save the partners and contact_points*/
				try{
					prepareDataAndSavePartnersAndContactsPoints($data);
				} catch (Exception $e){
					$everything_ok = false;
					PNApplication::error($e);
				}
			}
			
			if(!$everything_ok || PNApplication::has_errors()){
				SQLQuery::cancel_transaction();
				echo "false";
			} else {
				SQLQuery::end_transaction();
				echo "{id:".json_encode($data["id"]);
				echo ",fake_organization:".json_encode($data["fake_organization"]);
				echo ",address:".json_encode($data["address"]);
				echo ",date:".json_encode($data["date"]);
				echo "}";
			}
		}
	}
}	
?>