function Test_user_management_LoginLogoutUI(data) {
	return [
	  ["Start Browser","start"],
	  ["Wait login screen","wait_element_id","login_table"],
	  ["Set Domain","execute_code","document.forms['login_form'].elements['domain'].value = 'Test';"],
	  ["Set Username","execute_code","document.forms['login_form'].elements['username'].value = 'test_user';"],
	  ["Login","execute_code","login();"],
	  ["Enter application","wait_element_id","pn_application_container"],
	  ["Check interface","user_check","Please check the interface is ok, then press the play button"],
	  ["Click on user menu","click_element_id","user_menu"],
	  ["Wait context menu","sleep","1000"],
	  ["Click on logout","click_element_id","logout"],
	  ["Wait logout and go back to login screen","wait_element_id","login_table"],
	];
}