<?php 
class cron extends Component {

	public function getTasks() {
		require_once("component/cron/CronTask.inc");
		$tasks = array();
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi)
				if ($pi instanceof CronPlugin)
					$tasks = array_merge($tasks, $pi->getTasks());
		return $tasks;
	}
	public function getMaintenanceTasks() {
		require_once("component/cron/CronTask.inc");
		$tasks = array();
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi)
			if ($pi instanceof CronPlugin)
			$tasks = array_merge($tasks, $pi->getMaintenanceTasks());
		return $tasks;
	}
	
	public function executeTasks() {
		$start_cron = microtime(true);
		if (!file_exists("data/cron")) mkdir("data/cron");
		set_error_handler(function($severity, $message, $filename, $lineno) {
			if (error_reporting() == 0) return true;
			PNApplication::error("PHP Error: ".$message." in ".$filename.":".$lineno);
			return true;
		});
		foreach ($this->getTasks() as $task) {
			$this->executeCronTask($task);
		}
		$end = microtime(true);
		$f = fopen("data/cron/tasks_time","w");
		fwrite($f, number_format($end-$start_cron,2));
		fclose($f);
	}
	
	public function executeCronTask($task) {
		if (!file_exists("data/cron")) @mkdir("data/cron");
		if (file_exists("data/cron/".$task->task_id)) {
			$last = filemtime("data/cron/".$task->task_id);
			if ((time()-$last+10)/60 < $task->every_minutes) return;
		}
		@unlink("data/cron/".$task->task_id.".errors");
		$task_start = microtime(true);
		try {
			include($task->file);
		} catch (Exception $e) {
			PNApplication::error($e);
		}
		$task_end = microtime(true);
		$f = fopen("data/cron/".$task->task_id,"w");
		fwrite($f, number_format($task_end-$task_start,2));
		fclose($f);
		if (PNApplication::hasErrors()) {
			$f = fopen("data/cron/".$task->task_id.".errors","w");
			fwrite($f, "<ul>");
			foreach (PNApplication::$errors as $err)
				fwrite($f, "<li>".$err."</li>");
			fwrite($f, "</ul>");
			fclose($f);
			PNApplication::clearErrors();
		}
	}
	
	public function executeMaintenanceTasks() {
		$start_cron = microtime(true);
		if (!file_exists("data/cron")) mkdir("data/cron");
		set_error_handler(function($severity, $message, $filename, $lineno) {
			if (error_reporting() == 0) return true;
			PNApplication::error("PHP Error: ".$message." in ".$filename.":".$lineno);
			return true;
		});
		foreach ($this->getMaintenanceTasks() as $task) {
			@unlink("data/cron/".$task->task_id.".errors");
			$task_start = microtime(true);
			try {
				include($task->file);
			} catch (Exception $e) {
				PNApplication::error($e);
			}
			$task_end = microtime(true);
			$f = fopen("data/cron/".$task->task_id,"w");
			fwrite($f, number_format($task_end-$task_start,2));
			fclose($f);
			if (PNApplication::hasErrors()) {
				$f = fopen("data/cron/".$task->task_id.".errors","w");
				fwrite($f, "<ul>");
				foreach (PNApplication::$errors as $err)
					fwrite($f, "<li>".$err."</li>");
				fwrite($f, "</ul>");
				fclose($f);
				PNApplication::clearErrors();
			}
		}
		$end = microtime(true);
		$f = fopen("data/cron/maintenance_tasks_time","w");
		fwrite($f, number_format($end-$start_cron,2));
		fclose($f);
	}
	
	function getLastCronExecution() {
		if (!file_exists("data/cron/tasks_time")) return 0;
		$info = stat("data/cron/tasks_time");
		return $info["mtime"];
	}
	function lastCronHasErrors() {
		return file_exists("data/cron/cron_errors");
	}
	function cronTaskHasErrors($task_id) {
		return file_exists("data/cron/$task_id.errors");
	}

	function getLastCronMaintenanceExecution() {
		if (!file_exists("data/cron/maintenance_tasks_time")) return 0;
		$info = stat("data/cron/maintenance_tasks_time");
		return $info["mtime"];
	}
	function lastCronMaintenanceHasErrors() {
		return file_exists("data/cron/cron_maintenance_errors");
	}
	function cronMaintenanceTaskHasErrors($task_id) {
		return file_exists("data/cron/$task_id.errors");
	}
	
	function executeMostNeededCronTask() {
		$tasks = $this->getTasks();
		$most_task = null;
		$most_time = null;
		foreach ($tasks as $task) {
			if (!file_exists("data/cron/".$task->task_id)) { $most_task = $task; break; }
			$info = stat("data/cron/".$task->task_id);
			$time = $info["mtime"];
			$time = time()-$time;
			$time -= $task->every_minutes*60;
			if ($time < 300) continue; // less than 5 minutes
			if ($most_task == null || $time > $most_time) {
				$most_task = $task;
				$most_time = $time;
			}
		}
		if ($most_task == null) return;
		$this->executeCronTask($most_task);
	}
	
}
?>