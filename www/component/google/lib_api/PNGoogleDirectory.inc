<?php 
require_once("PNGoogle.inc");
class PNGoogleDirectory extends PNGoogle_Admin {
	
	/** @var Google_Service_Directory */
	protected $service;
	
	public function __construct() {
		parent::__construct(array('https://www.googleapis.com/auth/admin.directory.group.readonly','https://www.googleapis.com/auth/admin.directory.orgunit.readonly','https://www.googleapis.com/auth/admin.directory.user.readonly'));
		// connect to Google Directory service
		require_once("Google/Service/Directory.php");
		$this->service = new Google_Service_Directory($this->client);
	}
	
	public function getAllOrganizations() {
		$list = $this->service->orgunits->listOrgunits("my_customer",array("type"=>"all"));
		return $list->getOrganizationUnits();
	}
	
	public function getAllUsers() {
		$users = array();
		$list = $this->service->users->listUsers(array("customer"=>"my_customer","maxResults"=>500));
		while(true) {
			foreach ($list->getUsers() as $u) array_push($users, $u);
			$pageToken = $list->getNextPageToken();
			if ($pageToken)
				$list = $this->service->users->listUsers(array("customer"=>"my_customer","maxResults"=>500,"pageToken"=>$pageToken));
			else
				break;
		}
		return $users;
	}
	
	public function getHierarchy() {
		$orgs = $this->getAllOrganizations();
		$users = $this->getAllUsers();
		$root = array();
		$this->_hierarchy($orgs, $users, $root, "");
		return $root;
	}
	private function _hierarchy($orgs, $users, &$org, $org_path) {
		// users
		$org["users"] = array();
		foreach ($users as $u) {
			if ($u->orgUnitPath <> $org_path) continue;
			array_push($org["users"], $u);
		}
		// sub-organizations
		$org["sub_organizations"] = array();
		foreach ($orgs as $o) {
			if ($o->orgUnitPath <> $org_path."/".$o->name) continue;
			$so = array();
			$so["org"] = $o;
			$this->_hierarchy($orgs, $users, $so, $org_path."/".$o->name);
			array_push($org["sub_organizations"], $so);
		}
	}
}
?>