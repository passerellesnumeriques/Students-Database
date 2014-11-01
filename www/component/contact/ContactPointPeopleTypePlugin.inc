<?php 
require_once("component/people/PeopleTypePlugin.inc");
require_once("OrganizationPlugin.inc");
class ContactPointPeopleTypePlugin implements PeopleTypePlugin {
	
	/** @var OrganizationPlugin $org_plugin */
	private $org_plugin;
	/**
	 * @param OrganizationPlugin $org_plugin
	 */
	public function __construct($org_plugin) {
		$this->org_plugin = $org_plugin;
	}
	
	public function getSecurityPriority() { return 1000; }
	
	public function getId() { return "contact_".$this->org_plugin->getOrganizationCreator(); }
	public function getName() { return "Contact Point"; }
	public function getIcon16() { return "/static/contact/contact_point.png"; }
	public function getIcon32() { return "/static/contact/contact_point_32.png"; }
	public function isStandalone() { return false; }
	
	public function getTables() { return array("ContactPoint"); }
	
	public function canRead() {
		return $this->org_plugin->canReadOrganization();
	}
	public function canWrite() {
		return $this->org_plugin->canWriteOrganization();
	}
	public function canRemove() {
		return $this->org_plugin->canRemoveOrganization();
	}
	
	public function getPeopleRowDescription($people) {
		$p = SQLQuery::create()->bypassSecurity()
			->select("ContactPoint")
			->whereValue("ContactPoint", "people", $people["id"])
			->field("ContactPoint", "designation")
			->join("ContactPoint", "Organization", array("organization"=>"id"))
			->field("Organization", "name")
			->executeSingleRow();
		if ($p == null) return null;
		return "contact point of ".$p["name"]." (".$p["designation"].")";
	}
	
	public function getBuiltinDefaultUserRoles($people) { return array(); }
	
	public function remove($people_id) {
		$rows = SQLQuery::create()->bypassSecurity()->select("ContactPoint")->whereValue("ContactPoint","people",$people_id)->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("ContactPoint", $rows);
	}
}
?>