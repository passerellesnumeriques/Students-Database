<?php 
class notes extends Component {
	
	public function getReadAccess($table, $key, $sub_model, $sub_model_instance) {
		// first, try if we can read
		try {
			$q = SQLQuery::create();
			if ($sub_model <> null) $q->selectSubModel($sub_model, $sub_model_instance);
#DEV
			$q->noWarning();
#END
			$q->select($table);
			$q->whereKey($table, $key);
			$item = $q->executeSingleRow();
			if ($item == null)
				$can_read = false;
			else
				$can_read = true;
		} catch (Exception $e) {
			$can_read = false;
		}
		return $can_read;
	}
	public function getWriteAccess($table, $key, $sub_model, $sub_model_instance) {
		try {
			$q = SQLQuery::create();
			if ($sub_model <> null) $q->selectSubModel($sub_model, $sub_model_instance);
#DEV
			$q->noWarning();
#END
			$q->select($table);
			$q->whereKey($table, $key);
			require_once("component/data_model/Model.inc");
			$t = DataModel::get()->getTable($table);
			$t->prepareSelectToDetermineWriteAccess($q, $table);
			$item = $q->executeSingleRow();
			if ($item == null)
				$can_write = false;
			else
				$can_write = true;
		} catch (Exception $e) {
			$can_write = false;
		}
		return $can_write;
	}
	
}
?>