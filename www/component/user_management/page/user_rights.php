<?php
class page_user_rights extends Page {
	
	public function get_required_rights() { return array("consult_user_rights"); }
	
	public function execute() {
// get the user we need to display
$domain = $_GET["domain"];
$username = $_GET["username"];

// check the current user has the right to edit
$can_edit = PNApplication::$instance->user_management->has_right("edit_user_rights");

// if the user can edit, we need to lock the data, so another user will not modify the data at the same time
$locked = null;
if ($can_edit) {
	require_once("component/data_model/DataBaseLock.inc");
	$lock_id = DataBaseLock::lock_table("UserRights", $locked);
	if ($locked <> null)
		$can_edit = false;
}

// get roles of the user
$roles = SQLQuery::create()->select("UserRole")->field("UserRole","role_id")->join("UserRole","Role",array("role_id"=>"id"))->field("Role","name")->where("domain",$domain)->where("username",$username)->execute();
if (!is_array($roles)) $roles = array();

// check if the user is an administrator
$is_admin = false;
foreach ($roles as $role)
	if ($role["role_id"] == 0) {
		$is_admin = true;
		break;
	}

$this->add_javascript("/static/widgets/page_header.js");
$this->onload("new page_header('user_rights_header');");

if ($can_edit)
	DataBaseLock::generate_script($lock_id);
?>
<div id='user_rights_header' icon='/static/user_management/access_list_32.png' title="User Access Rights: &lt;span style='font-family:Courrier New;font-weight:bold;font-style:italic'&gt;<?php echo $domain."\\".$username;?>&lt;/span&gt;">
<?php 
	if ($can_edit) {
		?><div class='button' onclick='um_rights_save()'><img src='<?php echo theme::$icons_16["save"];?>'/> Save</div><?php
	}
	if ($locked <> null) {
		?><img src='<?php echo theme::$icons_16["lock"];?>'/> This page is already locked by <?php echo $locked;?><?php
	}
?>
</div>
<?php

if ($is_admin) {
	echo "This user is an administrator, it has the right to do everything";
	return;
}

// retrieve all existing rights, and categories
$all_rights = array();
$categories = array();
foreach (PNApplication::$instance->components as $component) {
	foreach ($component->get_readable_rights() as $cat) {
		if (!isset($categories[$cat->display_name]))
			$categories[$cat->display_name] = array();
		foreach ($cat->rights as $r) {
			array_push($categories[$cat->display_name], $r);
			$all_rights[$r->name] = $r;
		}
	}
	foreach ($component->get_writable_rights() as $cat) {
		if (!isset($categories[$cat->display_name]))
			$categories[$cat->display_name] = array();
		foreach ($cat->rights as $r) {
			array_push($categories[$cat->display_name], $r);
			$all_rights[$r->name] = $r;
		}
	}
}

// get rights directly attached to the user
$rights = SQLQuery::create()->select("UserRights")->field("right")->field("value")->where("domain",$domain)->where("username",$username)->execute();
if (!is_array($rights)) $rights = array();
$user_rights = array();
foreach ($rights as $r) $user_rights[$r["right"]] = $all_rights[$r["right"]]->parse_value($r["value"]);
// get rights for each role
$role_rights = array();
foreach ($roles as $role) {
	$rights = SQLQuery::create()->select("RoleRights")->field("right")->field("value")->where("role_id", $role["role_id"])->execute();
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
		echo "<tr>";
		echo "<td width='10px'></td>";
		echo "<td>".$r->display_name."</td>";
		echo "<td>";
		generate_right("final_", $r, @$final[$r->name]);
		echo "</td>";
		echo "<td>";
		if (isset($user_rights[$r->name]) || !isset($final[$r->name]) || !$r->is_highest($final[$r->name])) {
			generate_right("user_", $r, isset($user_rights[$r->name]) ? $user_rights[$r->name] : @$final[$r->name], !$can_edit, isset($user_rights[$r->name]));
			if ($can_edit) {
				echo "<img src='".theme::$icons_16["remove"]."' id='remove_".$r->name."' onclick=\"um_rights_remove('".$r->name."');\" style='".(isset($user_rights[$r->name]) ? "visibility:visible;position:static" : "visibility:hidden;position:absolute")."'/>";
				echo "<img src='".theme::$icons_16["add"]."' id='add_".$r->name."' onclick=\"um_rights_add('".$r->name."');\" style='".(isset($user_rights[$r->name]) ? "visibility:hidden;position:absolute" : "visibility:visible;position:static")."'/>";
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
</style>
<?php if ($can_edit) {?>
<script type='text/javascript'>
function um_rights_add(name) {
	var input = document.forms["um_rights"].elements["user_"+name];
	var add = document.getElementById("add_"+name);
	var remove = document.getElementById("remove_"+name);
	input.style.visibility = "visible";
	input.style.position = "static";
	add.style.visibility = "hidden";
	add.style.position = "absolute";
	remove.style.visibility = "visible";
	remove.style.position = "static";
}
function um_rights_remove(name) {
	var input = document.forms["um_rights"].elements["user_"+name];
	var add = document.getElementById("add_"+name);
	var remove = document.getElementById("remove_"+name);
	input.style.visibility = "hidden";
	input.style.position = "absolute";
	add.style.visibility = "visible";
	add.style.position = "static";
	remove.style.visibility = "hidden";
	remove.style.position = "absolute";
}
function um_rights_save() {
	var saving = document.createElement("DIV");
	setOpacity(saving, 0.33);
	saving.style.backgroundColor = "#A0A0A0";
	saving.style.position = "fixed";
	saving.style.top = "0px";
	saving.style.left = "0px";
	saving.style.width = getWindowWidth()+"px";
	saving.style.height = getWindowHeight()+"px";
	document.body.appendChild(saving);
	var icon = document.createElement("SPAN");
	icon.innerHTML = "<img src='"+theme.icons_16.save+"'/>Saving...";
	icon.style.top = (getWindowHeight()/2-icon.offsetHeight)+"px";
	icon.style.left = (getWindowWidth()/2-icon.offsetWidth)+"px";
	icon.style.position = "fixed";
	document.body.appendChild(icon);
	var form = document.forms["um_rights"];
	var data = "domain="+encodeURIComponent(<?php echo json_encode($domain)?>)+"&username="+encodeURIComponent(<?php echo json_encode($username)?>);
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
		data += "&"+encodeURIComponent(right)+"="+encodeURIComponent(value);
	}
	service.json("user_management","save_user_rights?lock=<?php echo $lock_id?>", data, function(result) {
		if (result != null)
			location.reload();
		else {
			document.body.removeChild(icon);
			document.body.removeChild(saving);
		}
	});
}
</script>
<?php }?>
<?php
	}
	
}
