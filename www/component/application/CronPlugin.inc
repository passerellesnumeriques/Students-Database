<?php 
require_once("component/cron/CronTask.inc");
class ApplicationCronPlugin implements CronPlugin {
	
	public function getTasks() {
		return array(
			new CronTask("synch_domains", "Partially download data (if needed) from other domains using remote access", 5, "component/application/cron_task_synch_domains.inc")
		);
	}
	
	public function getMaintenanceTasks() {
		return array(
			new CronMaintenanceTask("local_backup","Backup data (and remove old backups if more than 30)","component/application/cron_task_backup.inc"),
			new CronMaintenanceTask("synch_domains_recover","Synchronize data of other domains","component/application/cron_task_synch_domains_recover.inc")
		);
	}
	
}
?>