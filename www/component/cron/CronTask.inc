<?php 
class CronTask {
	
	public $task_id;
	public $task_name;
	public $every_minutes;
	public $file;
	
	public function __construct($task_id, $task_name, $every_minutes, $file) {
		$this->task_id = $task_id;
		$this->task_name = $task_name;
		$this->every_minutes = $every_minutes;
		$this->file = $file;
	}
	
}

class CronMaintenanceTask {
	
	public $task_id;
	public $task_name;
	public $file;

	public function __construct($task_id, $task_name, $file) {
		$this->task_id = $task_id;
		$this->task_name = $task_name;
		$this->file = $file;
	}
	
}

interface CronPlugin {
	
	public function getTasks();
	public function getMaintenanceTasks();
	
}
?>