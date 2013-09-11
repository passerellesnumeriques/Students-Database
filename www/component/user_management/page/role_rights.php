<?php
class page_role_rights extends Page {
	
	public function get_required_rights() { return array("manage_roles"); }
	
	protected function execute() {
		// get the role we need to display
		$role_id = $_GET["role"];
		
		// lock the data, so another user will not modify the data at the same time
		$locked = null;
		$can_edit = true;
		require_once("component/data_model/DataBaseLock.inc");
		$lock_id = DataBaseLock::lock_table("RoleRights", $locked);
		if ($locked <> null)
			$can_edit = false;

		$role = SQLQuery::create()->select("Role")->where("id",$role_id)->execute_single_row();

		$this->add_javascript("/static/widgets/page_header.js");
		$this->onload("new page_header('role_rights_header');");
		
		if ($can_edit)
			DataBaseLock::generate_script($lock_id);
		?>
		<div id='role_rights_header' icon='/static/user_management/access_list_32.png' title="Role: &lt;span style='font-family:Courrier New;font-weight:bold;font-style:italic'&gt;<?php echo $role["name"];?>&lt;/span&gt;">
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
		
		// get rights directly attached to the role
		$res = SQLQuery::create()->select("RoleRights")->field("right")->field("value")->where("role_id",$role_id)->execute();
		if (!is_array($res)) $res = array();
		$final = array();
		foreach ($res as $r) $final[$r["right"]] = $all_rights[$r["right"]]->parse_value($r["value"]);
		// add implications
		user_management::compute_rights_implications($final, $all_rights);
		
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
		echo "<tr><th colspan=2>Rights</th><th>Access</th></tr>";
		foreach ($categories as $cat_name=>$rights) {
			echo "<tr><td colspan=3 class='category_title'>".$cat_name."</td></tr>";
			foreach ($rights as $r) {
				echo "<tr>";
				echo "<td width='10px'></td>";
				echo "<td>".$r->display_name."</td>";
				echo "<td>";
				generate_right("right_", $r, @$final[$r->name], !$can_edit, isset($final[$r->name]));
				echo "<img src='".theme::$icons_16["remove"]."' id='remove_".$r->name."' onclick=\"um_rights_remove('".$r->name."');\" style='".(isset($final[$r->name]) ? "visibility:visible;position:static" : "visibility:hidden;position:absolute")."'/>";
				echo "<img src='".theme::$icons_16["add"]."' id='add_".$r->name."' onclick=\"um_rights_add('".$r->name."');\" style='".(isset($final[$r->name]) ? "visibility:hidden;position:absolute" : "visibility:visible;position:static")."'/>";
				echo "</td>";
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
			var input = document.forms["um_rights"].elements["right_"+name];
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
			var input = document.forms["um_rights"].elements["right_"+name];
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
			var data = "role_id=<?php echo $role_id?>";
			for (var i = 0; i < form.elements.length; ++i) {
				var e = form.elements[i];
				var name = e.name;
				if (!name.startsWith("right_")) continue;
				if (e.style.visibility != "visible") continue;
				var right = name.substring(6);
				var value;
				if (e.type == "checkbox")
					value = e.checked;
				else
					value = e.value;
				data += "&"+encodeURIComponent(right)+"="+encodeURIComponent(value);
			}
			service.json("user_management","save_role_rights?lock=<?php echo $lock_id?>", data, function(result) {
				if (result != null)
					location.reload();
				else {
					document.body.removeChild(icon);
					document.body.removeChild(saving);
				}
			});
		}
		<?php }?>
		</script>
		<?php		
	}
	
}
?>