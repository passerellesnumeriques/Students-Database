<?php
class service_save_user_rights extends Service {
	
	public function documentation() {
		?>Save the list of rights associated with the given user.<?php
	}
	public function get_required_rights() {
		return array("edit_user_rights");
	}
	public function input_documentation() {
	?>
	<ul>
		<li><code>user</code>: id of the user</li>
		<li><code>lock</code>: lock id</li>
		<li>list of rights to save: <code><i>right_name=right_value</i></code>
	</ul>
	<?php 
	}
	public function output_documentation() {
	?>return true on success.<?php 
	}
	public function execute(&$component, $input) {
		$user_id = $input["user"];
		
		// check data were locked before
		if (!isset($_GET["lock"]))  { PNApplication::error("missing lock"); return; }
		require_once("component/data_model/DataBaseLock.inc");
		if (!DataBaseLock::checkLock($_GET["lock"], "UserRights", null, null)) {
			PNApplication::error("You do not have the data locked, meaning you cannot modify them. This may be due to a long inactivity. Please refresh the page and try again");
			return;
		}
		
		$r = SQLQuery::create()->select("Users")->field("username")->where("id",$user_id);
		if ($r == null || count($r) == 0) {
			PNApplication::error("unknown user");
			return;
		}
		
		// retrieve all possible rights
		$all_rights = array();
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->get_readable_rights() as $cat) foreach ($cat->rights as $r) $all_rights[$r->name] = $r;
			foreach ($c->get_writable_rights() as $cat) foreach ($cat->rights as $r) $all_rights[$r->name] = $r;
		}
		
		$rights = array();
		foreach ($input as $name=>$value) {
			if ($name == "user") continue;
			if (!isset($all_rights[$name])) {
				PNApplication::error("unknown right ".$name);
				return;
			}
			$rights[$name] = $all_rights[$name]->parse_value($value);
		}
		
		$component->assign_user_rights($user_id, $rights);
		echo "true";
	}		
}
