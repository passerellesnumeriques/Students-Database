<?php
class page_set_geography_area extends Page {
	public function getRequiredRights(){
	// TODO
	return array();
	}
	public function execute(){
		if (!isset($_GET['country']) || $_GET['country'] == "") {
			echo "<div style='margin:10px'>Please select a country to edit</div>";
			return;
		}
		$country = SQLQuery::create()->select("Country")->where("id",$_GET["country"])->executeSingleRow();
		$this->addJavascript("/static/widgets/splitter_vertical/splitter_vertical.js");
		$this->onload("new splitter_vertical('page_split',0.25);");
		$this->addJavascript("/static/widgets/section/section.js");
		$this->onload("window.divisions_section = sectionFromHTML('manage_divisions_section');");
		$this->onload("sectionFromHTML('tree_section');");
		
		// example for province:
		// https://maps.googleapis.com/maps/api/geocode/json?address=Agusan%20del%20Norte&components=country:PH&sensor=false&key=AIzaSyBhG4Hn5zmbXcALGQtAPJDkUj2hDSZdVSU
?>
<div id='page_split' style='width:100%;height:100%'>
	<div style='overflow:auto;'>
		<div id='manage_divisions_section' title="Country Divisions" style='margin:10px'>
			<div id ='manage_divisions' ></div>
		</div>
	</div>
	<div style='height:100%;padding:10px'>
		<div id='tree_section' title="Geographic Areas" style='height:100%' fill_height="true">
			<div id='set_geography_area' style = "overflow:auto;height:100%"></div>
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
var lock = lock_screen(null, "Loading data for "+country_name+"...");
service.json("geography","get_country_data", {country_id:country_id}, function(res){
	unlock_screen(lock);
	if(!res) return;
	result = res;
	var res_size = 0;
	for(a in res)
		res_size++;
	var new_country = (res_size == 0) ? true : false;

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
		service.json("data_model","remove_row",{table:"GeographicArea", row_key:area_id}, function(res){
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
	result.addArea = function(area_name, parent_area, parent_division_index, ondone){
		var country_division = this[parent_division_index + 1].division_id;
		var name = area_name.uniformFirstLetterCapitalized();
		var field_saved_id = null;
		var t=this;
		service.json("data_model","save_entity", {table:"GeographicArea", field_name:name, field_parent:parent_area.area_id, field_country_division:country_division}, function(res){
			if(!res) return;
			field_saved_id = res.key;
			/*We now add the new area to result and tree*/
			var div_index = parent_division_index + 1;
			var ar_index = t[div_index].areas.length;
			t[div_index].areas[ar_index] = {area_id: field_saved_id, area_name: name, area_parent_id: parent_area.area_id};
			tr.createItem(t, div_index, ar_index, parent_area.item);
			ondone();
		});
	};
	
	/**
	 * Add one area, but at the root level: there is no parent_id since we are at the root level
	 * @parameter area_name: the name of the adding area. This method will set the case of the given name, according to the uniformFirstLetterCapitalized string method
	 */
	result.addRoot = function(area_name, ondone){
		var name = area_name.uniformFirstLetterCapitalized();
		var field_saved_id = null;
		//var area_parent_id = null;
		var country_division = this[0].division_id;
		var t=this;
		service.json("data_model","save_entity", {table:"GeographicArea", field_name:name, field_parent:null, field_country_division:country_division}, function(res){
			if(!res) return;
			field_saved_id = res.key;
			var div_index = 0;
			var ar_index = t[div_index].areas.length;
			t[div_index].areas[ar_index] = {area_id: field_saved_id, area_name: name, area_parent_id: null};
			tr.createItem(t, div_index, ar_index, tr.root);
			ondone();
		});
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
		var edit = new editable_cell(div, 'GeographicArea', 'name', this[division_index].areas[area_index].area_id, 'field_text', null, this[division_index].areas[area_index].area_name);
		var area_parent_id = this[division_index].areas[area_index].area_parent_id;
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
			else {
				error_dialog("A sub-area already exists with name " + text);
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
				var edit = new editable_cell(div, 'CountryDivision', 'name', division_id, 'field_text', null, this[i].division_name);
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
		var add_button = document.createElement('BUTTON');
		add_button.className = 'action';
		add_button.innerHTML = "<img src='"+theme.icons_16.add+"'/> Append a new division";
		add_button.onclick = function(){result.startAddDivision();};
		window.divisions_section.addToolBottom(add_button);
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
		var parent_index = null;
		var parent_id = null;
		if (!new_country){
			parent_index = this.length -1;
			parent_id = this[parent_index].division_id;
		}
		service.json("data_model","save_entity", {table:"CountryDivision", field_name:division_name, field_parent:parent_id, field_country:country_id}, function(res){
			if(!res) return;
		},true);
		/*We refresh the page*/
		location.reload();
	};
	
	/**
	 * Method to create a remove division button
	 * @parameter td_remove the cell where the button should be added
	 * @parameter division_id the id that will be removed by clicking on the button
	 */
	result.buttonRemoveDivision = function(td_remove, division_id){
		var remove_button = document.createElement('BUTTON');
		remove_button.className = "flat";
		remove_button.innerHTML = "<img src ='"+theme.icons_16.remove+"'/>";
		remove_button.title = "Remove this division";
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
		service.json("data_model","remove_row",{table:"CountryDivision", row_key:division_id}, function(res){
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
					if (result[parent_index.division_index + 1].areas[i] == null) continue;
					if (result[parent_index.division_index + 1].areas[i].area_parent_id != area_parent_id) continue;
					var temp_name = result[parent_index.division_index +1].areas[i].area_name.toLowerCase();
					if(temp_name == name){
						is_unique = false;
						break;
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
	tr.table.style.margin = "10px";
	
	/**
	* Create the add button on the tree, except for the last division
	* @method set_geography_area#tree#addAddButton
	* @parameter r = result object
	*/
	tr.addAddButton = function(r, division_index, area_index, div){
		if(division_index == null && area_index == null){
			var add_button = document.createElement('BUTTON');
			add_button.className = 'flat small_icon';
			add_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
			add_button.title = "Create sub-areas";
			add_button.onclick = function(){tr.addChild(r, null);};
			div.appendChild(add_button);
		}
		else{
			if(division_index != r.length -1){
				var add_button = document.createElement('BUTTON');
				add_button.className = 'flat small_icon';
				add_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
				add_button.title = "Create sub-areas";
				add_button.area = r[division_index].areas[area_index];
				add_button.onclick = function(){tr.addChild(r, this.area, division_index);};
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
	tr.addRemoveButton = function(r, division_index, area_index, div){
		var remove_button = document.createElement('BUTTON');
		remove_button.className = 'flat small_icon';
		remove_button.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
		remove_button.title = "Remove this area and all its content";
		if(division_index != null && area_index != null){
			remove_button.area_id = r[division_index].areas[area_index].area_id;
			remove_button.onclick = function(){tr.removeChildren(r, remove_button.area_id);};
			div.appendChild(remove_button);
		}
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
	tr.addChild = function(r, parent_area, parent_division_index){
		var content = document.createElement("DIV");
		content.style.padding = "10px";
		content.appendChild(document.createTextNode("Please enter new areas (one by line):"));
		content.appendChild(document.createElement("BR"));
		var text_area = document.createElement("TEXTAREA");
		text_area.rows = 10;
		text_area.cols = 50;
		content.appendChild(text_area);
		require("popup_window.js",function() {
			var popup = new popup_window("New Geographic Area", null, content);
			popup.addOkCancelButtons(function() {
				popup.freeze("Checking names...");
				setTimeout(function(){
					var text = text_area.value;
					var lines = text.split("\n");
					var names = [];
					for (var i = 0; i < lines.length; ++i) {
						var name = lines[i].trim();
						if (!name.checkVisible()) continue;
						if (!r.checkUnicity(name, "area", parent_area.area_id, null)) {
							alert("Area already exists: "+name);
							continue;
						}
						names.push(name);
					}
					popup.unfreeze();
					popup.freeze_progress("Creation of "+names.length+" Geographic Area(s)", names.length, function(span,pb) {
						var done = 0;
						for (var i = 0; i < names.length; ++i) {
							var added = function() {
								pb.addAmount(1);
								done++;
								if (done == names.length) {
									popup.close();
								}
							};
							if (parent_area != null)
								r.addArea(names[i], parent_area, parent_division_index, added);
							else
								r.addRoot(names[i], added);
								
						}
					});
				},1);
			});
			popup.show();
		});
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
		div.appendChild(document.createTextNode(country_name));
		var item = new TreeItem([new TreeCell(div)]);
		this.root = item;
		this.addItem(item);
		this.addAddButton(r,null,null,div);
		/*We manage the other levels*/
		for(var division_index=0; division_index < r.length; division_index++){
			for (var area_index = 0; area_index < r[division_index].areas.length; ++area_index) {
				var area = r[division_index].areas[area_index];
				var parent_item;
				if (division_index == 0) parent_item = this.root;
				else for (var i = 0; i < r[division_index-1].areas.length; ++i)
					if (r[division_index-1].areas[i].area_id == area.area_parent_id) {
						parent_item = r[division_index-1].areas[i].item;
						break;
					}
				tr.createItem(r, division_index, area_index, parent_item);
			}
		}
	};

	tr.createItem = function(r, division_index, area_index, parent_item) {
		var div = document.createElement('DIV');
		div.style.display ='inline-block';
		div.id = r[division_index].areas[area_index].area_id;
		var item = new TreeItem([new TreeCell(div)]);
		r[division_index].areas[area_index].item = item;
		parent_item.addItem(item);
		r.createEditable(division_index, area_index);
		tr.addAddButton(r, division_index, area_index, div);
		tr.addRemoveButton(r, division_index, area_index, div);
		return item;
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