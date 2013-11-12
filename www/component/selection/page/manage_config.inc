<?php
/**
 * The rights of the user is taken into account to set this page
 * This page needs no service to be generated
 */ 
function manage_config(&$page,$container_id){
	
	/** Start by locking the row in the database
	 * In case the data is already locked, generate an error message
	 * The row will be unlocked when leaving from the page
	 */
	require_once("component/data_model/DataBaseLock.inc");
	$campaign_id = PNApplication::$instance->components["selection"]->get_campaign_id();
	$locked_by = null;
	$lock_id = null;
	$lock_id = DataBaseLock::lock_table($campaign_id, $locked_by);
	if($lock_id == null & $locked_by <> null){
		PNApplication::error($locked_by." is already setting the configuration parameters of this campaign");
		return;
	} else {
		DataBaseLock::generate_script($lock_id);
	}
	
	require_once("component/data_model/Model.inc");
	$rights = array();
	
	$fct_name = "manage_config_".$page->generate_id();
	//$page->onload($fct_name."();");

	$rights['remove'] = DataModel::get()->getTable("Selection_campaign_config")->canRemove();
	$rights['edit'] = DataModel::get()->getTable("Selection_campaign_config")->canModifyField('name');
	$rights['add'] = DataModel::get()->getTable("Selection_campaign_config")->canAdd();
	
	/** Get all the possible configs */
	$all_configs = include("component/selection/config.inc");
	
	/* Get the current config */
	$config = PNApplication::$instance->components["selection"]->get_config();
	?>
	<script type='text/javascript'>
	function <?php echo $fct_name; ?>(){
		var campaign_id = <?php echo $campaign_id.";"; ?>
		alert(campaign_id);
		var lock_id = <?php echo $lock_id.";";?>
		var rights = {};
		rights.remove = <?php echo json_encode($rights['remove']).";";?>
		rights.edit = <?php echo json_encode($rights['edit']).";";?>
		rights.add = <?php echo json_encode($rights['add']).";";?>
		
		//TEMP
		rights.edit = true;
	
		var container = document.getElementById(<?php echo json_encode($container_id);?>);
		
		/* Create an object all_configs = [{name:, text:, default_value:, values:[]}] */
		var all_configs = <?php
		echo "[";
		$first = true;
		foreach($all_configs as $all_config){
			if(!$first) echo ", ";
			$first = false;
			echo "{name:".json_encode($all_config[0]);
			echo ", ";
			echo "text:".json_encode($all_config[1]);
			echo ", ";
			echo "default_value:".json_encode($all_config[2]);
			echo ", ";
			echo "type:".json_encode($all_config[4]);
			echo ", ";
			echo "values:[";
			$first_value = true;
			foreach($all_config[3] as $default_value){
				if(!$first_value) echo ", ";
				$first_value = false;
				echo json_encode($default_value);
			}
			echo "]";
			echo "}";
		}
		echo "];";
		?>
		
		/**
		 * Create an object containing the current config = [{name:, value:}]
		 */
		var config = <?php
			echo "[";
			if(count($config) > 0){
				$first = true;
				foreach($config as $c){
					if(!$first) echo ", ";
					$first = false;
					echo "{name:".json_encode($c["name"]).", value:".json_encode($c["value"])."}";
				}
			}
			echo "];";
			$q_default = SQLQuery::create()->insert("Selection_campaign_config",array("name"=>"toto","value"=>"tata"));
			//echo "count:".count($config).", name:".$config["name"].", value:".$config["value"];
		?>
		/**
		 * The old configuration is stocked in the old_config variable
		 * We need it to access the database (for the "where") as there is no primarykey
		 */
		var old_config = config;
		
		config.init = function(){
			this.createTable();
		}
		
		config.createTable = function(){
			var table = document.createElement("table");
			var theader = document.createElement("theader");
			var tbody = document.createElement("tbody");
			var ul = document.createElement("ul");
			var tfoot = document.createElement("tfoot");
			var form = document.createElement("form");
			config.setHeader(theader);
			config.setBody(tbody);
			config.setFoot(tfoot);
			table.appendChild(theader);
			table.appendChild(tbody);
			table.appendChild(tfoot);
			ul.appendChild(table);
			form.appendChild(ul);
			container.appendChild(form);
		}
		
		config.setHeader = function (theader){
			var div = document.createElement('div');
			div.innerHTML = "Selection Campaign Configuration <img src='"+theme.icons_32.config+"' />";
			div.style.fontSize = "x-large";
			div.style.padding = "30px";
			theader.appendChild(div);
		}
		
		config.setBody = function(tbody){
			if(!rights.edit){
				var div = document.createElement("div");
				div.innerHTML = "You are not allowed to edit the configuration of this campaign";
				div.style.fontStyle = "italic";
				tbody.appendChild(div);
			} else {
				for(var i = 0; i < all_configs.length; i++){
					config.addRow(i, tbody);
				}
			}
		}
		
		config.addRow = function(index, tbody){
			var tr = document.createElement("tr");
			var td1 = document.createElement("td");
			var td2 = document.createElement("td");
			var li = document.createElement("li");
			li.innerHTML = all_configs[index].text;
			td1.appendChild(li);
			var config_index = config.findIndex(all_configs[index].name);
			/* If the type is boolean, create a checkbox */
			if(all_configs[index].type == "boolean"){
				var check = document.createElement("input");
				check.type = "checkbox";
				check.value = true;
				check.name = all_configs[index].name;
				/* Set the current config */
				//config_index cannot be null because at least all the config parameters are set to default value
				if(config[config_index].value == true) check.checked = true;
				else check.checked = false;
				
				check.onchange = function(){
					/* We update the config object */
					config[config_index].value = check.checked;
				};
				
				td2.appendChild(check);
			}
			/* Else create a select */
			else{
				var select = document.createElement("select");
				for(var i = 0; i < all_configs[index].values.length; i++){
					var option = document.createElement("option");
					option.value = all_configs[index].values[i];
					option.text = all_configs[index].values[i];
					if(config[config_index].value == all_configs[index].values[i]) option.selected = true;
					select.appendChild(option);
				}
				select.onchange = function(){
					var option = this.options[this.selectedIndex];
					config[config_index].value = option.value;
				};
				td2.appendChild(select);
			}
			tr.appendChild(td1);
			tr.appendChild(td2);
			tbody.appendChild(tr);
		}
		
		config.findIndex = function(name){
			var index = null;
			for(var i = 0; i < config.length; i++){
				if(config[i].name == name){
					index = i;
					break;
				}
			}
			return index;
		}
		
		config.setFoot = function(tfoot){
			var submit = document.createElement("div");
			submit.innerHTML = "Save <img src='"+theme.icons_16.save+"' />";
			submit.style.fontWeight = "bold";
			submit.className = "button";
			submit.onclick = function(){
				config.save();
				return false;
			};
			tfoot.appendChild(submit);
		}
		
		config.save = function(){
			service.json("selection","save_config",{fields:config, old_config:old_config}, function(res){
				if(!res) return;
				require("popup_window.js",function() {
					var p = new popup_window("", theme.icons_16.info, "The new configuration has been succesfully saved");
					p.show();
				});
			});
		}
		if(typeof(container) == "object") config.init();
	}
	//Start the process
	<?php echo $fct_name; ?>();
	</script>
	<?php

}
?>