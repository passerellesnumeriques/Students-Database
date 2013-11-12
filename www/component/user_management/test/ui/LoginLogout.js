function Test_user_management_LoginLogout() {
	
	this.name = "Login / Logout";
	
	this.run = function(ondone, onaction) {
		var actions = [
		  ["Start Browser","start"],
		  ["Wait login screen","wait_element_id","login_table"],
		  ["Set Domain","execute_code","document.forms['login_form'].elements['domain'].value = 'Dev';"],
		  ["Set Username","execute_code","document.forms['login_form'].elements['username'].value = 'guillaume.le-cousin';"],
		  ["Login","execute_code","login();"],
		  ["Enter application","wait_element_id","pn_application_container"],
		  ["Click on user menu","click_element_class","pn_application_header_section"],
		  ["Wait context menu","sleep","1000"],
		  ["Click on logout","click_element_id","user_logout_menu"],
		  ["Wait logout and go back to login screen","wait_element_id","login_table"],
		];
		browser_control.run(ondone, actions, onaction);
	};
	
}