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
	require_once("component/data_model/Model.inc");
	$table = DataModel::get()->getTable("Selection_campaign_config");
	$locked_by = null;
	$lock_id = null;
	$lock_id = DataBaseLock::lock_table($table->getSQLNameFor($campaign_id), $locked_by);
	if($lock_id == null & $locked_by <> null){
		PNApplication::error($locked_by." is already setting the configuration parameters of this campaign");
		return;
	} else {
		DataBaseLock::generate_script($lock_id);
	}
	
	$rights = array();
	
	$fct_name = "manage_config_".$page->generate_id();
	//$page->onload($fct_name."();");

	$rights['manage'] = PNApplication::$instance->components["user_management"]->has_right("manage_selection_campaign",true);
	/** Get all the possible configs */
	$all_configs = include("component/selection/config.inc");
	
	/* Get the current config */	
	$config = PNApplication::$instance->components["selection"]->get_config();
	?>
	<script type='text/javascript'>
	function <?php echo $fct_name; ?>(){
		var campaign_id = <?php echo $campaign_id.";"; ?>
		var lock_id = <?php echo $lock_id.";";?>
		var rights = {};
		rights.manage = <?php echo json_encode($rights['manage']).";";?>
	
		var container = document.getElementById(<?php echo json_encode($container_id);?>);
		
		var campaigns = <?php echo(PNApplication::$instance->components["selection"]->get_json_campaigns().";"); ?>
		
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
			
		?>
		
		/**
		 * @method init
		 * Check that the user can manage a selection campaign
		 * if yes, run the createTable method
		 */
		config.init = function(){
			if(!rights.manage){
				var div = document.createElement("div");
				div.innerHTML = "You are not allowed to edit the configuration of this campaign";
				div.style.fontStyle = "italic";
				div.style.color = "red";
				container.appendChild(div);
			} else this.createTable();
		}
		
		/**
		 * @method createTable
		 * Creates the manage_config string
		 */
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
		
		/**
		 * @method setHeader
		 * @param theader the table header to set
		 * Create the header of the page
		 */
		config.setHeader = function (theader){
			var div = document.createElement('div');
			div.innerHTML = "<img src='"+theme.icons_32.config+"' /> Selection Campaign Configuration";
			div.style.fontSize = "x-large";
			div.style.padding = "30px";
			theader.appendChild(div);
		}
		
		/**
		 * @method setBody
		 * @param tbody te tbody to set
		 * Call the method addRow for each config attribute set in the all_config objects
		 * Call the addButtonsRenameDelete at the end
		 */
		config.setBody = function(tbody){		
			for(var i = 0; i < all_configs.length; i++){
				config.addRow(i, tbody);
			}
			config.addButtonsRenameDelete(tbody);
		}
		
		/**
		 * @method addRow
		 * @param {integer} index the index of the config attribute (in the all_configs object) that will be managed on this row
		 * @param tbody the tbody in which this method will add a row
		 * In case the current config attribute is a boolean, a checkbox is created
		 * Otherwise, a select is created, containing all the options set ine the all_configs object
		 * The row generated is set according to the config set into the database
		 */
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
		
		/**
		 * @method addButtonsRenameDelete
		 * @param tbody
		 * Create two buttons at the end of the tbody object
		 * One is to Rename the current campaign, the other one is to Delete it
		 */
		config.addButtonsRenameDelete = function(tbody){
			var tr = document.createElement("tr");
			var td1 = document.createElement("td");
			var td2 = document.createElement("td");
			var div_add = document.createElement("div");
			var div_remove = document.createElement("div");
			var div_total = document.createElement("div");
			
			div_remove.innerHTML = "<img src='"+theme.icons_16.remove+"' /> Remove campaign";
			div_remove.onmouseover = function(){div_remove.innerHTML = "<img src='"+theme.icons_16.remove_black+"' /> Remove campaign"};
			div_remove.onmouseout = function(){div_remove.innerHTML = "<img src='"+theme.icons_16.remove+"' /> Remove campaign"};
			div_remove.className = "button";
			div_remove.style.fontStyle = "italic";
			div_remove.style.fontWeight = "bold";
			div_remove.style.color = "#808080";
			div_remove.onclick = function(){config.dialogRemoveCampaign()};
			
			div_add.innerHTML = "Rename campaign";
			div_add.style.fontStyle = "italic";
			div_add.style.fontWeight = "bold";
			div_add.style.color = "#808080";
			div_add.className = "button";
			div_add.onclick = function(){config.dialogRename();}
			
			div_total.appendChild(div_add);
			div_total.appendChild(div_remove);
			div_total.style.display = "inline-block";
			
			td1.appendChild(div_total);
			//td2.appendChild(div_remove);
			tr.appendChild(td1);
			tr.appendChild(td2);
			tbody.appendChild(tr);
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
		config.dialogRename = function(){
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
									config.renameCampaign(text.uniformFirstLetterCapitalized(),div_locker);
								}
							}
				);
		}
		
		/**
		 * @method dialogRemoveCampaign
		 * Popup a confirm_dialog. If the user answers yes, lock the screen and call the remove campaign method
		 */
		config.dialogRemoveCampaign = function(){
			confirm_dialog("Are you sure you want to remove this campaign?<br/><i><b>All the related data will be removed</i></b>",function(res){
				if(res){
					var div_locker = lock_screen();
					config.removeCampaign(div_locker);
				}
			});
		}
		
		/**
		 * @method renameCampaign
		 * @param {string} name
		 * @param div_locker the id returned by the lock_screen function called by the dialogRename method
		 * Call the service set_campaign_name, unlock the screen, and reload the page in the case of no error was generated by the service
		 */
		config.renameCampaign = function(name, div_locker){
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
		 * Call the service remove_campaign, unlock the screen, and reload the page in the case of no error was generated by the service
		 */
		config.removeCampaign = function(div_locker){
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
		 * @method findIndex
		 * @param {string} name
		 * Look for the given config attribute into the config object, and return its index
		 * @return index
		 */
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
		
		/**
		 * @method setFoot
		 * @param tfoot the tfoot to set
		 * Add the save button into the given footer
		 */
		config.setFoot = function(tfoot){
			var submit = document.createElement("div");
			submit.innerHTML = "<img src='"+theme.icons_16.save+"' /> Save";
			submit.style.fontWeight = "bold";
			submit.className = "button";
			submit.onclick = function(){
				config.save();
				return false;
			};
			tfoot.appendChild(submit);
		}
		
		/**
		 * @method save
		 * Method called when clicking on the save button
		 * Locks the screen, calls the save_config service, and popup a message when it is saved, then unlock the screen
		 */
		config.save = function(){
			var div_locker = lock_screen();
			service.json("selection","save_config",{fields:config}, function(res){
				if(!res){
					unlock_screen(div_locker);
					return;
				}
				require("popup_window.js",function() {
					var p = new popup_window("", theme.icons_16.info, "The new configuration has been succesfully saved");
					p.show();
					unlock_screen(div_locker);
					// setTimeout(p.close(),3500);
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