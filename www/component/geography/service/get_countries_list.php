<?php
class service_get_countries_list extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){echo "No";}
	public function output_documentation(){
		?>
		Returns an array containing javascript objects:
		<ul>
			<li><code>country_id</code> the id of the country in the database</li>
			<li><code>country_code</code> the international country code</li>
			<li><code>country_name</code> the name of the country</li>
		</ul>
		<?php
	}
	public function documentation(){
		echo "Return an array of all the countries set into the database.<br/>First country of the list is the one of the user domain<br/>Next ones are first the PN countries, then all the others";
	}
	public function execute(&$component,$input){
		$countries = PNApplication::$instance->geography->getCountriesList();
		
		echo "[";
		$first = true;
		foreach($countries as $country){
			if(!$first) echo ", ";
			echo "{country_id:".json_encode($country["country_id"]).", ";
			echo "country_code:".json_encode($country["country_code"]).", ";
			echo "country_name:".json_encode($country["country_name"])."}";
			$first = false;		
		}
		echo "]";
	}
}