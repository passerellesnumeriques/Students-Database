<?php 
class cron extends Component {
	
	public function registerTask($task_id, $minutes, $file) {
		global $in_cron;
		if (!$in_cron) return;
		global $cron_tasks;
		array_push($cron_tasks, array($task_id, $minutes, $file));
	}
	
	public function registerMaintenanceTask($file) {
		global $in_cron_maintenance;
		if (!$in_cron_maintenance) return;
		global $cron_maintenance_tasks;
		array_push($cron_maintenance_tasks, array($task_id, $file));
	}
	
	public function executeTasks() {
		if (!file_exists("data/cron")) mkdir("data/cron");
		$now = time();
		global $cron_tasks;
		foreach ($cron_tasks as $task) {
			if (file_exists("data/cron/".$task[0])) {
				$last = filemtime("data/cron/".$task[0]);
				if (($now-$last)/60 < $task[1]) continue;
			}
			include($task[2]);
			$f = fopen("data/cron/".$task[0],"w");
			fwrite($f, "done at ".time());
			fclose($f);
		}
	}
	
	public function executeMaintenanceTasks() {
		global $cron_maintenance_tasks;
		foreach ($cron_maintenance_tasks as $task) {
			include($task);
		}
	}
	
}
?>