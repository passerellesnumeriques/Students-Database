<?php 
require_once("component/cron/CronTask.inc");
class StorageCronPlugin implements CronPlugin {
	
	public function getTasks() {
		return array(
			new CronTask("expired_storage","Remove expired temporary uploaded files",5,"component/storage/cron_task_expired_storage.inc")
		);
	}
	
	public function getMaintenanceTasks() {
		return array(
			new CronMaintenanceTask("cleanup_storage","Remove unused uploaded files","component/storage/cron_task_cleanup_storage.inc")
		);
	}
	
}
?>