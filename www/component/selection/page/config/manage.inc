<?php
/**
 * The rights of the user is taken into account to set this page
 * This page needs no service to be generated
 */ 
function manage_config(&$page,$container_id,$header_id){
	
	/** Start by locking the row in the database
	 * In case the data is already locked, generate an error message
	 * The row will be unlocked when leaving from the page
	 */
	require_once("component/data_model/DataBaseLock.inc");
	$campaign_id = PNApplication::$instance->components["selection"]->getCampaignId();
	require_once("component/data_model/Model.inc");
	$table = DataModel::get()->getTable("Selection_campaign_config");
	$locked_by = null;
	$lock_id = null;
	$lock_id = DataBaseLock::lockTable($table->getSQLNameFor($campaign_id), $locked_by);
	if($lock_id == null & $locked_by <> null){
		PNApplication::error($locked_by." is already setting the configuration parameters of this campaign");
		return;
	} else {
		DataBaseLock::generateScript($lock_id);
	}
	
	$rights = array();
	
	$fct_name = "manage_config_".$page->generateID();
	//$page->onload($fct_name."();");

	$rights['manage'] = PNApplication::$instance->components["user_management"]->has_right("manage_selection_campaign",true);
	/** Get all the possible configs */
	$all_configs = include("component/selection/config.inc");
	
	/* Get the current config */	
	$config = PNApplication::$instance->components["selection"]->getConfig();
	?>
	<script type='text/javascript'>
	function <?php echo $fct_name; ?>(){
		var t = this;
		require([["typed_field.js","field_date.js"],"popup_window.js","page_header.js"],function(){
			if(typeof(container) == "object"){
				t._init();
				t._setPageHeader();
			}
		});
		t.categories = getConfigCategoryIndexes();
		t.dependencies = {};
		t.date_fields = []; //contains all the existing fields_date
		var campaign_id = <?php echo $campaign_id.";"; ?>
		var lock_id = <?php echo $lock_id.";";?>
		var rights = {};
		t.header_cont = document.getElementById(<?php echo json_encode($header_id);?>);
		rights.manage = <?php echo json_encode($rights['manage']).";";?>
	
		var container = document.getElementById(<?php echo json_encode($container_id);?>);
		
		var campaigns = <?php require_once 'component/selection/SelectionJSON.inc';
		echo SelectionJSON::Steps().";"; ?>
		
		/* Create an object all_configs = [{name:, text:, default_value:, values:[], category:, dependencies:[{name:, value:},...]}] */
		var all_configs = <?php
		echo "[";
		$first = true;
		foreach($all_configs as $name => $data){
			if(!$first) echo ", ";
			$first = false;
			echo "{name:".json_encode($name);
			echo ", ";
			echo "text:".json_encode($data[0]);
			echo ", ";
			echo "default_value:".json_encode($data[1]);
			echo ", ";
			echo "type:".json_encode($data[3]);
			echo ", ";
			echo "values:[";
			$first_value = true;
			foreach($data[2] as $default_value){
				if(!$first_value) echo ", ";
				$first_value = false;
				echo json_encode($default_value);
			}
			echo "]";
			echo ",category:".json_encode($data[4]).",";
			echo "dependencies:[";
			$first_d = true;
			foreach($data[5] as $d_name => $d_value){
				if(!$first_d)
					echo ", ";
				$first_d = false;
				echo "{name:".json_encode($d_name).",value:".json_encode($d_value)."}";
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
			
		?>
		
		/**
		 * @method init
		 * Check that the user can manage a selection campaign
		 * if yes, run the createTable method
		 */
		t._init = function(){
			if(!rights.manage){
				var div = document.createElement("div");
				div.innerHTML = "You are not allowed to edit the configuration of this campaign";
				div.style.fontStyle = "italic";
				div.style.color = "red";
				container.appendChild(div);
			} else {
				this._setDependencies();
				this._createTable();
			}
		}
		
		t.reset = function(){
			//reset tbody
			container.removeChild(t.table);
			delete t.table;
			//reset t.date_fields
			delete t.date_fields;
			t.date_fields = [];
			t.table = document.createElement("table");
			t._setBody();
			container.appendChild(t.table);
		}
		
		t._setDependencies = function(){
			for(var i = 0; i < all_configs.length; i++){
				if(all_configs[i].dependencies.length > 0){
					t.dependencies[all_configs[i].name] = all_configs[i].dependencies;
				}
			}
		}
		
		/**
		 * @method createTable
		 * Creates the manage_config string
		 */
		t._createTable = function(){
			t.table = document.createElement("table");
			t.table.style.backgroundColor = "#FFFFFF";
			// t._setHeader(theader);
			t._setBody();
			// t._setFoot(tfoot);
			container.appendChild(t.table);
		}
		
		t._setPageHeader = function(){
			t.header = new page_header(t.header_cont,true);
			t.header.setTitle("<img src='"+theme.icons_16.config+"' /> Selection Campaign Configuration");
			var back = document.createElement("div");
			back.className = "button";
			back.innerHTML = "<img src = '"+theme.icons_16.back+"'/> Back to selection";
			back.onclick = function(){
				location.assign("/dynamic/selection/page/selection_main_page");
			};
			t.header.addMenuItem(back);
			
			var remove = document.createElement("div");
			var rename = document.createElement("div");
			remove.innerHTML = "<img src='"+theme.icons_16.remove+"' /> Remove campaign";
			remove.onmouseover = function(){this.innerHTML = "<img src='"+theme.icons_16.remove_black+"' /> Remove campaign"};
			remove.onmouseout = function(){this.innerHTML = "<img src='"+theme.icons_16.remove+"' /> Remove campaign"};
			remove.className = "button";
			remove.onclick = t._dialogRemoveCampaign;
			
			rename.innerHTML = "Rename campaign";
			rename.className = "button";
			rename.onclick = t._dialogRename;
			
			var save = document.createElement("div");
			save.innerHTML = "<img src='"+theme.icons_16.save+"' /> Save";
			save.style.fontWeight = "bold";
			save.className = "button";
			save.onclick = t._save;
			
			t.header.addMenuItem(save);
			t.header.addMenuItem(rename);
			t.header.addMenuItem(remove);
			
		}
	
		/**
		 * @method setBody
		 * Call the method addRow for each config attribute set in the all_config objects
		 * Call the addButtonsRenameDelete at the end
		 */
		t._setBody = function(){	
			t._setCategoriesOrdered();
			for(var category_index = 0; category_index < t.ordered.length; category_index++){
				//create a table for each category
				var table = document.createElement("table");
				var th = document.createElement("th");
				th.colSpan = 2;
				th.style.textAlign = "left";
				var tr = document.createElement("tr");
				var ul = document.createElement("ul");
				tr.appendChild(th);
				table.appendChild(tr);
				th.innerHTML = t.ordered[category_index].uniformFirstLetterCapitalized();
				for(var i = 0; i < all_configs.length; i++){
					if(all_configs[i].category == t.ordered[category_index])
						t._addRowToCategoryTable(all_configs[i].name,ul);
				}
				table.appendChild(ul);
				t.table.appendChild(table);
			}
		}
		
		t._setCategoriesOrdered = function(){
			t.ordered = [];
			for(var i = 0; i < t.categories.length; i++)
				t.ordered[i] = t.categories[t._findCategoryIndex(i+1)].name;
		}
		
		t._findCategoryIndex = function(index){
			var index_in_categories = null;
			for(var i = 0; i < t.categories.length; i++){
				if(index == t.categories[i].index){
					index_in_categories = i;
					break;
				}
			}
			return index_in_categories;
		}
		
		t._addRowToCategoryTable = function(config_name,ul){
			if(t._allowedByDependencies(config_name)){
				var tr = document.createElement("tr");
				var td1 = document.createElement("td");
				var td2 = document.createElement("td");
				var li = document.createElement("li");
				var all_configs_index = t._findAllConfigIndex(config_name);
				li.innerHTML = all_configs[all_configs_index].text;
				td1.appendChild(li);
				var config_index = findIndexInConfig(config, config_name);
				if(all_configs[all_configs_index].type == "boolean"){
					var check = document.createElement("input");
					check.type = "checkbox";
					check.value = true;
					check.name = all_configs[all_configs_index].name;
					/* Set the current config */
					/*config_index cannot be null because at least all the config parameters are set to default value*/
					if(config[config_index].value == true)
						check.checked = true;
					else
						check.checked = false;
					check.onchange = function(){
						/* We update the config object */
						config[config_index].value = check.checked;
						if(t._isTargetedByDependencies(this.name))
							t.reset(); //may have allowed | forbidden some other fields
					};
					td2.appendChild(check);
				} else if(all_configs[all_configs_index].type == "enum"){
					var select = document.createElement("select");
					for(var i = 0; i < all_configs[all_configs_index].values.length; i++){
						var option = document.createElement("option");
						option.value = all_configs[all_configs_index].values[i];
						option.text = all_configs[all_configs_index].values[i];
						if(config[config_index].value == all_configs[all_configs_index].values[i])
							option.selected = true;
						select.appendChild(option);
					}
					select.onchange = function(){
						var option = this.options[this.selectedIndex];
						config[config_index].value = option.value;
					};
					td2.appendChild(select);
				} else if(all_configs[all_configs_index].type == "date"){
					//add the field_date to the t.date_fields array
					var index = t.date_fields.length;
					t.date_fields[index] = {};
					t.date_fields[index].field_date = new field_date(config[config_index].value,true);
					t.date_fields[index].config_name = config_name;
					td2.appendChild(t.date_fields[index].field_date.getHTMLElement());
				}
				tr.appendChild(td1);
				tr.appendChild(td2);
				ul.appendChild(tr);
			}
		}
		
		t._findAllConfigIndex = function(name){
			var index = null;
			for(var i = 0; i < all_configs.length; i++){
				if(all_configs[i].name == name){
					index = i;
					break;
				}
			}
			return index;
		}
		
		t._allowedByDependencies = function(name){
			var allowed = true;
			if(t._hasDependencies(name)){
				for(var i = 0; i < t.dependencies[name].length; i++){
					var index = findIndexInConfig(config, t.dependencies[name][i].name);
					if(config[index].value != t.dependencies[name][i].value){
						allowed = false;
						break;
					}
				}
			}
			return allowed;
		}
		
		t._hasDependencies = function(name){
			if(typeof(t.dependencies[name]) != "undefined")
				return true;
			else
				return false;
		}
		
		t._isTargetedByDependencies = function(name){
			var is_targeted = false;
			for(var i = 0; i < all_configs.length; i++){
				if(t._hasDependencies(all_configs[i].name)){
					for(var j = 0; j < t.dependencies[all_configs[i].name].length; j++){
						if(t.dependencies[all_configs[i].name][j].name == name){
							is_targeted = true;
							break;
						}
					}
				}
			}
			return is_targeted;
		}
		
		/**
		 * @method checkCampaignName
		 * @param name {string} the name to set
		 * @return {boolean} true if the name passed the test
		 */
		function checkCampaignName(name){
			var is_unique = true;
			for(var i = 0; i < campaigns.length; i++){
				if(campaigns[i].name.toLowerCase() == name.toLowerCase()){
					is_unique = false;
					break;
				}
			}
			return is_unique;
		}
		
		/**
		 * @method dialogRename
		 * Popup an input_dialog, asking for the new name
		 * Checks that the given name does not exist in the database
		 * If not, call the renameCampaign method after uniformizing the case
		 * The screen is locked by the lock_screen method while the service was not executed
		 */ 
		t._dialogRename = function(){
			input_dialog(theme.icons_16.question,
							"Rename the current selection campaign",
							"Enter the new name of the selection campaign.<br/><i>You will be redirected after submitting<br/><b>All the unsaved configurations will be lost!</b></i>",
							'',
							50,
							function(text){
								if(!text.checkVisible()) return "You must enter at least one visible caracter";
								else {
									if(!checkCampaignName(text)) return "A campaign is already set as " + text.uniformFirstLetterCapitalized();
									else return;
								}
							},
							function(text){
								if(text){
									var div_locker = lock_screen();
									t._renameCampaign(text.uniformFirstLetterCapitalized(),div_locker);
								}
							}
				);
		}
		
		/**
		 * @method dialogRemoveCampaign
		 * Popup a confirm_dialog. If the user answers yes, lock the screen and call the remove campaign method
		 */
		t._dialogRemoveCampaign = function(){
			confirm_dialog("Are you sure you want to remove this campaign?<br/><i><b>All the related data will be removed</i></b>",function(res){
				if(res){
					var div_locker = lock_screen();
					t._removeCampaign(div_locker);
				}
			});
		}
		
		/**
		 * @method renameCampaign
		 * @param {string} name
		 * @param div_locker the id returned by the lock_screen function called by the dialogRename method
		 * Call the service set_campaign_name, unlock the screen, and reload the page in the case of no error was generated by the service
		 */
		t._renameCampaign = function(name, div_locker){
			service.json("selection","set_campaign_name",{id:campaign_id, name:name},function(res){
				if(!res){
					unlock_screen(div_locker);
					return;
				}
				unlock_screen(div_locker);
				window.parent.location.reload();
			});
		}
		
		/**
		 * @method removeCampaign
		 * @param div_locker the id returned by the lock_screen function called by the dialogRemoveCampaign method
		 * Call the service removeCampaign, unlock the screen, and reload the page in the case of no error was generated by the service
		 */
		t._removeCampaign = function(div_locker){
			service.json("selection","remove_campaign",{id:campaign_id},function(res){
				if(!res){
					unlock_screen(div_locker);
					return;
				}
				unlock_screen(div_locker);
				window.parent.location.reload();
			});
		}
		
		/**
		 * @method save
		 * Method called when clicking on the save button
		 * Locks the screen, calls the saveConfig service, and popup a message when it is saved, then unlock the screen
		 */
		t._save = function(){
			/* Get the data from all the field_dates */
			var errors = false;
			for(var i = 0; i < t.date_fields.length; i++){
				var index = findIndexInConfig(config,t.date_fields[i].config_name);
				var value = t.date_fields[i].field_date.getCurrentData();
				if(value == null)
					errors = true;
				else
					config[index].value = value;
			}
			if(errors)
				error_dialog("Please check that all the dates fields are fulfilled");
			else {
				var div_locker = lock_screen();
				service.json("selection","config/save",{fields:config}, function(res){
					unlock_screen(div_locker);
					if(!res){
						error_dialog("An error occured, your informations were not saved");
						return;
					}
					var p = new popup_window("Saved!", theme.icons_16.info, "The new configuration has been succesfully saved");
					p.show();
				});
			}
		}
	}
	//Start the process
	<?php echo $fct_name; ?>();
	</script>
	<?php

}
?>