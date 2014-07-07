<?php
class service_get_countries_list extends Service{
	public function getRequiredRights(){return array();}
	public function inputDocumentation(){echo "No";}
	public function outputDocumentation(){
		?>
		Returns an array containing javascript objects:
		<ul>
			<li><code>country_id</code> the id of the country in the database</li>
			<li><code>country_code</code> the international country code</li>
			<li><code>country_name</code> the name of the country</li>
			<li><code>north</code></li>
			<li><code>west</code></li>
			<li><code>south</code></li>
			<li><code>east</code></li>
		</ul>
		<?php
	}
	public function documentation(){
		echo "Return an array of all the countries set into the database.<br/>First country of the list is the one of the user domain<br/>Next ones are first the PN countries, then all the others";
	}
	public function execute(&$component,$input){
		$countries = PNApplication::$instance->geography->getCountriesList();
		echo json_encode($countries);
	}
}