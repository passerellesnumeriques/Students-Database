<?php 
require_once("component/cron/CronTask.inc");
class GoogleCronPlugin implements CronPlugin {
	
	public function getTasks() {
		return array(
			new CronTask("synch_google_calendars","Synchronize Calendars List with Google",15,"component/google/cron_task_synch_calendars.inc"),
			new CronTask("synch_google_calendars_events","Synchronize Calendars Events with Google",2,"component/google/cron_task_synch_calendars_events.inc")
		);
	}
	
	public function getMaintenanceTasks() {
		return array(
		);
	}
	
}
?>