<?php 
require_once("component/cron/CronTask.inc");
/**
 * Declares a cron task to remove expired locks in the database
 */
class DataModelCronPlugin implements CronPlugin {
	
	public function getTasks() {
		return array(
			new CronTask("clean_datalocks", "Remove expired database locks", 2, "component/data_model/cron_task_remove_expired_locks.inc")
		);
	}
	
	public function getMaintenanceTasks() {
		return array();
	}
	
}
?>