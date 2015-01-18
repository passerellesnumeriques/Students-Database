<?php
/**
 * Component providing functionalities to import data from Excel files or other format, into the data model
 */ 
class data_import extends Component {

	public function getTemplatePlugin($type) {
		require_once("DataImportTemplatePlugin.inc");
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations("DataImportTemplatePlugin") as $pi)
				if ($pi->getId() == $type)
					return $pi;
		return null;
	}
	
	public function getTemplates($type, $sub_model = null) {
		$pi = $this->getTemplatePlugin($type);
		if ($pi == null) { PNApplication::error("Invalid template type ".$type); return array(); }
		if (!$pi->canRead()) return array();
		return SQLQuery::create()->bypassSecurity()
			->select("DataImportTemplate")
			->whereValue("DataImportTemplate","type",$type)
			->whereValue("DataImportTemplate","sub_model",$sub_model)
			->execute();
	}
	
}
?>