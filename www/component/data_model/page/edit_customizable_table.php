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
			<tr><?php foreach ($fixed_by_category as $cat=>$list) echo "<th>".htmlentities($cat)."</th>"; ?></tr>
			<tr>
				<?php
				foreach ($fixed_by_category as $cat=>$list) {
					echo "<td valign=top>";
					foreach ($list as $data)
						echo htmlentities($data->getDisplayName())."<br/>";
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
			</style>
			<table class='custom_data'>
				<tr id='header_row'>
					<th>Description</th>
					<th>Type</th>
					<th>Mandatory</th>
					<th></th>
				</tr>
				<tr id='buttons_row'>
					<td colspan=4 align=center>
						<button class='action' onclick="addData();"><img src='<?php echo theme::$icons_16["add"];?>'/> Add new data</button>
						<button class='action' onclick="save();"><img src='<?php echo theme::$icons_16["save"];?>'/> Save</button>
					</td>
			</table>
			<script type='text/javascript'>
			function addData() {
				var tr = document.createElement("TR");
				var td;
				// description
				td = document.createElement("TD");
				tr.descr = document.createElement("INPUT");
				tr.descr.type = "text";
				tr.descr.size = 25;
				tr.descr.maxLength = 100;
				td.appendChild(tr.descr);
				tr.appendChild(td);

				// type
				td = document.createElement("TD");
				tr.type_select = document.createElement("SELECT");
				fillTypes(tr.type_select);
				tr.type_container = document.createElement("DIV");
				tr.type_container.style.display = "inline-block";
				tr.type_container.style.marginLeft = "3px";
				tr.type_select.onchange = function() { updateType(tr); };
				td.appendChild(tr.type_select);
				td.appendChild(tr.type_container);
				tr.appendChild(td);

				// mandatory
				td = document.createElement("TD");
				td.style.textAlign = "center";
				tr.mand = document.createElement("INPUT");
				tr.mand.type = "checkbox";
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
				// TODO do not allow to remove if some data are already there...

				var next = document.getElementById('buttons_row');
				next.parentNode.insertBefore(tr, next);
			}

			function addType(select, value, text) {
				var o = document.createElement("OPTION");
				o.value = value;
				o.text = text;
				select.add(o);
			}
			function fillTypes(select) {
				addType(select, "", "");
				addType(select, "boolean", "Yes / No");
				addType(select, "string", "Text");
				addType(select, "integer", "Number - Integer");
				addType(select, "decimal", "Number - Decimal");
				addType(select, "date", "Date");
			}
			
			function updateType(tr) {
				tr.type_container.removeAllChildren();
				tr.type_container.spec = {};
				var type = tr.type_select.value;
				if (type == "") return;
				switch (type) {
				case "boolean": createBoolean(tr.type_container); break;
				case "string": createString(tr.type_container); break;
				case "integer": createInteger(tr.type_container); break;
				case "decimal": createDecimal(tr.type_container); break;
				case "date": createDate(tr.type_container); break;
				}
			}

			function createBoolean(container) {
				// nothing, just a boolean
				container.spec.get = function() { return {}; }
			}
			function createString(container) {
				container.appendChild(document.createTextNode(" Maximum size "));
				container.spec.max_size = new field_integer(null,true,{can_be_null:true,min:1,max:2000});
				container.appendChild(container.spec.max_size.getHTMLElement());
				container.spec.get = function() {
					if (this.max_size.hasError()) return null;
					return {max_length:this.max_size.getCurrentData()};
				};
			}
			function createInteger(container) {
				container.appendChild(document.createTextNode(" Minimum "));
				container.spec.min = new field_integer(null,true,{can_be_null:true});
				container.appendChild(container.spec.min.getHTMLElement());
				container.appendChild(document.createTextNode(" Maximum "));
				container.spec.max = new field_integer(null,true,{can_be_null:true});
				container.appendChild(container.spec.max.getHTMLElement());
				container.spec.min.onchange.add_listener(function() {
					container.spec.max.setMinimum(container.spec.min.getCurrentData());
				});
				container.spec.max.onchange.add_listener(function() {
					container.spec.min.setMaximum(container.spec.max.getCurrentData());
				});
				container.spec.get = function() {
					if (this.min.hasError() || this.max.hasError()) return null;
					return {min:this.min.getCurrentData(),max:this.max.getCurrentData()};
				};
			}
			function createDecimal(container) {
				container.appendChild(document.createTextNode(" Number of decimals "));
				container.spec.digits = new field_integer(2,true,{can_be_null:false,min:1,max:6});
				container.appendChild(container.spec.digits.getHTMLElement());
				container.spec.digits.onchange.add_listener(function() {
					container.spec.min.setDecimalDigits(container.spec.digits.getCurrentData());
					container.spec.max.setDecimalDigits(container.spec.digits.getCurrentData());
				});
				container.appendChild(document.createTextNode(" Minimum "));
				container.spec.min = new field_decimal(null,true,{can_be_null:true,integer_digits:10,decimal_digits:2});
				container.appendChild(container.spec.min.getHTMLElement());
				container.appendChild(document.createTextNode(" Maximum "));
				container.spec.max = new field_decimal(null,true,{can_be_null:true,integer_digits:10,decimal_digits:2});
				container.appendChild(container.spec.max.getHTMLElement());
				container.spec.min.onchange.add_listener(function() {
					container.spec.max.setMinimum(container.spec.min.getCurrentData());
				});
				container.spec.max.onchange.add_listener(function() {
					container.spec.min.setMaximum(container.spec.max.getCurrentData());
				});
				container.spec.get = function() {
					if (this.digits.hasError() || this.min.hasError() || this.max.hasError()) return null;
					return {decimal_digits:this.digits.getCurrentData(),min:this.min.getCurrentData(),max:this.max.getCurrentData()};
				};
			}
			function createDate(container) {
				container.appendChild(document.createTextNode(" Minimum "));
				container.spec.min = new field_date(null,true,{can_be_empty:true});
				container.appendChild(container.spec.min.getHTMLElement());
				container.appendChild(document.createTextNode(" Maximum "));
				container.spec.max = new field_date(null,true,{can_be_empty:true});
				container.appendChild(container.spec.max.getHTMLElement());
				container.spec.min.onchange.add_listener(function() {
					container.spec.max.setMinimum(container.spec.min.getCurrentData());
				});
				container.spec.max.onchange.add_listener(function() {
					container.spec.min.setMaximum(container.spec.max.getCurrentData());
				});
				container.spec.get = function() {
					if (this.min.hasError() || this.max.hasError()) return null;
					return {min:this.min.getCurrentData(),max:this.max.getCurrentData()};
				};
			}
			
			function save() {
				var tr = document.getElementById('header_row');
				var fields = [];
				while (tr.nextSibling.id != 'buttons_row') {
					tr = tr.nextSibling;
					if (tr.nodeType != 1) continue;
					var field = {};
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
				}
				var locker = lock_screen(null, "Saving...");
				service.json("data_model","save_custom_table",{table:<?php echo json_encode($table_name);?>,sub_model:<?php echo json_encode($sub_model);?>,columns:fields,lock_id:<?php echo $lock_id;?>},function(res) {
					unlock_screen(locker);
				});
			}
			</script>
		</div>
	</div>
</div>
		<?php 
	}
	
}
?>