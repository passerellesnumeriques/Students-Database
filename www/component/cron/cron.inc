<?php
/**
 * Component to execute tasks regularly.
 * Two types of tasks exist: normal, executed typically every few minutes, and maintenance, which require the software to be in maintenance mode, and which are typically executed during the night
 */ 
class cron extends Component {

	/** Get the list of tasks
	 * @return CronTask[] list of tasks
	 */
	public function getTasks() {
		require_once("component/cron/CronTask.inc");
		$tasks = array();
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi)
				if ($pi instanceof CronPlugin)
					$tasks = array_merge($tasks, $pi->getTasks());
		return $tasks;
	}
	/** Get the list of maintenance tasks
	 * @return CronMaintenanceTask[] list of tasks
	 */
	public function getMaintenanceTasks() {
		require_once("component/cron/CronTask.inc");
		$tasks = array();
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi)
			if ($pi instanceof CronPlugin)
			$tasks = array_merge($tasks, $pi->getMaintenanceTasks());
		return $tasks;
	}
	
	/** Executes the needed tasks (according to last time of execution, and the <code>every_minutes</code> parameter of the task) */
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
	
	/** Execute the given task
	 * @param CronTask $task the task to execute
	 */
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
	
	/** Execute all maintenance tasks */
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
	
	/** Get the last time cron tasks have been executed by a scheduled task on the system
	 * @return integer timestamp
	 */
	function getLastCronExecution() {
		if (!file_exists("data/cron/tasks_time")) return 0;
		$info = stat("data/cron/tasks_time");
		return $info["mtime"];
	}
	/** Indicates if last execution of cron encounter errors which avoid to execute all tasks correctly
	 * @return boolean true in case of errors
	 */
	function lastCronHasErrors() {
		return file_exists("data/cron/cron_errors");
	}
	/** Indicates if the given task encountered errors during its last execution
	 * @param string $task_id the id of the task
	 * @return boolean true in case of errors
	 */
	function cronTaskHasErrors($task_id) {
		return file_exists("data/cron/$task_id.errors");
	}

	/** Get the last time maintenance tasks have been executed by a scheduled task on the system
	 * @return integer timestamp
	 */
	function getLastCronMaintenanceExecution() {
		if (!file_exists("data/cron/maintenance_tasks_time")) return 0;
		$info = stat("data/cron/maintenance_tasks_time");
		return $info["mtime"];
	}
	/** Indicates if last execution of cron_maintenance encounter errors which avoid to execute all tasks correctly
	 * @return boolean true in case of errors
	 */
	function lastCronMaintenanceHasErrors() {
		return file_exists("data/cron/cron_maintenance_errors");
	}
	/** Indicates if the given task encountered errors during its last execution
	 * @param string $task_id the id of the task
	 * @return boolean true in case of errors
	 */
	function cronMaintenanceTaskHasErrors($task_id) {
		return file_exists("data/cron/$task_id.errors");
	}
	
	/**
	 * Execute the task which need more (difference between last time it should have been executed, and now).
	 * This is only used if we detect that cron tasks are not executed automatically. In that case, when we receive
	 * a ping of a user, we randomly try to execute a task.
	 */
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