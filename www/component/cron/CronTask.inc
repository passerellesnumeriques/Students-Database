<?php 
/**
 * Define a task to be executed regularly
 */
class CronTask {
	
	/** @var string $task_id identifier */
	public $task_id;
	/** @var string $task_name name */
	public $task_name;
	/** @var integer $every_minutes number of minutes */
	public $every_minutes;
	/** @var string $file path of the file to be executed */
	public $file;
	
	/**
	 * @param string $task_id identifier
	 * @param string $task_name name
	 * @param integer $every_minutes number of minutes
	 * @param string $file path of the file to be executed
	 */
	public function __construct($task_id, $task_name, $every_minutes, $file) {
		$this->task_id = $task_id;
		$this->task_name = $task_name;
		$this->every_minutes = $every_minutes;
		$this->file = $file;
	}
	
}

/**
 * Define a task to be executed with maintenance mode
 */
class CronMaintenanceTask {
	
	/** @var string $task_id identifier */
	public $task_id;
	/** @var string $task_name name */
	public $task_name;
	/** @var string $file path of the file to be executed */
	public $file;

	/**
	 * @param string $task_id identifier
	 * @param string $task_name name
	 * @param string $file path of the file to be executed
	 */
	public function __construct($task_id, $task_name, $file) {
		$this->task_id = $task_id;
		$this->task_name = $task_name;
		$this->file = $file;
	}
	
}

/**
 * Interface to implement to provide tasks to be executed by cron
 */
interface CronPlugin {
	
	/** List of tasks to execute
	 * @return CronTask[]
	 */
	public function getTasks();
	/** List of maintenance tasks to execute
	 * @return CronMaintenanceTask[]
	 */
	public function getMaintenanceTasks();
	
}
?>