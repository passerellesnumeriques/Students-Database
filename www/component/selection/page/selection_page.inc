<?php
abstract class selection_page extends Page{
	//we don't define get_required_rights this way we will be obliged to define it in selection_page instances
	final public function execute(){
		$id = PNApplication::$instance->components["selection"]->get_campaign_id();
		if($id <> null) $this->execute_selection_page($this);
		else echo "<div>You must select a selection campaign</div>";
	}
	
	abstract public function execute_selection_page(&$page);
}
?>