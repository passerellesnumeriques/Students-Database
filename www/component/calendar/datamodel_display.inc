<?php 
class CalendarEventDateDataDisplay extends datamodel\DataDisplay {

	public function __construct($display_name, $event_foreign_key) {
		$this->display_name = $display_name;
		$this->event_foreign_key = $event_foreign_key;
	}

	private $display_name;
	private $event_foreign_key;

	public function getDisplayName() { return $this->display_name; }
	public function getHandledColumns() { return array(); }
	public function getTypedFieldInList($sub_model) {
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

	public function getData($row, $resultFromBuildSQL) {
		return null; // TODO
	}
	public function saveData($key, $value, $sub_model, &$tables_fields) {
		// TODO
	}

}
?>