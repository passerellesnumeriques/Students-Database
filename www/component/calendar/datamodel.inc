<?php 
/* @var $model DataModel */
$model->addTable("Calendar")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, false)
	->addString("color", 6, 6, true, false)
	->addString("icon", 200, 1, true, false)
	->addInteger("last_modification")
	->addString("type", 50, 1, false, false)
	->addString("data", 4096, 0, true, false)
	;
$model->addTable("UserCalendar")
	->addForeignKey("user", "Users", true, false, true)
	->addForeignKey("calendar", "Calendar", true, true, false)
	;
$model->addTable("CalendarRights")
	->addForeignKey("calendar", "Calendar", true, false, true)
	->addString("right_name", 100, 1, false, false)
	->addString("right_value")
	->addBoolean("writable", false)
	;
$model->addTable("UserCalendarConfiguration")
	->addForeignKey("calendar", "Calendar", true, false, true, false, false)
	->addForeignKey("user", "Users", true, false, true, false, false)
	->setKey(array("calendar","user"))
	->addString("color", 6, 6, true, false)
	->addBoolean("show", false)
	;
$model->addTable("CalendarEvent")
	->addPrimaryKey("id")
	->addForeignKey("calendar", "Calendar", true, false, true)
	->addString("uid", 255, 1, false, false)
	->addInteger("start")
	->addInteger("end")
	->addBoolean("all_day", false)
	->addInteger("last_modified")
	->addString("title", 1024, 0, false, false)
	->addString("description", 4096, 0, true, false)
	->addString("location_freetext", 1024, 0, true, false)
	// TODO allow to link to geography for location
	->addString("organizer", 1024, 0, true, false)
	->addEnum("participation", array("UNKNOWN","YES","NO","TENTATIVE","DELEGATE"), false, false)
	->addEnum("role", array("REQUESTED","OPTIONAL","FOR_INFO"), false, false)
	->addString("app_link", 1024, 0, true, false)
	->addString("app_link_name", 1024, 0, true, false)
	;
$model->addTable("CalendarEventAttendee")
	->addForeignKey("event", "CalendarEvent", true, false, true)
	->addString("attendee", 1024, 0, true, false)
	->addEnum("participation", array("UNKNOWN","YES","NO","TENTATIVE","DELEGATE"), false, false)
	->addEnum("role", array("REQUESTED","OPTIONAL","FOR_INFO"), false, false)
	;
$model->addTable("CalendarEventFrequency")
	->addForeignKey("event", "CalendarEvent", true, false, false)
	->addEnum("frequency", array("DAILY","WEEKLY","MONTHLY","YEARLY"))
	->addInteger("until", 32, null, null, true, false)
	->addInteger("count", 32, 1, null, true, false)
	->addInteger("interval", 32, 1, null, true, false)
	->addString("by_month", 0, 100, true, false)
	->addString("by_week_no", 0, 100, true, false)
	->addString("by_year_day", 0, 100, true, false)
	->addString("by_month_day", 0, 100, true, false)
	->addString("by_week_day", 0, 100, true, false)
	->addString("by_hour", 0, 100, true, false)
	->addString("by_setpos", 0, 100, true, false)
	->addString("week_start", 0, 2, true, false)
	;

class CalendarEventDateDataDisplay extends datamodel\DataDisplay {
	
	public function __construct($handler, $display_name, $event_foreign_key) {
		parent::__construct($handler);
		$this->display_name = $display_name;
		$this->event_foreign_key = $event_foreign_key;
	}
	
	private $display_name;
	private $event_foreign_key;
	
	public function getDisplayName() { return $this->display_name; }
	public function getHandledColumns() { return array(); }
	public function getTypedField($sub_model) {
		return array("field_timestamp","{show_time:false,data_is_seconds:true}");
	}
	public function isEditable() { return false; }
	public function getEditLocks($sub_model) { return array(); }
	public function isSortable() { return true; }
	public function isMandatory($sub_model) { return false; }
	
	public function buildSQL(&$q, $path, $filters) {
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		$alias = $q->getFieldAlias($table_alias, $this->event_foreign_key);
		if ($alias == null) {
			$alias = $q->generateFieldAlias();
			$q->field($table_alias, $this->event_foreign_key, $alias);
		}
		$result = array("data"=>null,"key"=>$alias);
		return $result;
	}
	
	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path, $filters) {
		$events_ids = array();
		foreach ($rows as &$row) {
			if ($row[$sql_aliases["key"]] == null) {
				$row[$sql_aliases["data"]] = null;
				continue;
			}
			if (!in_array($row[$sql_aliases["key"]], $events_ids))
				array_push($events_ids, $row[$sql_aliases["key"]]);
		}
		if (count($events_ids) == 0) return;
		$events = SQLQuery::create()->bypassSecurity()->select("CalendarEvent")->whereIn("id",$events_ids)->field("CalendarEvent","calendar")->field("CalendarEvent","id")->field("CalendarEvent","start")->execute();
		$accessible_calendars = PNApplication::$instance->calendar->getAccessibleCalendars();
		foreach ($rows as &$row) {
			if ($row[$sql_aliases["key"]] == null) continue;
			foreach ($events as $event) {
				if ($event["id"] == $row[$sql_aliases["key"]]) {
					if (in_array($event["calendar"], $accessible_calendars))
						$row[$sql_aliases["data"]] = $event["start"];
					else
						$row[$sql_aliases["data"]] = null;
					break;
				}
			}
		}
		// TODO filters
	}
	
	public function getData($key, $sub_model, $row = null, $key_alias = null, $value_alias = null) {
		return null; // TODO
	}
	public function saveData($key, $value, $sub_model) {
		// TODO 
	}
	
}
?>