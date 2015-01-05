<?php
class page_role_rights extends Page {
	
	public function getRequiredRights() { return array("manage_roles"); }
	
	protected function execute() {
		// get the role we need to display
		$role_id = $_GET["role"];
		
		// lock the data, so another user will not modify the data at the same time
		$locked = null;
		$can_edit = true;
		require_once("component/data_model/DataBaseLock.inc");
		$lock_id = DataBaseLock::lockTable("RoleRights", $locked);
		if ($locked <> null)
			$can_edit = false;

		$role = SQLQuery::create()->select("Role")->where("id",$role_id)->executeSingleRow();

		if ($can_edit)
			DataBaseLock::generateScript($lock_id);
		
		?>
		<div style='width:100%;height:100%;display:flex;flex-direction:column;overflow:hidden;position:absolute;top:0px;left:0px;'>
		<div class='page_title' style='flex:none;'>
			<img src='/static/user_management/access_list_32.png'/>
			Role: <span style='font-family:Courrier New;font-weight:bold;font-style:italic'><?php echo $role["name"];?></span>
		</div>
		<div style='background-color:white;padding:10px;overflow:auto;flex:1 1 auto;'>
			<?php
			if ($role_id == -1)
				echo "<div class='info_box'><img src='".theme::$icons_16["info"]."'/> This role cannot be modified: it will always have all rights</div>"; 
			?>
			<form name='um_rights' onsubmit='return false' style='height:100%'>
		<?php
		if ($locked <> null)
			echo "<img src='".theme::$icons_16["lock"]."'/> This page is already locked by ".$locked."<br/>";
				
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
		
		// get rights directly attached to the role
		if ($role_id <> -1) {
			$res = SQLQuery::create()->select("RoleRights")->field("right")->field("value")->where("role",$role_id)->execute();
			if (!is_array($res)) $res = array();
			$final = array();
			foreach ($res as $r) $final[$r["right"]] = $all_rights[$r["right"]]->parseValue($r["value"]);
			// add implications
			PNApplication::$instance->user_management->computeRightsImplications($final);
		} else {
			$final = array();
			foreach ($all_rights as $name=>$r) $final[$name] = $r->getHighestValue();
		}
		
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
		echo "<table rules=all cellspacing=0 cellpadding=2>";
		echo "<tr><th colspan=2>Rights</th><th>Access</th></tr>";
		foreach ($categories as $cat_name=>$rights) {
			echo "<tr><td colspan=3 class='category_title'>".$cat_name."</td></tr>";
			foreach ($rights as $r) {
				echo "<tr class='".$right_type[$r->name]."_right'>";
				echo "<td width='10px'></td>";
				echo "<td>".$r->display_name."</td>";
				echo "<td>";
				if ($role_id == -1)
					generate_right("right_", $r, @$final[$r->name], true, true);
				else {
					generate_right("right_", $r, @$final[$r->name], !$can_edit, isset($final[$r->name]));
					echo "<img src='".theme::$icons_16["remove"]."' id='remove_".$r->name."' onclick=\"um_rights_remove('".$r->name."',".(isset($final[$r->name])?"true":"false").");\" style='".(isset($final[$r->name]) ? "visibility:visible;position:static" : "visibility:hidden;position:absolute")."'/>";
					echo "<img src='".theme::$icons_16["add"]."' id='add_".$r->name."' onclick=\"um_rights_add('".$r->name."',".(isset($final[$r->name])?"true":"false").");\" style='".(isset($final[$r->name]) ? "visibility:hidden;position:absolute" : "visibility:visible;position:static")."'/>";
				}
				echo "</td>";
				echo "</tr>";
			}
		}
		echo "</table>";
		?>
		</form>
		<style type='text/css'>
		table {
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
		function um_rights_add(name,was_set) {
			var input = document.forms["um_rights"].elements["right_"+name];
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
		function um_rights_remove(name,was_set) {
			var input = document.forms["um_rights"].elements["right_"+name];
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
			var locker = lockScreen(null, "<img src='"+theme.icons_16.save+"'/>Saving...");
			var form = document.forms["um_rights"];
			var data = {role_id:<?php echo $role_id?>,lock:<?php echo $lock_id?>};
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
				data[right] = value;
			}
			service.json("user_management","save_role_rights", data, function(result) {
				unlockScreen(locker);
				if (result != null) {
					pnapplication.cancelDataUnsaved();
					location.reload();
				}
			});
		}
		</script>
		<?php }?>
		</div>
		<?php if ($can_edit && $role_id <> -1) {?>
		<div class='page_footer' style='flex:none'>
			<button class='action' id='save_button' onclick='um_rights_save()'><img src='<?php echo theme::$icons_16["save"];?>'/> Save</button>
			<script type='text/javascript'>pnapplication.autoDisableSaveButton('save_button');</script>
		</div>
		<?php } ?>
		</div>
		<?php		
	}
	
}
?>