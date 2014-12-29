<?php 
class SelectionPartnersJSON {

	/**
	 * Return the JSON structure of the partners for the given center
	 * @param string $center_type 'InformationSession', 'ExamCenter' or 'InterviewCenter'
	 * @param integer $id center id
	 * @return string the JSON
	 * @no_name_check
	 */
	public static function PartnersFromCenterID($center_type, $id, $campaign_id) {
		switch ($center_type) {
			case "InformationSession": $join_key = "information_session"; break;
			case "ExamCenter": $join_key = "exam_center"; break;
			case "InterviewCenter": $join_key = "interview_center"; break;
		}
		// get list of partners
		$q = SQLQuery::create()
			->selectSubModel("SelectionCampaign", $campaign_id)
			->select($center_type."Partner")
			->whereValue($center_type."Partner", $join_key, $id)
			->field($center_type."Partner", $join_key, "partner_center_id")
			->field($center_type."Partner", "organization", "organization")
			->field($center_type."Partner", "host", "host")
			->field($center_type."Partner", "host_address", "host_address")
			;
		// join organization information
		PNApplication::$instance->contact->joinOrganization($q, $center_type."Partner", "organization");
		$partners = $q->execute();
		// get contacts, addresses, and contact points for all partners
		$orgs_ids = array();
		foreach ($partners as $p) array_push($orgs_ids, $p["organization"]);
		if (count($orgs_ids) > 0) {
			$contacts = PNApplication::$instance->contact->getOrganizationsContacts($orgs_ids);
			$addresses = PNApplication::$instance->contact->getOrganizationsAddresses($orgs_ids);
			$contact_points = PNApplication::$instance->contact->getOrganizationsContactPoints($orgs_ids);
			$selected_contact_points = SQLQuery::create()
				->select($center_type."ContactPoint")
				->whereIn($center_type."ContactPoint", "organization", $orgs_ids)
				->whereValue($center_type."ContactPoint", $join_key, $id)
				->field($center_type."ContactPoint", "organization", "organization_id")
				->field($center_type."ContactPoint", "people", "contact_point_id")
				->execute();
		} else {
			$contacts = array();
			$addresses = array();
			$contact_points = array();
			$selected_contact_points = array();
		}
		return self::PartnersJSON($partners, $contacts, $addresses, $contact_points, $selected_contact_points);
	}

	/**
	 * Get the PartnerJSON of the hosting partner of the given center
	 * @param string $center_type 'InformationSession', 'ExamCenter' or 'InterviewCenter'
	 * @param integer $id center id
	 * @return string the JSON
	 * @no_name_check
	 */
	public static function HostingPartnerFromCenterID($center_type, $id) {
		switch ($center_type) {
			case "InformationSession": $join_key = "information_session"; break;
			case "ExamCenter": $join_key = "exam_center"; break;
			case "InterviewCenter": $join_key = "interview_center"; break;
		}
		// get hosting partner
		$q = SQLQuery::create()
			->select($center_type."Partner")
			->whereValue($center_type."Partner", $join_key, $id)
			->whereValue($center_type."Partner", "host", 1)
			->field($center_type."Partner", $join_key, "partner_center_id")
			->field($center_type."Partner", "organization", "organization")
			->field($center_type."Partner", "host", "host")
			->field($center_type."Partner", "host_address", "host_address")
			;
		// join organization information
		PNApplication::$instance->contact->joinOrganization($q, $center_type."Partner", "organization");
		$host = $q->executeSingleRow();
		if ($host == null) return "null";
		// get contacts, addresses, and contact points for all partners
		$org_id = $host["organization"];
		$contacts = PNApplication::$instance->contact->getOrganizationsContacts(array($org_id));
		$addresses = PNApplication::$instance->contact->getOrganizationsAddresses(array($org_id));
		$contact_points = PNApplication::$instance->contact->getOrganizationsContactPoints(array($org_id));
		$selected_contact_points = SQLQuery::create()
			->select($center_type."ContactPoint")
			->whereValue($center_type."ContactPoint", "organization", $org_id)
			->whereValue($center_type."ContactPoint", $join_key, $id)
			->field($center_type."ContactPoint", "people", "contact_point_id")
			->executeSingleField();
		return self::PartnerJSON($host, $contacts, $addresses, $contact_points, $selected_contact_points);
	}
	
	/**
	 * Build the JSON array of the partners, with all the given information
	 * @param array $partners list of organizations
	 * @param array $contacts list of contacts for all organizations
	 * @param array $addresses list of postal addresses for all organizations
	 * @param array $contact_points list of contact points for all organizations
	 * @param array $selected_contact_points list of selected contact points
	 * @return string the JSON
	 */
	public static function PartnersJSON($partners, $contacts, $addresses, $contact_points, $selected_contact_points) {
		$s = "[";
		$first  =true;
		foreach ($partners as $partner) {
			if ($first) $first = false; else $s .= ",";
			$pcontacts = array();
			foreach ($contacts as $contact) if ($contact["contact_organization_id"] == $partner["organization"]) array_push($pcontacts, $contact);
			$paddresses = array();
			foreach ($addresses as $address) if ($address["address_organization_id"] == $partner["organization"]) array_push($paddresses, $address);
			$pcontactpoints = array();
			foreach ($contact_points as $cp) if ($cp["contact_point_organization_id"] == $partner["organization"]) array_push($pcontactpoints, $cp);
			$selected = array();
			foreach ($selected_contact_points as $scp) if ($scp["organization_id"] == $partner["organization"]) array_push($selected, $scp["contact_point_id"]);
			$s .= self::PartnerJSON($partner, $pcontacts, $paddresses, $pcontactpoints, $selected);
		}
		$s .= "]";
		return $s;
	}
	
	/**
	 * Build the JSON structure for the given partner
	 * @param array $partner the Organization
	 * @param array $contacts Contact rows
	 * @param array $addresses PostalAddress rows
	 * @param array $contact_points OrganizationContactPoint rows
	 * @param integer[] $selected_contact_points id of the selected contact points 
	 * @return string the JSON
	 */
	public static function PartnerJSON($partner, $contacts, $addresses, $contact_points, $selected_contact_points) {
		require_once("component/contact/ContactJSON.inc");
		$s = "{";
		$s .= "center_id:".$partner["partner_center_id"];
		$s .= ",organization:".ContactJSON::OrganizationJSON($partner, null, $contacts, $addresses, $contact_points);
		$s .= ",host:".($partner["host"] <> null && $partner["host"] == 1 ? "true" : "false");
		$s .= ",host_address_id:".json_encode($partner["host_address"]);
		$s .= ",selected_contact_points_id:";
		if ($selected_contact_points !== null) {
			$s .= "[";
			$first = true;
			foreach ($selected_contact_points as $scp) {
				if ($first) $first = false; else $s .= ",";
				$s .= $scp;
			}
			$s .= "]";
		} else
			$s .= "null";
		$s .= "}";
		return $s;
	}
}
?>