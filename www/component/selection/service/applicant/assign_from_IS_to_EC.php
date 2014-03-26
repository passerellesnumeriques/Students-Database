<?php 
class service_applicant_assign_from_IS_to_EC extends Service {
	
	public function get_required_rights() {return array("can_access_selection_data");}
	
	public function documentation() {echo 'Assign applicants to an exam center from the informations sessions linked to this center';}
	public function input_documentation() {echo "<code>EC_id</code> number the exam center ID";}
	public function output_documentation() {
		?>
		Object containing two attributes:
			<ul>
			  <li> <code>res</code> {Boolean} true if well performed</li>
			  <li> <code>assigned</code> {NULL|array} NULL if no applicant was assigned, else array of {Applicant} objects</li>
			</ul>

		<?php
	}
	
	public function execute(&$component, $input) {
		if(isset($input["EC_id"])){
			require_once 'component/selection/SelectionJSON.inc';
			$res = $component->assignApplicantsFromISToEC($input["EC_id"]);
			if($res == null)
				echo "{res:true, assigned:null}";
			else {
				$first = true;
				$json = "[";
				foreach($res as $applicant){
					if(!$first) $json .= ", ";
					$first = false;
					$json .= SelectionJSON::Applicant(null, $applicant);
				}
				$json .= "]";
				echo "{res:true, assigned:".$json."}";
			}
		} else 
			echo 'false';
	}
	
}
?>