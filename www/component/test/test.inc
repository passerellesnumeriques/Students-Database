<?php 
class test extends Component {

	public function initRequest() {
		Component::allow_page_without_login("test", "home");
		Component::allow_page_without_login("test", "ui");
		Component::allow_service_without_login("test", "get_tests");
		Component::allow_service_without_login("test", "execute_functionalities_scenario");
		Component::allow_service_without_login("test", "services_scenario_init_db");
		Component::allow_service_without_login("test", "services_scenario_step_init");
		Component::allow_service_without_login("test", "services_scenario_step_check_output");
		Component::allow_service_without_login("test", "services_scenario_step_finalize");
		Component::allow_service_without_login("test", "ui_scenario_init_db");
	}
	
}
?>