<?phpclass service_get_people_contact extends Service{	public function get_required_rights(){return array();}	public function input_documentation(){}//TODO	public function output_documentation(){}//TODO	public function documentation(){}//TODO	public function execute(&$component,$input){		require_once("get_contacts.inc");		$people_id = $input['people_id'];		get_contacts("People_contact", "people", "People_address", "people", $people_id);	}}?>