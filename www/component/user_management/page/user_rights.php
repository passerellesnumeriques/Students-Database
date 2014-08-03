<?php
class page_user_rights extends Page {
	
	public function getRequiredRights() { return array("consult_user_rights"); }
	
	public function execute() {
// get the user we need to display
$user_id = $_GET["user"];

// check the current user has the right to edit
$can_edit = PNApplication::$instance->user_management->has_right("edit_user_rights");

// if the user can edit, we need to lock the data, so another user will not modify the data at the same time
$locked = null;
if ($can_edit) {
	require_once("component/data_model/DataBaseLock.inc");
	$lock_id = DataBaseLock::lockTable("UserRights", $locked);
	if ($locked <> null)
		$can_edit = false;
}

// get info about the user
$user = SQLQuery::create()->select("Users")->where("id", $user_id)->executeSingleRow();

// get roles of the user
$roles = SQLQuery::create()->select("UserRole")->field("UserRole","role")->join("UserRole","Role",array("role"=>"id"))->field("Role","name")->where("user",$user_id)->execute();
if (!is_array($roles)) $roles = array();

// check if the user is an administrator
$is_admin = false;
foreach ($roles as $role)
	if ($role["role"] == -1) {
		$is_admin = true;
		break;
	}

if ($can_edit)
	DataBaseLock::generateScript($lock_id);
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;overflow:hidden;position:absolute;top:0px;left:0px;'>
<div class='page_title' style='flex:none'>
	<img src='/static/user_management/access_list_32.png'/>
	User Access Rights: <span style='font-family:Courrier New;font-weight:bold;font-style:italic'><?php echo $user["domain"]."\\".$user["username"];?></span>
</div>
<div style='background-color:white;flex:1 1 auto;overflow:auto;'>
<?php 
if ($locked <> null)
	echo "<img src='".theme::$icons_16["lock"]."'/> This page is already locked by ".$locked."<br/>";

if ($is_admin) {
	echo "<div style='padding:10px'>This user is an administrator, it has the right to do everything</div>";
} else {

// retrieve all existing rights, and categories
$all_rights = array();
$categories = array();
$right_type = array();
foreach (PNApplication::$instance->components as $component) {
	foreach ($component->getReadableRights() as $cat) {
		if (!isset($categories[$cat->display_name]))
			$categories[$cat->display_name] = array();
		foreach ($cat->rights as $r) {
			array_push($categories[$cat->display_name], $r);
			$all_rights[$r->name] = $r;
			$right_type[$r->name] = "readable";
		}
	}
	foreach ($component->getWritableRights() as $cat) {
		if (!isset($categories[$cat->display_name]))
			$categories[$cat->display_name] = array();
		foreach ($cat->rights as $r) {
			array_push($categories[$cat->display_name], $r);
			$all_rights[$r->name] = $r;
			$right_type[$r->name] = "writable";
		}
	}
}

// get rights directly attached to the user
$rights = SQLQuery::create()->select("UserRights")->field("right")->field("value")->where("user",$user_id)->execute();
if (!is_array($rights)) $rights = array();
$user_rights = array();
foreach ($rights as $r) $user_rights[$r["right"]] = $all_rights[$r["right"]]->parse_value($r["value"]);
// get rights for each role
$role_rights = array();
foreach ($roles as $role) {
	$rights = SQLQuery::create()->select("RoleRights")->field("right")->field("value")->where("role", $role["role"])->execute();
	if (!is_array($rights)) $rights = array();
	$a = array();
	foreach ($rights as $r) $a[$r["right"]] = $all_rights[$r["right"]]->parse_value($r["value"]);
	array_push($role_rights, $a);
}
// compute final for user
$final = array();
// 1- from user
foreach ($user_rights as $name=>$value) $final[$name] = $value;
// 2- from roles
foreach ($role_rights as $rights)
	foreach ($rights as $name=>$value)
		if (!isset($final[$name]))
			$final[$name] = $value;
		else
			$final[$name] = $all_rights[$name]->get_higher_value($value, $final[$name]);
// 3- from implications
PNApplication::$instance->user_management->compute_rights_implications($final, $all_rights);

/** Generate a field according to the type of the right: for example, a checkbox for a boolean right */
function generate_right($prefix, $right, $value, $readonly = true, $visible = true) {
	if ($right instanceof BooleanRight) {
		echo "<input type='checkbox' name='".$prefix.$right->name."'";
		if ($value) echo " checked='checked'";
		if ($readonly) echo " disabled='disabled'";
		echo " style='".($visible ? "visibility:visible;position:static" : "visibility:hidden;position:absolute")."'";
		echo " onchange=\"if (this.checked == ".($value ? "true" : "false").") pnapplication.dataSaved('right_".$prefix.$right->name."'); else pnapplication.dataUnsaved('right_".$prefix.$right->name."');\"";
		echo "/>";
	} else
		echo "unknown right type";
}

// print the table of rights
echo "<form name='um_rights' onsubmit='return false'>";
echo "<table rules=all cellspacing=0 cellpadding=2>";
$roles_cols = count($roles);
if ($roles_cols == 0) { $roles_cols = 1; echo "<th></th>"; }
echo "<tr><th colspan=2 rowspan=2>Right</th><th rowspan=2>Access</th><th rowspan=2>Attached to user</th><th colspan=".$roles_cols.">Inherited from roles</th></tr>";
echo "<tr>";
foreach ($roles as $role)
	echo "<th>".$role["name"]."</th>";
echo "</tr>";
foreach ($categories as $cat_name=>$rights) {
	echo "<tr><td colspan=".(5+$roles_cols)." class='category_title'>".$cat_name."</td></tr>";
	foreach ($rights as $r) {
		echo "<tr class='".$right_type[$r->name]."_right'>";
		echo "<td width='10px'></td>";
		echo "<td>".$r->display_name."</td>";
		echo "<td>";
		generate_right("final_", $r, @$final[$r->name]);
		echo "</td>";
		echo "<td>";
		if (isset($user_rights[$r->name]) || !isset($final[$r->name]) || !$r->is_highest($final[$r->name])) {
			generate_right("user_", $r, isset($user_rights[$r->name]) ? $user_rights[$r->name] : @$final[$r->name], !$can_edit, isset($user_rights[$r->name]));
			if ($can_edit) {
				echo "<img src='".theme::$icons_16["remove"]."' id='remove_".$r->name."' onclick=\"um_rights_remove('".$r->name."',".(isset($user_rights[$r->name])?"true":"false").");\" style='".(isset($user_rights[$r->name]) ? "visibility:visible;position:static" : "visibility:hidden;position:absolute")."'/>";
				echo "<img src='".theme::$icons_16["add"]."' id='add_".$r->name."' onclick=\"um_rights_add('".$r->name."',".(isset($user_rights[$r->name])?"true":"false").");\" style='".(isset($user_rights[$r->name]) ? "visibility:hidden;position:absolute" : "visibility:visible;position:static")."'/>";
			}
		}
		echo "</td>";
		if (count($roles) == 0)
			echo "<td></td>";
		else
			foreach ($role_rights as $role) {
				echo "<td>";
				if (isset($role[$r->name])) generate_right("role_", $r, $role[$r->name]);
				echo "</td>";
			}
		echo "</tr>";
	}
}
echo "</table></form>";
?>
<style type='text/css'>
table {
	margin: 5px;
	border: 1px solid black;
}
tr td:first-of-type {
	border-right: 0px;
	text-align: left;
}
tr td:first-of-type+td {
	border-left: 0px;
	text-align: left;
}
tr td {
	text-align: center;
}
th {
	background-color: #C0C0C0;
}
td.category_title {
	background-color: #D0D0FF;
	font-weight: bold;
}
img {
	cursor: pointer;
}
tr.readable_right {
	background-color: #D8FFD8;
}
tr.writable_right {
	background-color: #FFF0D8;
}
</style>
<?php if ($can_edit) {?>
<script type='text/javascript'>
function um_rights_add(name, was_set) {
	var input = document.forms["um_rights"].elements["user_"+name];
	var add = document.getElementById("add_"+name);
	var remove = document.getElementById("remove_"+name);
	input.style.visibility = "visible";
	input.style.position = "static";
	add.style.visibility = "hidden";
	add.style.position = "absolute";
	remove.style.visibility = "visible";
	remove.style.position = "static";
	if (was_set) pnapplication.dataSaved('add_right_'+name); else pnapplication.dataUnsaved('add_right_'+name);
}
function um_rights_remove(name, was_set) {
	var input = document.forms["um_rights"].elements["user_"+name];
	var add = document.getElementById("add_"+name);
	var remove = document.getElementById("remove_"+name);
	input.style.visibility = "hidden";
	input.style.position = "absolute";
	add.style.visibility = "visible";
	add.style.position = "static";
	remove.style.visibility = "hidden";
	remove.style.position = "absolute";
	if (!was_set) pnapplication.dataSaved('add_right_'+name); else pnapplication.dataUnsaved('add_right_'+name);
}
function um_rights_save() {
	var locker = lock_screen(null, "<img src='"+theme.icons_16.save+"'/>Saving...");
	var form = document.forms["um_rights"];
	data = {lock:<?php echo $lock_id;?>,user:<?php echo $user_id;?>};
	for (var i = 0; i < form.elements.length; ++i) {
		var e = form.elements[i];
		var name = e.name;
		if (!name.startsWith("user_")) continue;
		if (e.style.visibility != "visible") continue;
		var right = name.substring(5);
		var value;
		if (e.type == "checkbox")
			value = e.checked;
		else
			value = e.value;
		data[right] = value;
	}
	service.json("user_management","save_user_rights", data, function(result) {
		unlock_screen(locker);
		if (result != null) {
			pnapplication.cancelDataUnsaved();
			location.reload();
		}
	});
}
</script>
<?php }?>
<?php
} // not an admin
?>
</div>
<?php if ($can_edit && !$is_admin) {?>
<div class='page_footer' style='flex:none;'>
	<button id='save_button' class='action' onclick='um_rights_save()'><img src='<?php echo theme::$icons_16["save"];?>'/> Save</button>
	<script type='text/javascript'>pnapplication.autoDisableSaveButton('save_button');</script>
</div>
</div>
<?php }

	}
	
}
