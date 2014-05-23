<?php 
class test extends Component {

	public function initRequest() {
		Component::allowPageWithoutLogin("test", "home");
		Component::allowPageWithoutLogin("test", "ui");
		Component::allowServiceWithoutLogin("test", "get_tests");
		Component::allowServiceWithoutLogin("test", "execute_functionalities_scenario");
		Component::allowServiceWithoutLogin("test", "services_scenario_init_db");
		Component::allowServiceWithoutLogin("test", "services_scenario_step_init");
		Component::allowServiceWithoutLogin("test", "services_scenario_step_check_output");
		Component::allowServiceWithoutLogin("test", "services_scenario_step_finalize");
		Component::allowServiceWithoutLogin("test", "ui_scenario_init_db");
	}
	
}
?>