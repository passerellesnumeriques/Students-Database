<?php
class page_set_geography_area extends Page {
	public function get_required_rights(){
	// TODO
	return array();
	}
	public function execute(){
		if (!isset($_GET['country']) || $_GET['country'] == "") {
			echo "Please select a country to edit";
			return;
		}
		$country = SQLQuery::create()->select("Country")->where("id",$_GET["country"])->execute_single_row();
		$this->add_javascript("/static/widgets/splitter_vertical/splitter_vertical.js");
		$this->onload("new splitter_vertical('page_split',0.25);");
		$this->add_javascript("/static/widgets/section/section.js");
		$this->onload("section_from_html('manage_divisions_section');");
		$this->onload("section_from_html('tree_section');");
?>
<div id='page_split' style='width:100%;height:100%'>
	<div style='overflow:auto;'>
		<div id='manage_divisions_section' title="Country Divisions" style='margin:10px'>
			<div id ='manage_divisions' ></div>
		</div>
	</div>
	<div style='overflow:auto;'>
		<div id='tree_section' title="Geographic Areas" style='margin:10px'>
			<div id='set_geography_area' style = "padding:10px"></div>
		</div>
	</div>
</div>
<script type = 'text/javascript'>
var result = null;
var tr = null;
var editable = null;

var country_id = <?php echo $country['id'];?>;
var country_code = "<?php echo $country['code'];?>";
var country_name = "<?php echo $country['name'];?>";
service.json("geography","get_country_data", {country_id:country_id}, function(res){
	if(!res) return;
	result = res;

	/**
	* Call the remove function for one area and all its children
	* @method set_geographic_area#startRemove
	* @parameter area_id
	*/
	result.startRemove = function(area_id){
		var child = this.findChildren(area_id);
		var children = [];
		children[0] = [];
		var index = this.findIndex(area_id);
		var level = index.division_index;
		/*we initialize children*/
		for(var i = 0; i < child.length; i++){
			children[0][i] = child[i].area_id;
		}
		var k = 0;
		while(level != this.length -1){
			children[k+1] = [];
			var child_index = 0;
			for(var j = 0; j < children[k].length; j++){
				var c = this.findChildren(children[k][j]);
				for(var l = 0; l < c.length; l++){
					children[k+1][child_index] = c[l].area_id;
					child_index++;
				}
			}
			k++;
			level++;
		}
		/*We now start removing all the areas contained by children*/
		for(var i = children.length -1; i >= 0; i--){
			for(var j = children[i].length - 1; j >= 0; j--){
				this.removeArea(children[i][j]);
			}
		}
		/*We remove the current area*/
		this.removeArea(area_id);
		/*We remove from the database*/
		service.json("data_model","remove_row",{table:"Geographic_area", row_key:area_id}, function(res){
			if(!res) return;
		},true);
	};
	
	/**
	 * Remove one area from the tree and update result object (mandatory because the page is not refreshed after removing)
	 * @parameter area_id
	 */
	result.removeArea = function(area_id){
		/*We remove from the tree*/
		var index = this.findIndex(area_id);
		tr.removeItem(result[index.division_index].areas[index.area_index].item);
		/*We update result object*/
		this[index.division_index].areas[index.area_index] = null;
	};
	
	/**
	 * Add one area in the tree and then add it in the database and in the result object
	 * This method is only called when we are not at the root level, so area_parent_id exists
	 * @parameter area_name: the name of the adding area. This method will set the case of the given name, according to the uniformFirstLetterCapitalized string method
	 * @parameter area_parent_id
	 */
	result.addArea = function(area_name, area_parent_id){
		var parent_index = this.findIndex(area_parent_id);
		var country_division = this[parent_index.division_index + 1].division_id;
		var name = area_name.uniformFirstLetterCapitalized();
		var field_saved_id = null;
		service.json("data_model","save_entity", {table:"Geographic_area", field_name:name, field_parent:area_parent_id, field_country_division:country_division}, function(res){
			if(!res) return;
			field_saved_id = res.key;
		},true);
		/*We now add the new area to result and tree*/
		var div_index = parent_index.division_index + 1;
		var ar_index = this[div_index].areas.length;
		this[div_index].areas[ar_index] = {area_id: field_saved_id, area_name: name, area_parent_id: area_parent_id};
		tr.buildItem(this, div_index, ar_index);
	};
	
	/**
	 * Add one area, but at the root level: there is no parent_id since we are at the root level
	 * @parameter area_name: the name of the adding area. This method will set the case of the given name, according to the uniformFirstLetterCapitalized string method
	 */
	result.addRoot = function(area_name){
		var name = area_name.uniformFirstLetterCapitalized();
		var field_saved_id = null;
		//var area_parent_id = null;
		var country_division = this[0].division_id;
		service.json("data_model","save_entity", {table:"Geographic_area", field_name:name, field_parent:null, field_country_division:country_division}, function(res){
			if(!res) return;
			field_saved_id = res.key;
		},true);
		var div_index = 0;
		var ar_index = this[div_index].areas.length;
		this[div_index].areas[ar_index] = {area_id: field_saved_id, area_name: name, area_parent_id: null};
		tr.buildItem(this, div_index, ar_index);
	};
	
	/**
	* Get the index in the result object of the given area
	* @method set_geographic_area#findIndex
	* @parameter area_id
	 * @returns object {division_index: , area_index: }
	 */
	result.findIndex = function(area_id){
		var index ={};
		for(var l = 0; l < this.length; l++){
			for(var m =0; m < this[l].areas.length; m++){
				if(this[l].areas[m] != null){
					if(this[l].areas[m].area_id == area_id){
						index = {division_index:l, area_index:m};
						break;
					}
				}
			}
		}
		return index;
	};
	
	/**
	* Get all the children (just one generation after)
	* @method set_geographic_area#findChildren
	* @parameter area_id
	 * @returns empty array if no child, else array of objects {division_id: ,area_id: }
	 */
	result.findChildren = function(area_id){
		var index = this.findIndex(area_id);
		var division_index = index.division_index;
		var children = [];
		
		if (division_index == this.length -1){
			return children;
		}
		else{
			if(this[division_index +1].areas.length == 0){return children;}
			else{
				var k=0;
				for(var n = 0; n < this[division_index +1].areas.length; n++){
					if(this[division_index +1].areas[n] != null){
						if(this[division_index +1].areas[n].area_parent_id == area_id){
							children[k] = {division_id:this[division_index +1].division_id, area_id:this[division_index +1].areas[n].area_id};
							k++;
						}
					}
				}
				return children;
			}
		}
	};
	
	/**
	* Get the parent of the given area
	* @method set_geographic_area#findParent
	* @parameter area_id
	 * @returns null if we are at the root level. Else return an object {division_id: ,area_id: }
	 */
	result.findParent = function(area_id){
		var parent ={};
		var index = this.findIndex(area_id);
		if(index.division_index == 0){
			parent = null;
		}
		else{
			for(var i=0; i < this[index.division_index -1].areas.length; i++){
				if(this[index.division_index -1].areas[i] != null && this[index.division_index].areas[index.area_index] != null){
					if(this[index.division_index -1].areas[i].area_id == this[index.division_index].areas[index.area_index].area_parent_id){
						parent = {division_id:this[index.division_index -1].division_id, area_id:this[index.division_index -1].areas[i].area_id};
						break;
					}
				}
			}
		}
		return parent;
	};
	
	/**
	* Create editable cells in the tree
	 * This method will add checkings to the editable cell. Thus, before saving, methods checkVisible and checkUnicity are called
	 * @method set_geographic_area#createEditable
	 */
	result.createEditable = function(division_index, area_index){
		var div = document.createElement('div');
		div.style.display ='inline-block';
		var container = document.getElementById(this[division_index].areas[area_index].area_id);
		var edit = new editable_cell(div, 'Geographic_area', 'name', this[division_index].areas[area_index].area_id, 'field_text', null, this[division_index].areas[area_index].area_name);
		var area_parent_id = this[division_index].areas[area_index].area_parent_id;
		var parent_name = null;
		if(area_parent_id != null){
			parent_index = this.findIndex(area_parent_id);
			parent_name = this[parent_index.division_index].areas[parent_index.area_index].area_name;
		}
		else parent_name = country_name;
		edit.onsave = function(text){
						if(text.checkVisible() && result.checkUnicity(text, "area", area_parent_id, null)){
							/*We update result*/
							result[division_index].areas[area_index].area_name = text.uniformFirstLetterCapitalized();
							return text.uniformFirstLetterCapitalized();
						}
						if(!text.checkVisible()){
							error_dialog("You must enter at least one visible caracter");
							return result[division_index].areas[area_index].area_name;
						}
						else {error_dialog(parent_name + " already has one child called " + text);
							return result[division_index].areas[area_index].area_name;
						}
						};
		container.appendChild(div);
	};
	
	/**
	 * This method builds the table to manage the country divisions, based on the result object
	 * The remove button is not added at the root level in order to avoid removing all the data about a country
	 * The add button is only added in the footer: it is not possible to add a division between two others
	 * All the divisions name are editable cells: as previously, the methods checkVisible and checkUnicity are called before saving
	 */
	result.buildTableDivisions = function(){
		var container = document.getElementById('manage_divisions');
		var table = document.createElement('table');
		table.id = "table_manage_divisions";
		var tbody = document.createElement('tbody');
		var thead = document.createElement('thead');
		var tfoot = document.createElement('tfoot');
		//var th = document.createElement('th');
		//var th_remove = document.createElement('th');
		//th.innerHTML = "Manage country divisions";
		//var tr_header = document.createElement('tr');
		//tr_header.appendChild(th);
		//tr_header.appendChild(th_remove);
		//thead.appendChild(tr_header);
		table.appendChild(thead);
		if(result !={}){
			for(var i = 0; i < this.length; i++){
				var division_id = this[i].division_id;
				var tr = document.createElement('tr');
				var td = document.createElement('td');
				var td_remove = document.createElement('td');
				if(i > 0){
					result.buttonRemoveDivision(td_remove, division_id);
				}
				var div = document.createElement('div');
				var edit = new editable_cell(div, 'Country_division', 'name', division_id, 'field_text', null, this[i].division_name);
				edit.division_name = result[i].division_name;
				edit.division_index = i;
				edit.onsave = function(text){
					if(result.checkUnicity(text,null,null,"division") && text.checkVisible()){
						/*We update result*/
						result[edit.division_index].division_name = text.uniformFirstLetterCapitalized();
						return text.uniformFirstLetterCapitalized();
					}
					if(!text.checkVisible()){
						error_dialog("You must enter at least one visible caracter");
						return edit.division_name;
					}
					if(!result.checkUnicity(text,null,null,"division")){
						error_dialog("The country "+country_name+" already has a division called "+ text);
						return edit.division_name;
					}
				};
				td.appendChild(div);
				tr.appendChild(td);
				tr.appendChild(td_remove);
				tbody.appendChild(tr);
			}
		}
		table.appendChild(tbody);
		var tr_foot = document.createElement('tr');
		var td_foot = document.createElement('td');
		var add_button = document.createElement('div');
		add_button.className = 'button';
		add_button.innerHTML = "<img src='"+theme.icons_16.add+"'/> Append a new division";
		add_button.onclick = function(){result.startAddDivision();};
		td_foot.appendChild(add_button);
		tr_foot.appendChild(td_foot);
		tfoot.appendChild(tr_foot);
		table.appendChild(tfoot);
		container.appendChild(table);
		var form_reload = document.createElement('form');
		form_reload.id = "form_reload";
		form_reload.action = '';
		var form_reload_input = document.createElement("input");
		form_reload_input.type = "hidden";
		form_reload_input.name = "toto";
		form_reload_input.value = '';
		form_reload.appendChild(form_reload_input);
		container.appendChild(form_reload);
	};
	
	/**
	 * Method called to manage the add division button
	 * The name of the added division is checked by the checkVisible and checkUnicity methods
	 */
	result.startAddDivision = function(){
		input_dialog(theme.icons_16.question,
					"Add a new division",
					"Add a new geographic division to this country. You will be redirected after submitting.",
					"",
					50,
					function(text){
						if(text.checkVisible()){ 
							if(!result.checkUnicity(text, null, null, "division")){ return "This division name is already set for this country";}
							else return;
						}
						else return "You must enter at least one visible caracter";
					},
					function(text){
						if(text) result.addDivision(text.uniformFirstLetterCapitalized());
					});
	};
	
	/**
	 * Method which is called by the add division button
	 * This method add the division in the database
	 * No need to update the tree nor result object since the page is reloaded after submitting
	 * @parameter division_name
	 */
	result.addDivision = function(division_name){
		var parent_index = this.length -1;
		var parent_id = this[parent_index].division_id;
		service.json("data_model","save_entity", {table:"Country_division", field_name:division_name, field_parent:parent_id, field_country:country_id}, function(res){
			if(!res) return;
		},true);
		/*We refresh the page*/
		document.getElementById('form_reload').submit();
	};
	
	/**
	 * Method to create a remove division button
	 * @parameter td_remove the cell where the button should be added
	 * @parameter division_id the id that will be removed by clicking on the button
	 */
	result.buttonRemoveDivision = function(td_remove, division_id){
		var remove_button = document.createElement('div');
		remove_button.className = "button";
		remove_button.innerHTML = "<img src ='"+theme.icons_16.remove+"'/>";
		remove_button.onclick = function(){result.askRemoveDivision(division_id);};
		td_remove.appendChild(remove_button);
	};
	
	/**
	 * Method asking the user to confirm the removing
	 * @parameter division_id
	 */
	result.askRemoveDivision = function (division_id){
		confirm_dialog("Are you sure you want to delete this division? All its children will be removed, even the geographic areas",
						function(text){if(text) result.removeDivision(division_id);}
						);
	};
	
	/**
	 * Remove one division from the database (and all the areas, divisions which are linked by a foreign key)
	 * Refresh the page after processing
	 * @parameter division_id
	 */
	result.removeDivision = function(division_id){
		service.json("data_model","remove_row",{table:"Country_division", row_key:division_id}, function(res){
			if(!res) return;
		},true);
		/*We refresh the page*/
		document.getElementById('form_reload').submit();
	};
	
	/**
	 * Method called before adding a child to an area or a division
	 * @parameter {string} text
	 * @parameter {string} area
	 * @parameter area_parent_id the id of the current area to which we are trying to insert a child. area_parameter == null means we are at the root level
	 * @parameter {string} division
	 * if called with area != null, means the user wants to check the unicity of an area name. In this case, this method will compare text (after lower case) to all the children of the given parent
	 * if called with division != null, means the user wants to check the unicity of a division name: just compare text with all the divisions names
	 * @returns {boolean} true if unique, else false
	 */
	result.checkUnicity = function(text, area, area_parent_id, division){
		name = text.toLowerCase();
		var is_unique = true;
		if(area != null){
			var parent_index = {};
			if(area_parent_id == null){
				parent_index.division_index = null;
			}
			else{
				parent_index = this.findIndex(area_parent_id);
			}
			if(parent_index.division_index != null){
				for(var i = 0; i < result[parent_index.division_index + 1].areas.length; i++){
					if(result[parent_index.division_index + 1].areas[i] != null){
						var temp_name = result[parent_index.division_index +1].areas[i].area_name.toLowerCase();
						var temp_parent = this.findParent(result[parent_index.division_index +1].areas[i].area_id);
						if(area_parent_id == temp_parent.area_id && temp_name == name){
							is_unique = false;
							break;
						}
					}
				}
			}
			else{
				for(var i = 0; i < result[0].areas.length; i++){
					if(result[0].areas[i] != null){
						var temp_name = result[0].areas[i].area_name.toLowerCase();
						if(temp_name == name){
							is_unique = false;
							break;
						}
					}
				}
			}
		}
		if(division != null){
			for(var i = 0; i < result.length; i++){
				var temp_name = result[i].division_name.toLowerCase();
				if(temp_name == name){
					is_unique = false;
					break;
				}
			}
		}
		return is_unique;
	};
	
	if (tr != null && editable != null) everything_ready();
});


require('tree.js',function(){
	tr = new tree('set_geography_area');
	
	/**
	* Create the add button on the tree, except for the last division
	* @method set_geography_area#tree#addAddButton
	* @parameter r = result object
	*/
	tr.addAddButton = function(r, division_index, area_index){
		if(division_index == null && area_index == null){
			var add_button = document.createElement('IMG');
			add_button.className = 'button';
			add_button.src = theme.icons_10.add;
			add_button.style.verticalAlign = "bottom";
			add_button.style.padding = "2px";
			add_button.title = "Create a sub-area";
			add_button.onclick = function(){tr.addChild(r, null, 'root');};
			var div = document.getElementById('root');
			div.appendChild(add_button);
		}
		else{
			if(division_index != r.length -1){
				var add_button = document.createElement('IMG');
				add_button.className = 'button';
				add_button.src = theme.icons_10.add;
				add_button.style.verticalAlign = "bottom";
				add_button.style.padding = "2px";
				add_button.title = "Create a sub-area";
				add_button.area_parent_id = r[division_index].areas[area_index].area_id;
				add_button.onclick = function(){tr.addChild(r, add_button.area_parent_id);};
				var div = document.getElementById(r[division_index].areas[area_index].area_id);
				div.appendChild(add_button);
			}
		}
	};
	
	/**
	 * Create the remove button in the tree
	 * The root case is treated separately
	 * @parameter r the result object
	 * @parameter division_index the level in the tree
	 * @parameter area_index the index of the current area
	 */
	tr.addRemoveButton = function(r, division_index, area_index){
		var remove_button = document.createElement('IMG');
		remove_button.className = 'button';
		remove_button.src = theme.icons_10.remove;
		remove_button.style.verticalAlign = "bottom";
		remove_button.style.padding = "2px";
		remove_button.title = "Remove this area and all its content";
		var div = null;
		if(division_index == null && area_index == null){
			div = document.getElementById('root');
		}
		else{
			div = document.getElementById(r[division_index].areas[area_index].area_id);
			remove_button.area_id = r[division_index].areas[area_index].area_id;
			remove_button.onclick = function(){tr.removeChildren(r, remove_button.area_id);};
		}
		div.appendChild(remove_button);
	};
	
	/**
	 * Ask for user opinion before removing one area
	 * @parameter r the result object
	 * @parameter area_id the id of the area to remove
	 */
	tr.removeChildren = function(r, area_id){
		confirm_dialog("Are you sure you want to delete this area? All its children will also be deleted.", function(text){if(text == true) r.startRemove(area_id);});
	};
	
	/**
	 * Method called to add an area on the tree
	 * This method also calls checkVisible and checkUnicity methods
	 * @parameter r the result object
	 * @parameter area_parent_id the id of the area to which the user is trying to add a child
	 * @parameter root if root == 'root' means we are at the root level, so will call the addRoot method. Else, root == null
	 */
	tr.addChild = function(r, area_parent_id, root){
		if(root == null){
			input_dialog(theme.icons_16.question,
									'Add a new child',
									'Enter the area name',
									'',
									50,
									function(text){
										if(text.checkVisible()){ 
											if(!r.checkUnicity(text, "area", area_parent_id, null)){ return "The current area already has a child with this name";}
											else return;
										}
										else return "You must enter at least one visible caracter";
									},
									function(text){if (text) r.addArea(text ,area_parent_id);});
		}
		if(root == 'root'){
			input_dialog(theme.icons_16.question,
									'Add a new child',
									'Enter the area name',
									'',
									50,
									function(text){
										if(text.checkVisible()){ 
											if(!r.checkUnicity(text, "area", area_parent_id, null)){ return "The current area already has a child with this name";}
											else return;
										}
										else return "You must enter at least one visible caracter";
									},
									function(text){if (text) r.addRoot(text);});
		}
	};
	
	/**
	 * Build the whole tree, adding a root level
	 * @parameter r the result object
	 */
	tr.buildTree = function (r){
		tr.addColumn(new TreeColumn(""));
		/*We add a root level to be able to manage the first level(add, remove)*/
		var div = document.createElement('div');
		div.id ='root';
		div.style.display ='inline-block';
		div.innerHTML = country_name;
		var item = new TreeItem([new TreeCell(div)]);
		this.root = item;
		this.addItem(item);
		this.addAddButton(r,null,null);
		/*We manage the other levels*/
		for(var i =0; i < r.length; i++){
			for(var j =0; j < r[i].areas.length; j++){
				if(r[i].areas[j] != null){
					tr.buildItem(r,i,j);
				}
			}
		}		
	};
	
	/**
	 * Build an item in the tree
	 * @parameter r the result object
	 * @parameter division_index
	 * @parameter area_index
	 */
	tr.buildItem = function(r, division_index, area_index){
		var div = document.createElement('div');
		div.id = r[division_index].areas[area_index].area_id;
		div.style.display ='inline-block';
		r[division_index].areas[area_index].item = new TreeItem([new TreeCell(div)]);
		var parent_index = r.findIndex(r[division_index].areas[area_index].area_parent_id);
		if(typeof(parent_index.division_index) != 'undefined'){
			r[parent_index.division_index].areas[parent_index.area_index].item.addItem(r[division_index].areas[area_index].item);
		}
		else{
			this.root.addItem(r[division_index].areas[area_index].item);
		}
		r.createEditable(division_index, area_index);
		tr.addAddButton(r, division_index, area_index);
		tr.addRemoveButton(r, division_index, area_index);
	};
	
	if (result != null && editable != null) everything_ready();
});

require('editable_cell.js',function(){
	editable = 'ok';
	if (result != null && tr != null) everything_ready();
});

/**
 * Function called when all the javascripts is ready to initiate the page
 */
function everything_ready() {
	/*We create the tree*/
	tr.buildTree(result);
	/*We create the table to manage the divisions*/
	result.buildTableDivisions();
}
</script>
	<?php
	}
}

?>