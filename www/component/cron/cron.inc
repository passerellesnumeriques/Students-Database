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
			if (file_exists("data/cron/".$task->task_id)) {
				$last = filemtime("data/cron/".$task->task_id);
				if ((time()-$last+10)/60 < $task->every_minutes) continue;
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
		$end = microtime(true);
		$f = fopen("data/cron/tasks_time","w");
		fwrite($f, number_format($end-$start_cron,2));
		fclose($f);
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
	
}
?>