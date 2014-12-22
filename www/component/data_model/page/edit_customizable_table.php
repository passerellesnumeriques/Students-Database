<?php 
class page_edit_customizable_table extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		require_once("component/data_model/Model.inc");
		$table_name = $_GET["table"];
		$table = DataModel::get()->getTable($table_name);
		$sub_model = null;
		if ($table->getModel() instanceof SubDataModel) {
			if (isset($_GET["sub_model"])) $sub_model = $_GET["sub_model"];
			else $sub_model = SQLQuery::getPreselectedSubModel($table->getModel()->getParentTable());
			if ($sub_model == null) {
				PNApplication::error("No sub model selected");
				return;
			}
		}
		if (!PNApplication::$instance->user_management->has_right($table->getCustomizationRight())) {
			PNApplication::error("You are not allowed to edit those information");
			return;
		}
		
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		$lock_id = DataBaseLock::lockTable($table->getSQLNameFor($sub_model), $locked_by, true);
		if ($lock_id == null) {
			PNApplication::error("This is currently used by ".$locked_by." so you cannot edit it");
			return;
		}
		DataBaseLock::generateScript($lock_id);
		
		// get the current list of columns in the table
		$columns = $table->internalGetColumnsFor($sub_model);
		// filter non-custom columns
		for ($i = 0; $i < count($columns); $i++) {
			$name = $columns[$i]->name;
			if (substr($name,0,1) <> "c" || intval(substr($name,1) <= 0)) {
				array_splice($columns, $i, 1);
				$i--;
			}
		}
		// is there any data in the table ?
		$has_data = SQLQuery::create()->bypassSecurity(true)
#DEV
			->noWarning()
#END
			->select($table_name)->selectSubModelForTable($table, $sub_model)->limit(0, 1)->executeSingleRow() <> null;
		$columns_data = array();
		foreach ($columns as $col) {
			if (!$has_data) $columns_data[$col->name] = false;
			else $columns_data[$col->name] = SQLQuery::create()->bypassSecurity()
#DEV
				->noWarning()
#END
				->select($table_name)->selectSubModelForTable($table, $sub_model)->field($table_name, $col->name)->whereNotNull($table_name, $col->name)->limit(0,1)->executeSingleRow() <> null;
		}
		
		?>
<div style='width:100%;height:100%;background-color:white;display:flex;flex-direction:column;'>
	<div class='page_title' style='flex:none'>
		<img src='<?php echo theme::make_icon("/static/data_model/database_32.png", theme::$icons_16["edit"])?>'/>
		Database Customization: <i><?php echo $table->getCustomizationName()?></i>
	</div>
	<div style='flex:1 1 auto;overflow:auto'>
		<?php
		$avail_data = PNApplication::$instance->data_model->getAvailableFields($table_name, $sub_model);
		$fixed_data = array();
		for ($i = 0; $i < count($avail_data); $i++) {
			if ($avail_data[$i][0]->getTableName() <> $table_name)
				array_push($fixed_data, $avail_data[$i][0]);
		}
		$fixed_by_category = array();
		foreach ($fixed_data as $data) {
			$cat = $data->getCategoryName();
			if (!isset($fixed_by_category[$cat])) $fixed_by_category[$cat] = array();
			array_push($fixed_by_category[$cat], $data);
		}
		
		require_once("component/data_model/DataModelCustomizationPlugin.inc");
		/* @var $plugins DataModelCustomizationPlugin[] */
		$plugins = array();
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi)
				if ($pi instanceof DataModelCustomizationPlugin)
					array_push($plugins, $pi);
		
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_integer.js");
		$this->requireJavascript("field_decimal.js");
		$this->requireJavascript("field_date.js");
		?>
		<div class='page_section_title'>
			Data already present in the database, that you cannot customize
		</div>
		<style type='text/css'>
		.fixed_data {
			border: 1px solid black;
			border-collapse: collapse;
			border-spacing: 0px;
			margin: 10px;
		}
		.fixed_data th {
			background-color: #C0C0FF;
			border: 1px solid black;
		}
		.fixed_data td {
			border: 1px solid black;
		}
		</style>
		<table class='fixed_data'>
			<tr><?php foreach ($fixed_by_category as $cat=>$list) echo "<th>".toHTML($cat)."</th>"; ?></tr>
			<tr>
				<?php
				foreach ($fixed_by_category as $cat=>$list) {
					echo "<td valign=top>";
					foreach ($list as $data)
						echo toHTML($data->getDisplayName())."<br/>";
					echo "</td>";
				}
				?>
			</tr>
		</table>
		<div class='page_section_title'>
			Additional Data
		</div>
		<div>
			<style type='text/css'>
			.custom_data {
				border: 1px solid black;
				border-collapse: collapse;
				border-spacing: 0px;
				margin: 10px;
			}
			.custom_data th {
				background-color: #C0C0FF;
				border: 1px solid black;
			}
			.custom_data td {
				border: 1px solid black;
			}
			.no_impact {
				background-color: #A0FFA0;
			}
			.impact {
				background-color: #FFA040;
			}
			</style>
			<div style='display:inline-block;border:1px solid black;margin:5px;padding:5px;'>
				<div style='text-decoration:underline;'>Legend</div>
				<div class='no_impact' style='border:1px solid black;width:20px;height:14px;display:inline-block;margin-right:5px;margin-top:3px;vertical-align:bottom;'></div>Rows in green already exist, but no data has been filled in yet, so it can be modified without impacting any data<br/>
				<div class='impact' style='border:1px solid black;width:20px;height:14px;display:inline-block;margin-right:5px;margin-top:3px;vertical-align:bottom;'></div>Rows in orange already exist, and contain already data. Any modification may impact current data.<br/>
				<div style='border:1px solid black;width:20px;height:14px;display:inline-block;margin-right:5px;margin-top:3px;vertical-align:bottom;'></div>Rows in white are new data that you just added.<br/>
				<i>Note:</i> The description can be modified without impact on the data. Changing the type will remove any previous data. Changing type specification (minimum value, maximum size...) may impact the data (i.e. you change the minimum from 5 to 10, all values stored between 5 and 10 will become 10).
			</div><br/>
			<table class='custom_data'>
				<tr id='header_row'>
					<th>Description</th>
					<th>Type</th>
					<th>Mandatory</th>
					<th></th>
				</tr>
				<tr id='buttons_row'>
					<td colspan=4 align=center>
						<button class='action green' onclick="addData();"><img src='<?php echo theme::$icons_16["add_white"];?>'/> Add new data</button>
						<button class='action' onclick="save();"><img src='<?php echo theme::$icons_16["save"];?>'/> Save</button>
					</td>
			</table>
			<script type='text/javascript'>
			function addData(id, has_data, descr, type, spec) {
				var tr = document.createElement("TR");
				tr.col_id = id;
				if (id)
					tr.className = has_data ? "impact" : "no_impact";
				var td;
				// description
				td = document.createElement("TD");
				td.style.verticalAlign = "top";
				tr.descr = document.createElement("INPUT");
				tr.descr.type = "text";
				tr.descr.size = 25;
				tr.descr.maxLength = 100;
				if (descr) tr.descr.value = descr;
				td.appendChild(tr.descr);
				tr.appendChild(td);

				// type
				td = document.createElement("TD");
				td.style.verticalAlign = "top";
				tr.type_select = document.createElement("SELECT");
				tr.type_select.style.verticalAlign = "top";
				fillTypes(tr.type_select, type);
				tr.type_container = document.createElement("DIV");
				tr.type_container.style.display = "inline-block";
				tr.type_container.style.marginLeft = "3px";
				tr.type_select.onchange = function() { updateType(tr); };
				td.appendChild(tr.type_select);
				td.appendChild(tr.type_container);
				if (spec)
					updateType(tr, spec);
				tr.appendChild(td);

				// mandatory
				td = document.createElement("TD");
				td.style.textAlign = "center";
				tr.mand = document.createElement("INPUT");
				tr.mand.type = "checkbox";
				if (spec && spec.can_be_null === false) tr.mand.checked = "checked";
				td.appendChild(tr.mand);
				tr.appendChild(td);

				// remove
				td = document.createElement("TD");
				var button = document.createElement("BUTTON");
				button.className = "flat";
				button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
				button.onclick = function() {
					tr.parentNode.removeChild(tr);
				};
				td.appendChild(button);
				tr.appendChild(td);

				var next = document.getElementById('buttons_row');
				next.parentNode.insertBefore(tr, next);
			}

			function addType(select, value, text, selected) {
				var o = document.createElement("OPTION");
				o.value = value;
				o.text = text;
				if (selected && selected == value) o.selected = true;
				select.add(o);
			}
			function fillTypes(select, selected) {
				addType(select, "", "", selected);
				addType(select, "boolean", "Yes / No", selected);
				addType(select, "string", "Text", selected);
				addType(select, "integer", "Number - Integer", selected);
				addType(select, "decimal", "Number - Decimal", selected);
				addType(select, "date", "Date", selected);
				addType(select, "enum", "Choice", selected);
				<?php
				foreach ($plugins as $pi)
					echo "addType(select, ".json_encode($pi->getId()).",".json_encode($pi->getDisplayName()).",selected);";
				?>
			}
			
			function updateType(tr, spec) {
				tr.type_container.removeAllChildren();
				tr.type_container.spec = {};
				var type = tr.type_select.value;
				if (type == "") return;
				switch (type) {
				case "boolean": createBoolean(tr.type_container, spec); break;
				case "string": createString(tr.type_container, spec); break;
				case "integer": createInteger(tr.type_container, spec); break;
				case "decimal": createDecimal(tr.type_container, spec); break;
				case "date": createDate(tr.type_container, spec); break;
				case "enum": createEnum(tr.type_container, spec); break;
				<?php
				foreach ($plugins as $pi)
					echo "case ".json_encode($pi->getId()).": create__".$pi->getId()."(tr.type_container, spec); break;";
				?>
				}
			}

			function createBoolean(container, spec) {
				// nothing, just a boolean
				container.spec.get = function() { return {}; };
			}
			function createString(container, spec) {
				container.appendChild(document.createTextNode(" Maximum size "));
				container.spec.max_size = new field_integer(spec ? spec.max_length : 50,true,{can_be_null:false,min:1,max:2000});
				container.appendChild(container.spec.max_size.getHTMLElement());
				container.spec.get = function() {
					if (this.max_size.hasError()) return null;
					return {max_length:this.max_size.getCurrentData()};
				};
			}
			function createInteger(container, spec) {
				container.appendChild(document.createTextNode(" Minimum "));
				container.spec.min = new field_integer(spec ? spec.min : null,true,{can_be_null:true});
				container.appendChild(container.spec.min.getHTMLElement());
				container.appendChild(document.createTextNode(" Maximum "));
				container.spec.max = new field_integer(spec ? spec.max : null,true,{can_be_null:true});
				container.appendChild(container.spec.max.getHTMLElement());
				container.spec.min.onchange.addListener(function() {
					container.spec.max.setMinimum(container.spec.min.getCurrentData());
				});
				container.spec.max.onchange.addListener(function() {
					container.spec.min.setMaximum(container.spec.max.getCurrentData());
				});
				container.spec.get = function() {
					if (this.min.hasError() || this.max.hasError()) return null;
					return {min:this.min.getCurrentData(),max:this.max.getCurrentData()};
				};
			}
			function createDecimal(container, spec) {
				container.appendChild(document.createTextNode(" Number of decimals "));
				container.spec.digits = new field_integer(spec ? spec.decimal_digits : 2,true,{can_be_null:false,min:1,max:6});
				container.appendChild(container.spec.digits.getHTMLElement());
				container.spec.digits.onchange.addListener(function() {
					container.spec.min.setDecimalDigits(container.spec.digits.getCurrentData());
					container.spec.max.setDecimalDigits(container.spec.digits.getCurrentData());
				});
				container.appendChild(document.createTextNode(" Minimum "));
				container.spec.min = new field_decimal(spec ? spec.min : null,true,{can_be_null:true,integer_digits:10,decimal_digits:2});
				container.appendChild(container.spec.min.getHTMLElement());
				container.appendChild(document.createTextNode(" Maximum "));
				container.spec.max = new field_decimal(spec ? spec.max : null,true,{can_be_null:true,integer_digits:10,decimal_digits:2});
				container.appendChild(container.spec.max.getHTMLElement());
				container.spec.min.onchange.addListener(function() {
					container.spec.max.setMinimum(container.spec.min.getCurrentData());
				});
				container.spec.max.onchange.addListener(function() {
					container.spec.min.setMaximum(container.spec.max.getCurrentData());
				});
				container.spec.get = function() {
					if (this.digits.hasError() || this.min.hasError() || this.max.hasError()) return null;
					return {decimal_digits:this.digits.getCurrentData(),min:this.min.getCurrentData(),max:this.max.getCurrentData()};
				};
			}
			function createDate(container, spec) {
				container.appendChild(document.createTextNode(" Minimum "));
				container.spec.min = new field_date(spec ? spec.min : null,true,{can_be_null:true});
				container.appendChild(container.spec.min.getHTMLElement());
				container.appendChild(document.createTextNode(" Maximum "));
				container.spec.max = new field_date(spec ? spec.max : null,true,{can_be_null:true});
				container.appendChild(container.spec.max.getHTMLElement());
				container.spec.min.onchange.addListener(function() {
					container.spec.max.setMinimum(container.spec.min.getCurrentData());
				});
				container.spec.max.onchange.addListener(function() {
					container.spec.min.setMaximum(container.spec.max.getCurrentData());
				});
				container.spec.get = function() {
					if (this.min.hasError() || this.max.hasError()) return null;
					return {min:this.min.getCurrentData(),max:this.max.getCurrentData()};
				};
			}
			function createEnum(container, spec) {
				container.appendChild(document.createTextNode("Possible choices:"));
				var add_button = document.createElement("BUTTON");
				add_button.className = "flat small_icon";
				add_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
				add_button.title = "Add a new choice";
				container.appendChild(add_button);
				container.spec.choices = [];
				var addChoice = function(choice) {
					var div = document.createElement("DIV");
					var input = document.createElement("INPUT");
					input.type = "text";
					input.maxLength = 30;
					input.size = 15;
					div.appendChild(input);
					input.value = choice;
					container.spec.choices.push(input);
					var remove = document.createElement("BUTTON");
					remove.className = "flat small_icon";
					remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
					remove.onclick = function() {
						if (container.spec.choices.length == 1) {
							// last one
							input.value = "";
							return;
						}
						container.spec.choices.remove(input);
						container.removeChild(div);
					};
					div.appendChild(remove);
					container.insertBefore(div, add_button);
				};
				add_button.onclick = function() {
					addChoice("");
				};
				if (spec) for (var i = 0; i < spec.values.length; ++i) addChoice(spec.values[i]);
				else addChoice("");
				container.spec.get = function() {
					var s = {values:[]};
					for (var i = 0; i < container.spec.choices.length; ++i) s.values.push(container.spec.choices[i].value);
					return s;
				};
			}
			<?php 
			foreach ($plugins as $pi) {
				echo "function create__".$pi->getId()."(container, spec) {\n";
				// nothing needed so far
				echo "container.spec.get = function() { return {}; };\n";
				echo "}\n";
			}
			?>
			
			function save() {
				var tr = document.getElementById('header_row');
				var trs = [];
				var fields = [];
				while (tr.nextSibling.id != 'buttons_row') {
					tr = tr.nextSibling;
					if (tr.nodeType != 1) continue;
					var field = {};
					field.id = tr.col_id ? tr.col_id : null;
					field.description = tr.descr.value.trim();
					if (field.description.length == 0) {
						alert("Please enter a description for every data");
						return;
					}
					field.type = tr.type_select.value;
					if (field.type == "") {
						alert("Please select a type for every data");
						return;
					}
					field.spec = tr.type_container.spec.get();
					if (field.spec == null) {
						alert("Please correct errors first");
						return;
					}
					field.spec.can_be_null = tr.mand.checked ? false : true;
					fields.push(field);
					trs.push(tr);
				}
				var locker = lock_screen(null, "Saving...");
				service.json("data_model","save_custom_table",{table:<?php echo json_encode($table_name);?>,sub_model:<?php echo json_encode($sub_model);?>,columns:fields,lock_id:<?php echo $lock_id;?>},function(res) {
					if (res) {
						// update columns' names
						for (var i = 0; i < trs.length; ++i)
							trs[i].col_id = res[i];
					}
					unlock_screen(locker);
				});
			}

			<?php
			$display = DataModel::get()->getTableDataDisplay($table_name);
			$data = $display->getDataDisplay(null, $sub_model);
			foreach ($columns as $col) {
				$d = null;
				foreach ($data as $da) {
					$hc = $da->getHandledColumns();
					if ($hc <> null && count($hc) > 0 && $hc[0] == $col->name) { $d = $da; break; }
				}
				if ($d == null) continue; // strange, should not happen
				$descr = $d->getDisplayName();
				$type = null;
				$spec = array("can_be_null"=>$col->can_be_null);
				if ($col instanceof \datamodel\ColumnBoolean) {
					$type = "boolean";
				} else if ($col instanceof \datamodel\ColumnString) {
					$type = "string";
					$spec["max_length"] = $col->max_length;
				} else if ($col instanceof \datamodel\ForeignKey) {
					$pi = null;
					foreach ($plugins as $p) if ($p->getForeignTable() == $col->foreign_table) { $pi = $p; break; }
					if ($pi == null) continue;
					$type = $pi->getId();
					// nothing in the spec so far
				} else if ($col instanceof \datamodel\ColumnInteger) {
					$type = "integer";
					$spec["min"] = $col->min;
					$spec["max"] = $col->max;
				} else if ($col instanceof \datamodel\ColumnDecimal) {
					$type = "decimal";
					$spec["decimal_digits"] = $col->decimal_digits;
					$spec["min"] = $col->min;
					$spec["max"] = $col->max;
				} else if ($col instanceof \datamodel\ColumnDate) {
					$type = "date";
					$spec["min"] = $col->minimum_date;
					$spec["max"] = $col->maximum_date;
				} else if ($col instanceof \datamodel\ColumnEnum) {
					$type = "enum";
					$spec["values"] = $col->values;
				}
				echo "addData(".json_encode($col->name).",".($columns_data[$col->name] ? "true" : "false").",".json_encode($descr).",".json_encode($type).",".json_encode($spec).");\n";
			}
			?>
			</script>
		</div>
	</div>
</div>
		<?php 
	}
	
}
?>