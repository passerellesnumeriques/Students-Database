<?php 
class documents extends Component {

	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "StoragePlugin":
				require_once("DocumentsStorage.inc");
				return array(
					new DocumentsStorage()
				);
		}
		return array();
	}
	
	public function getAttachedDocumentsPlugin($table, $type) {
		require_once("DocumentsPlugin.inc");
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations("AttachedDocumentsPlugin") as $pi)
				if ($pi->getTable() == $table && $pi->getType() == $type)
					return $pi;
		return null;
	}
	
	public function insertDivForAttachedDocuments(&$page, $table, $sub_model, $key, $type, $div_size, $div_orientation) {
		$pi = $this->getAttachedDocumentsPlugin($table, $type);
		if ($pi == null) {
			PNApplication::error("Invalid table or type for documents");
			return;
		}
		if (!$pi->canRead($key, $sub_model)) return;
		$id = $page->generateID();
		echo "<div id='$id'></div>";
		$page->requireJavascript("documents.js");
		theme::css($page, "documents.css");
		$page->requireJavascript("animation.js");
		$page->onload("new AttachedDocuments('$id','$table',".json_encode($sub_model).",".json_encode($key).",'$type',".($pi->canAddAndRemove($key, $sub_model) ? "true" : "false").",".($pi->canWrite($key, $sub_model) ? "true" : "false").",'$div_size','$div_orientation');");
	}
	
}
?>