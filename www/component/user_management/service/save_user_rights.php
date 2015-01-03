<?php
class service_save_user_rights extends Service {
	
	public function documentation() {
		?>Save the list of rights associated with the given user.<?php
	}
	public function getRequiredRights() {
		return array("edit_user_rights");
	}
	public function inputDocumentation() {
	?>
	<ul>
		<li><code>user</code>: id of the user</li>
		<li><code>lock</code>: lock id</li>
		<li>list of rights to save: <code><i>right_name=right_value</i></code>
	</ul>
	<?php 
	}
	public function outputDocumentation() {
	?>return true on success.<?php 
	}
	public function execute(&$component, $input) {
		$user_id = $input["user"];
		
		// check data were locked before
		if (!isset($input["lock"]))  { PNApplication::error("missing lock"); return; }
		require_once("component/data_model/DataBaseLock.inc");
		if (DataBaseLock::checkLock($input["lock"], "UserRights", null, null) <> null) {
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
			foreach ($c->getReadableRights() as $cat) foreach ($cat->rights as $r) $all_rights[$r->name] = $r;
			foreach ($c->getWritableRights() as $cat) foreach ($cat->rights as $r) $all_rights[$r->name] = $r;
		}
		
		$rights = array();
		foreach ($input as $name=>$value) {
			if ($name == "user" || $name == "lock") continue;
			if (!isset($all_rights[$name])) {
				PNApplication::error("unknown right ".$name);
				return;
			}
			$rights[$name] = $all_rights[$name]->parseValue($value);
		}
		
		$component->assignUserRights($user_id, $rights);
		echo "true";
	}		
}
