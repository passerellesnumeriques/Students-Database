<?phpclass service_get_organization_contact extends Service{	public function get_required_rights(){return array();}	public function input_documentation(){}//TODO	public function output_documentation(){}//TODO	public function documentation(){}//TODO	public function execute(&$component,$input){		require_once("get_contacts.inc");		$organization_id = $input['organization_id'];		get_contacts("Organization_contact", "organization", "Organization_address", "organization", $organization_id);	}}?>