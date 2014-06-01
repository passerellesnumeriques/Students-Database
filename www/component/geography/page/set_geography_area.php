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
		$this->onload("window.areas_section = sectionFromHTML('tree_section');");
		
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
	var res_size = res.length;
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
			if(res) {
				field_saved_id = res.key;
				/*We now add the new area to result and tree*/
				var div_index = parent_division_index + 1;
				var ar_index = t[div_index].areas.length;
				t[div_index].areas[ar_index] = {area_id: field_saved_id, area_name: name, area_parent_id: parent_area.area_id};
				parent_area.item.addItem(tr.createItem(t, div_index, ar_index));
				window.span_nb_total.innerHTML = parseInt(window.span_nb_total.innerHTML)+1;
			}
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
			if(res) {
				field_saved_id = res.key;
				var div_index = 0;
				var ar_index = t[div_index].areas.length;
				t[div_index].areas[ar_index] = {area_id: field_saved_id, area_name: name, area_parent_id: null};
				tr.root.addItem(tr.createItem(t, div_index, ar_index));
			}
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
		var index = this.findIndex(area_id);
		if(index.division_index == 0) return null;
		return this.findParentIn(this[index.division_index].areas[index.area_index], index.division_index-1);
	};
	result.findParentIn = function(area, division_index){
		for(var i=0; i < this[division_index].areas.length; i++)
			if(this[division_index].areas[i].area_id == area.area_parent_id)
				return {division_index:division_index,division_id:this[division_index].division_id, area_id:this[division_index].areas[i].area_id, area_index:i};
	};
	
	/**
	* Create editable cells in the tree
	 * This method will add checkings to the editable cell. Thus, before saving, methods checkVisible and checkUnicity are called
	 * @method set_geographic_area#createEditable
	 */
	result.createEditable = function(container, division_index, area_index){
		var div = document.createElement('div');
		div.style.display ='inline-block';
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

	result.dialogCoordinates = function(division_index, area_index, onnext) {
		var area = this[division_index].areas[area_index];
		var t=this;
		
		var parents = [];
		var d = division_index-1;
		var p = area;
		while (d >= 0) {
			p = this.findParentIn(p, d);
			if (p == null) break;
			var pa = {area:this[d].areas[p.area_index],division_index:d,area_index:p.area_index};
			p = pa.area;
			d--;
			parents.splice(0,0,pa);
		}
		parents.push({area:area,division_index:division_index,area_index:area_index});

		var popup;
		var createLink = function(name, division_index, area_index, indicate_if_coordinates) {
			var link = document.createElement("A");
			link.href = "#";
			link.className = "black_link";
			link.appendChild(document.createTextNode(name));
			if (indicate_if_coordinates)
				link.appendChild(document.createTextNode(" ("+(t[division_index].areas[area_index].north ? "has" : "no")+" coord.)"));
			link.onclick = function() {
				t.dialogCoordinates(division_index,area_index,onnext);
				popup.close();
				return false;
			};
			return link;
		};
		var content = document.createElement("DIV");
		var title = document.createElement("DIV");
		title.style.textAlign = "center";
		title.style.fontWeight = "bold";
		title.style.fontSize = "14pt";
		for (var i = 0; i < parents.length; ++i) {
			if (i > 0) title.appendChild(document.createTextNode(" > "));
			title.appendChild(createLink(parents[i].area.area_name, parents[i].division_index, parents[i].area_index));
		}
		content.appendChild(title);
		
		var table = document.createElement("TABLE");
		content.appendChild(table);
		require("popup_window.js", function() {
			popup = new popup_window("Edit Geographic Coordinates", "/static/geography/geography_16.png", content);
		
			var field_north, field_south, field_east, field_west;
			var map;
			var rect = null;
			var check_children, check_siblings, check_parent;
			var children_rects = null, siblings_rects = null, parent_rect = null;
			var change_from_map = false;
			var maxBounds = function(b1,b2) {
				var north1 = b1.getNorthEast().lat();
				var south1 = b1.getSouthWest().lat();
				var west1 = b1.getSouthWest().lng();
				var east1 = b1.getNorthEast().lng();
				var north2 = b2.getNorthEast().lat();
				var south2 = b2.getSouthWest().lat();
				var west2 = b2.getSouthWest().lng();
				var east2 = b2.getNorthEast().lng();
				return new window.top.google.maps.LatLngBounds(
						new window.top.google.maps.LatLng(Math.min(south1, south2), Math.min(west1, west2)),
						new window.top.google.maps.LatLng(Math.max(north1, north2), Math.max(east1, east2))
					);
			};
			var rect_tooltip = function(rect, tooltip) {
				window.top.google.maps.event.addListener(rect, 'click', function() {
					if (rect._iw) { rect._iw.close(); rect._iw = null; return; }
					var bounds = rect.getBounds();
					var lat = bounds.getSouthWest().lat()+(bounds.getNorthEast().lat()-bounds.getSouthWest().lat())/2;
					var lng = bounds.getSouthWest().lng()+(bounds.getNorthEast().lng()-bounds.getSouthWest().lng())/2;
					rect._iw = new window.top.google.maps.InfoWindow({
						content: tooltip,
						position: new window.top.google.maps.LatLng(lat,lng)
					});
					rect._iw.open(map);
				});
			};
			var field_changed = function() {
				if (!map) return;
				var north = field_north.getCurrentData(); if (north != null) north = parseFloat(north);
				var south = field_south.getCurrentData(); if (south != null) south = parseFloat(south);
				var west = field_west.getCurrentData(); if (west != null) west = parseFloat(west);
				var east = field_east.getCurrentData(); if (east != null) east = parseFloat(east);
				var bounds = null;
				if (north && south && west && east) {
					var sw = new window.top.google.maps.LatLng(south, west);
					var ne = new window.top.google.maps.LatLng(north, east);
					if (rect == null) {
						rect = new window.top.google.maps.Rectangle({
							bounds: new window.top.google.maps.LatLngBounds(sw, ne),
							strokeColor: "#6060F0",
							strokeWeight: 2,
							strokeOpacity: 0.8,
							fillColor: "#D0D0F0",
							fillOpacity: 0.3,
							editable: true,
							map:map
						});
						window.top.google.maps.event.addListener(rect, 'bounds_changed', function() {
							change_from_map = true;
							var bounds = rect.getBounds();
							field_north.setData(bounds.getNorthEast().lat());
							field_east.setData(bounds.getNorthEast().lng());
							field_south.setData(bounds.getSouthWest().lat());
							field_west.setData(bounds.getSouthWest().lng());
							change_from_map = false;
						});
					} else {
						if (!change_from_map) {
							rect.setBounds(new window.top.google.maps.LatLngBounds(sw, ne));
						}
					}
					bounds = new window.top.google.maps.LatLngBounds(sw, ne);
				} else if (rect) {
					rect.setMap(null);
					rect = null;
				}
				if (check_children && check_children.checked) {
					if (children_rects == null) {
						var children = [];
						children_rects = [];
						for (var i = 0; i < result[division_index+1].areas.length; ++i)
							if (result[division_index+1].areas[i].area_parent_id == area.area_id)
								children.push(result[division_index+1].areas[i]);
						for (var i = 0; i < children.length; ++i) {
							if (!children[i].north) continue; // no coordinates
							var r = new window.top.google.maps.Rectangle({
								bounds: new window.top.google.maps.LatLngBounds(
									new window.top.google.maps.LatLng(parseFloat(children[i].south), parseFloat(children[i].west)),
									new window.top.google.maps.LatLng(parseFloat(children[i].north), parseFloat(children[i].east))
								),
								strokeColor: "#00F000",
								strokeWeight: 2,
								strokeOpacity: 0.7,
								fillColor: "#D0F0D0",
								fillOpacity: 0.2,
								editable: false,
								map:map
							});
							rect_tooltip(r, children[i].area_name);
							children_rects.push(r);
						}
					}
					for (var i = 0; i < children_rects.length; ++i)
						bounds = bounds != null ? maxBounds(bounds, children_rects[i].getBounds()) : children_rects[i].getBounds();
				} else if (children_rects != null) {
					for (var i = 0; i < children_rects.length; ++i)
						children_rects[i].setMap(null);
					children_rects = null;
				}
				var parent = null;
				if (check_parent && check_parent.checked) {
					if (parent_rect == null) {
						for(var i=0; i < result[division_index-1].areas.length; i++)
							if(result[division_index-1].areas[i].area_id == area.area_parent_id) { parent = result[division_index-1].areas[i]; break; }
						parent_rect = [];
						if (parent && parent.north) { 
							var r = new window.top.google.maps.Rectangle({
								bounds: new window.top.google.maps.LatLngBounds(
									new window.top.google.maps.LatLng(parseFloat(parent.south), parseFloat(parent.west)),
									new window.top.google.maps.LatLng(parseFloat(parent.north), parseFloat(parent.east))
								),
								strokeColor: "#F0F000",
								strokeWeight: 2,
								strokeOpacity: 0.7,
								fillColor: "#F0F0D0",
								fillOpacity: 0.2,
								editable: false,
								map:map
							});
							parent_rect.push(r);
						}
					}
					for (var i = 0; i < parent_rect.length; ++i)
						bounds = bounds != null ? maxBounds(bounds, parent_rect[i].getBounds()) : parent_rect[i].getBounds();
				} else if (parent_rect != null) {
					for (var i = 0; i < parent_rect.length; ++i)
						parent_rect[i].setMap(null);
					parent_rect = null;
				}
				if (check_siblings && check_siblings.checked) {
					if (siblings_rects == null) {
						var siblings = [];
						siblings_rects = [];
						for (var i = 0; i < result[division_index].areas.length; ++i)
							if (result[division_index].areas[i].area_parent_id == area.area_parent_id && result[division_index].areas[i].area_id != area.area_id)
								siblings.push(result[division_index].areas[i]);
						for (var i = 0; i < siblings.length; ++i) {
							if (!siblings[i].north) continue; // no coordinates
							var r = new window.top.google.maps.Rectangle({
								bounds: new window.top.google.maps.LatLngBounds(
									new window.top.google.maps.LatLng(parseFloat(siblings[i].south), parseFloat(siblings[i].west)),
									new window.top.google.maps.LatLng(parseFloat(siblings[i].north), parseFloat(siblings[i].east))
								),
								strokeColor: "#800080",
								strokeWeight: 2,
								strokeOpacity: 0.7,
								fillColor: "#F0D0F0",
								fillOpacity: 0.2,
								editable: false,
								map:map
							});
							rect_tooltip(r, siblings[i].area_name);
							siblings_rects.push(r);
						}
					}
					for (var i = 0; i < siblings_rects.length; ++i)
						bounds = bounds != null ? maxBounds(bounds, siblings_rects[i].getBounds()) : siblings_rects[i].getBounds();
				} else if (siblings_rects != null) {
					for (var i = 0; i < siblings_rects.length; ++i)
						siblings_rects[i].setMap(null);
					siblings_rects = null;
				}
				if (!change_from_map && bounds)
					map.fitBounds(bounds);
			};

			var processSearchResults = function(results, div) {
				if (!results || results.length == 0) {
					div.innerHTML = "<center><i>No result</i></center>";
					return;
				}
				div.style.textAlign = "left";
				var ul = document.createElement("UL");
				ul.style.padding = "0px";
				ul.style.paddingLeft = "20px";
				div.removeAllChildren();
				div.appendChild(ul);
				for (var i = 0; i < results.length; ++i) {
					var li = document.createElement("LI");
					ul.appendChild(li);
					var link = document.createElement("A");
					link.href = "#";
					link.className = "black_link";
					link.style.fontWeight = "bold";
					link.title = "Click to use the coordinates of this one";
					link.appendChild(document.createTextNode(results[i].name));
					li.appendChild(link);
					li.appendChild(document.createTextNode(" ("+results[i].full_name+") "));
					link.res = results[i];
					link.rect = null;
					link.onclick = function() {
						if (this.rect) { this.rect.setMap(null); this.rect = null; }
						if (this.res.north) {
							field_north.setData(this.res.north);
							field_south.setData(this.res.south);
							field_west.setData(this.res.west);
							field_east.setData(this.res.east);
						} else {
							field_north.setData(parseFloat(this.res.lat)+0.1);
							field_south.setData(parseFloat(this.res.lat)-0.1);
							field_west.setData(parseFloat(this.res.lng)-0.1);
							field_east.setData(parseFloat(this.res.lng)+0.1);
						}
						return false;
					};
					link.onmouseover = function() {
						if (this.rect) return;
						if (!map) return;
						var north = this.res.north ? parseFloat(this.res.north) : parseFloat(this.res.lat)+0.1;
						var south = this.res.north ? parseFloat(this.res.south) : parseFloat(this.res.lat)-0.1;
						var west = this.res.north ? parseFloat(this.res.west) : parseFloat(this.res.lng)-0.1;
						var east = this.res.north ? parseFloat(this.res.east) : parseFloat(this.res.lng)+0.1;
						var bounds = new window.top.google.maps.LatLngBounds(
								new window.top.google.maps.LatLng(south, west), 
								new window.top.google.maps.LatLng(north, east)
							);
						this.rect = new window.top.google.maps.Rectangle({
							bounds: bounds,
							strokeColor: "#F06060",
							strokeWeight: 2,
							strokeOpacity: 0.6,
							fillColor: "#F0D0D0",
							fillOpacity: 0.2,
							editable: false,
							map:map
						});
						bounds = new window.top.google.maps.LatLngBounds(
								new window.top.google.maps.LatLng(south, west), 
								new window.top.google.maps.LatLng(north, east)
							);
						if (rect) bounds = maxBounds(bounds, rect.getBounds());
						map.panToBounds(bounds);
					};
					link.onmouseout = function() {
						if (this.rect) { this.rect.setMap(null); this.rect = null; }
					};
				}
			};

			popup.addSaveButton(function() {
				if (!field_north) return;
				var north = field_north.getCurrentData(); if (north != null) north = parseFloat(north);
				var south = field_south.getCurrentData(); if (south != null) south = parseFloat(south);
				var west = field_west.getCurrentData(); if (west != null) west = parseFloat(west);
				var east = field_east.getCurrentData(); if (east != null) east = parseFloat(east);
				if (!north || !south || !west || !east) {
					field_north.setData(null);
					field_south.setData(null);
					field_west.setData(null);
					field_east.setData(null);
					north = south = west = east = null;
				} else {
					if (south > north) { alert("You specified a south at the north of the north..."); return; }
					if (west > east) { alert("You specified a west at the east of the east..."); return; }
					popup.freeze("Saving coordinates...");
					var area = result[division_index].areas[area_index];
					service.json("data_model","save_entity",{
						table:"GeographicArea",
						key:area.area_id,
						lock:-1,
						field_north:north,
						field_south:south,
						field_west:west,
						field_east:east
					},function(res) {
						popup.unfreeze();
						if (res) {
							if (area.north) {
								if (!north) window.span_nb_have_coordinates.innerHTML = parseInt(window.span_nb_have_coordinates.innerHTML)-1;
							} else {
								if (north) window.span_nb_have_coordinates.innerHTML = parseInt(window.span_nb_have_coordinates.innerHTML)+1;
							}
							area.north = north;
							area.south = south;
							area.west = west;
							area.east = east;
							if (area.coordinates_button)
								tr.setCoordinateButton(area.coordinates_button, division_index, area_index);
						}
					});
				}
			});
			if (onnext) 
				popup.addNextButton(function() {
					popup.close();
					onnext();
				});
			popup.show();
			
			
			require([["typed_field.js","field_decimal.js"],"google_maps.js"], function() {
				var tr, td, td_map;
				table.appendChild(tr = document.createElement("TR"));

				tr.appendChild(td = document.createElement("TH"));
				td.appendChild(document.createTextNode("Coordinates:"));
				td.colSpan = 2;

				tr.appendChild(td_map = document.createElement("TD"));
				td_map.rowSpan = 6;

				tr.appendChild(td = document.createElement("TH"));
				td.rowSpan = 6;
				td.style.verticalAlign = "top";
				td.appendChild(document.createTextNode("Results from Internet:"));
				var div_title = document.createElement("DIV");
				div_title.innerHTML = "From GeoNames:";
				div_title.style.backgroundColor = "#D0D0D0";
				td.appendChild(div_title);
				var div_geonames = document.createElement("DIV");
				div_geonames.style.width = "300px";
				div_geonames.style.height = "120px";
				div_geonames.style.overflow = "auto";
				div_geonames.style.fontWeight = "normal";
				div_geonames.style.whiteSpace = "nowrap";
				div_geonames.innerHTML = "<img src='"+theme.icons_16.loading+"'/>";
				td.appendChild(div_geonames);
				div_title = document.createElement("DIV");
				div_title.innerHTML = "<img src='/static/google/google.png' style='vertical-align:bottom'/> From Google:";
				div_title.style.backgroundColor = "#D0D0D0";
				td.appendChild(div_title);
				var div_google = document.createElement("DIV");
				div_google.style.width = "300px";
				div_google.style.height = "120px";
				div_google.style.overflow = "auto";
				div_google.style.fontWeight = "normal";
				div_google.style.whiteSpace = "nowrap";
				div_google.innerHTML = "<img src='"+theme.icons_16.loading+"'/>";
				td.appendChild(div_google);

				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.style.whiteSpace = "nowrap";
				td.appendChild(document.createTextNode("Latitude North"));
				tr.appendChild(td = document.createElement("TD"));
				field_north = new field_decimal(area.north, true, {can_be_null:true,integer_digits:3,decimal_digits:6,min:-90,max:90});
				td.appendChild(field_north.getHTMLElement());
				field_north.fillWidth();
				field_north.onchange.add_listener(field_changed);

				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(document.createTextNode("Longitude West"));
				td.style.whiteSpace = "nowrap";
				tr.appendChild(td = document.createElement("TD"));
				field_west = new field_decimal(area.west, true, {can_be_null:true,integer_digits:3,decimal_digits:6,min:-180,max:180});
				td.appendChild(field_west.getHTMLElement());
				field_west.fillWidth();
				field_west.onchange.add_listener(field_changed);
				
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(document.createTextNode("Latitude South"));
				td.style.whiteSpace = "nowrap";
				tr.appendChild(td = document.createElement("TD"));
				field_south = new field_decimal(area.south, true, {can_be_null:true,integer_digits:3,decimal_digits:6,min:-90,max:90});
				td.appendChild(field_south.getHTMLElement());
				field_south.fillWidth();
				field_south.onchange.add_listener(field_changed);
				
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.appendChild(document.createTextNode("Longitude East"));
				td.style.whiteSpace = "nowrap";
				tr.appendChild(td = document.createElement("TD"));
				field_east = new field_decimal(area.east, true, {can_be_null:true,integer_digits:3,decimal_digits:6,min:-180,max:180});
				td.appendChild(field_east.getHTMLElement());
				field_east.fillWidth();
				field_east.onchange.add_listener(field_changed);

				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TD"));
				td.style.whiteSpace = "nowrap";
				td.colSpan = 2;
				var reset_button = document.createElement("BUTTON");
				reset_button.className = "action important";
				reset_button.innerHTML = "Reset";
				reset_button.onclick = function() {
					field_north.setData(null);
					field_south.setData(null);
					field_west.setData(null);
					field_east.setData(null);
					return false;
				};
				td.appendChild(reset_button);
				var map_button = document.createElement("BUTTON");
				map_button.className = "action";
				map_button.innerHTML = "Use map bounds";
				map_button.onclick = function() {
					var bounds = map.getBounds();
					field_north.setData(bounds.getNorthEast().lat());
					field_south.setData(bounds.getSouthWest().lat());
					field_west.setData(bounds.getSouthWest().lng());
					field_east.setData(bounds.getNorthEast().lng());
					return false;
				};
				td.appendChild(map_button);
				var parent_button = document.createElement("BUTTON");
				parent_button.className = "action";
				parent_button.innerHTML = "Use parent area";
				parent_button.onclick = function() {
					var parent = null;
					for(var i=0; i < result[division_index-1].areas.length; i++)
						if(result[division_index-1].areas[i].area_id == area.area_parent_id) { parent = result[division_index-1].areas[i]; break; }
					if (parent) {
						field_north.setData(parent.north);
						field_south.setData(parent.south);
						field_west.setData(parent.west);
						field_east.setData(parent.east);
					}
					return false;
				};
				td.appendChild(parent_button);
				td.appendChild(document.createElement("BR"));
				if (division_index < res_size-1) {
					check_children = document.createElement("INPUT");
					check_children.type = "checkbox";
					td.appendChild(check_children);
					td.appendChild(document.createTextNode(" Display sub-areas (green)"));
					check_children.onchange = function() {
						field_changed();
					};
				}
				td.appendChild(document.createElement("BR"));
				check_siblings = document.createElement("INPUT");
				check_siblings.type = "checkbox";
				td.appendChild(check_siblings);
				td.appendChild(document.createTextNode(" Display siblings areas (purple)"));
				check_siblings.onchange = function() {
					field_changed();
				};
				td.appendChild(document.createElement("BR"));
				if (division_index > 0) {
					check_parent = document.createElement("INPUT");
					check_parent.type = "checkbox";
					td.appendChild(check_parent);
					td.appendChild(document.createTextNode(" Display parent area (orange)"));
					check_parent.onchange = function() {
						field_changed();
					};
					td.appendChild(document.createElement("BR"));
				}
				if (division_index < res_size-1) {
					var div_sub = document.createElement("DIV");
					td.appendChild(div_sub);
					var div_sub_title = document.createElement("DIV");
					div_sub_title.appendChild(document.createTextNode("Sub-areas"));
					div_sub_title.style.backgroundColor = "#D0D0D0";
					div_sub.appendChild(div_sub_title);
					div_sub.style.width = "100%";
					div_sub.style.height = "100px";
					div_sub.style.overflow = "auto";
					var ul = document.createElement("UL");
					ul.style.padding = "0px";
					ul.style.paddingLeft = "20px";
					div_sub.appendChild(ul);
					for (var i = 0; i < t[division_index+1].areas.length; ++i) {
						var sa = t[division_index+1].areas[i];
						if (sa.area_parent_id != area.area_id) continue;
						var li = document.createElement("LI");
						li.appendChild(createLink(sa.area_name,division_index+1,i,true));
						ul.appendChild(li);
					}
				}
				
				var canvas = document.createElement("DIV");
				canvas.style.width = "400px";
				canvas.style.height = "300px";
				td_map.appendChild(canvas);
				loadGoogleMaps(function() {
					var coordinates_area = area;
					var i = parents.length-2;
					while (!coordinates_area.north && i >= 0) {
						coordinates_area = parents[i--].area;
					} 
					var center_lat = coordinates_area.north ? parseFloat(coordinates_area.south)+(parseFloat(coordinates_area.north)-parseFloat(coordinates_area.south))/2 : 0;
					var center_lng = coordinates_area.north ? parseFloat(coordinates_area.west)+(parseFloat(coordinates_area.east)-parseFloat(coordinates_area.west))/2 : 0;
					var center = new window.top.google.maps.LatLng(center_lat, center_lng); 
					map = new window.top.google.maps.Map(canvas, { center:center, zoom:8 });
		
					field_changed();
				});
						
				layout.invalidate(content);

				service.json("geography","search_geonames",{country_id:country_id,area_id:result[division_index].areas[area_index].area_id},function(res) {
					processSearchResults(res, div_geonames);
				});
				service.json("geography","search_google",{country_id:country_id,area_id:result[division_index].areas[area_index].area_id},function(res) {
					processSearchResults(res, div_google);
				});
			});
		});
	};
	
	result.editCoordinates = function(division_index, area_index, onnext) {
		var area = this[division_index].areas[area_index];
		if (area.north || onnext) {
			this.dialogCoordinates(division_index, area_index, onnext);
		} else {
			// automatically try
			var lock = lock_screen(null, "Searching coordinates on Internet...");
			var t=this;
			service.json("geography", "search_coordinates", {country_id:country_id, area_id:this[division_index].areas[area_index].area_id},function(res) {
				unlock_screen(lock);
				var found = false;
				if (res) {
					window.span_nb_have_coordinates.innerHTML = parseInt(window.span_nb_have_coordinates.innerHTML)+res.length;
					for (var i = 0; i < res.length; ++i) {
						var area_id = res[i].id;
						var index = result.findIndex(area_id);
						if (index.division_index == division_index && index.area_index == area_index)
							found = true;
						var a = t[index.division_index].areas[index.area_index];
						a.north = res[i].north;
						a.south = res[i].south;
						a.east = res[i].east;
						a.west = res[i].west;
						if (a.coordinates_button)
							tr.setCoordinateButton(a.coordinates_button, index.division_index, index.area_index);
					}
				}
				if (found) {
					if (res.length == 1)
						window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK,"Coordinates successfully found on Internet for "+area.area_name,[{action:"close"}],5000));
					else
						window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK,"Coordinates successfully found on Internet for "+area.area_name+" as well as "+(res.length-1)+" other!",[{action:"close"}],5000));
				} else {
					if (!res || res.length == 0)
						window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_WARNING,"Coordinates not found on Internet for "+area.area_name,[{action:"close"}],5000));
					else
						window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_WARNING,"Coordinates not found on Internet for "+area.area_name+", but we found coordinates for "+res.length+" other while searching",[{action:"close"}],5000));
				}
				t.dialogCoordinates(division_index, area_index);
			});
		}
	};

	result.nextMissingCoordinates = function(pos_division, pos_area) {
		// start to resolve the children
		if (typeof pos_division == 'undefined') {
			pos_division = res_size-1;
			pos_area = 0;
		}
		do {
			if (pos_division < 0) { alert("No more missing coordinates"); return; }
			if (pos_area >= result[pos_division].areas.length) { pos_division--; pos_area = 0; continue; }
			var area = result[pos_division].areas[pos_area];
			if (area.north) { pos_area++; continue; }
			result.editCoordinates(pos_division, pos_area, function() {
				result.nextMissingCoordinates(pos_division, pos_area+1);
			});
			break;
		} while (true);
	};

	var stop_searching = false;
	result.searchMissingCoordinates = function(search_division) {
		stop_searching = false;
		if (typeof search_division == 'undefined') {
			var total = parseInt(window.span_nb_total.innerHTML);
			var ok = parseInt(window.span_nb_have_coordinates.innerHTML);
			var missing = total-ok;
			if (missing == 0) return;
			var lock = lock_screen(null, "");
			set_lock_screen_content_progress(lock, missing, "Searching missing coordinates from Internet...<br/>This will take a while, please be patient", true, function(span, pb, sub) {
				var span_nb_found = document.createElement("SPAN");
				span_nb_found.innerHTML = "0";
				var span_nb_remaining = document.createElement("SPAN");
				span_nb_remaining.innerHTML = missing;
				sub.appendChild(span_nb_found);
				sub.appendChild(document.createTextNode(" found, "));
				sub.appendChild(span_nb_remaining);
				sub.appendChild(document.createTextNode(" remaining."));
				sub.appendChild(document.createElement("BR"));
				var button = document.createElement("BUTTON");
				button.innerHTML = "<img src='"+theme.icons_16.cancel+"'/> Stop";
				button.onclick = function() {
					this.innerHTML = "<img src='"+theme.icons_16.loading+"'/> Please wait while we are waiting for pending results...";
					this.disabled = "disabled";
					stop_searching = true;
				};
				sub.appendChild(button);
				// start from the top, going to the leaves, then back to the top
				var i1 = 0;
				var i2 = res_size-1;
				var done = function() {
					unlock_screen(lock);
				};
				var doit2 = function() {
					if (stop_searching) done();
					result._searchCoordinatesDivision(i2, pb, span_nb_found, span_nb_remaining, function() {
						if (--i2 < 0) done();
						else doit2();
					});
				};
				var doit1 = function() {
					if (stop_searching) done();
					result._searchCoordinatesDivision(i1, pb, span_nb_found, span_nb_remaining, function() {
						if (++i1 >= res_size) doit2();
						else doit1();
					});
				};
				doit1();
			});
		} else {
			var total = 0;
			var missing = 0;
			for (var i = 0; i < result[search_division].areas.length; ++i) {
				total++;
				if (result[search_division].areas[i].north) continue; // already set
				missing++;
			}
			var lock = lock_screen(null, "");
			set_lock_screen_content_progress(lock, missing, "Searching missing coordinates from Internet...<br/>This will take a while, please be patient", true, function(span, pb, sub) {
				var span_nb_found = document.createElement("SPAN");
				span_nb_found.innerHTML = "0";
				var span_nb_remaining = document.createElement("SPAN");
				span_nb_remaining.innerHTML = missing;
				sub.appendChild(span_nb_found);
				sub.appendChild(document.createTextNode(" found, "));
				sub.appendChild(span_nb_remaining);
				sub.appendChild(document.createTextNode(" remaining."));
				sub.appendChild(document.createElement("BR"));
				var button = document.createElement("BUTTON");
				button.innerHTML = "<img src='"+theme.icons_16.cancel+"'/> Stop";
				button.onclick = function() {
					this.innerHTML = "<img src='"+theme.icons_16.loading+"'/> Please wait while we are waiting for pending results...";
					this.disabled = "disabled";
					stop_searching = true;
				};
				sub.appendChild(button);
				// search the division
				result._searchCoordinatesDivision(search_division, pb, span_nb_found, span_nb_remaining, function() {
					unlock_screen(lock);
				});
			});
		}
	};
	result._searchCoordinatesDivision = function(division_index, pb, span_nb_found, span_nb_remaining, ondone) {
		var todo = [];
		var pending = 0;
		for (var i = 0; i < result[division_index].areas.length; ++i) {
			if (result[division_index].areas[i].north) continue; // already set
			todo.push(i);
		}
		var check = function() {
			if (todo.length > 0 && !stop_searching) { launch(); return; }
			if (pending > 0) return;
			ondone();
		};
		var launch = function() {
			if (todo.length == 0 || stop_searching) return;
			pending++;
			var i = todo[0];
			todo.splice(0,1);
			result._seachCoordinatesArea(division_index, i, pb, span_nb_found, span_nb_remaining, function() { pending--; check(); });
		};
		var nb = todo.length;
		if (nb > 10) nb = 10;
		for (var i = 0; i < nb; ++i) {
			if (i == 0) { launch(); continue; }
			if (i == 1) { setTimeout(launch, 1000); continue; }
			if (i == 2) { setTimeout(launch, 5000); continue; }
			setTimeout(launch, (i-2)*10000);
		}
		check();
	};
	result._seachCoordinatesArea = function(division_index, area_index, pb, span_nb_found, span_nb_remaining, ondone) {
		var area = result[division_index].areas[area_index];
		service.json("geography", "search_coordinates", {country_id:country_id, area_id:area.area_id},function(res) {
			var found = false;
			if (res) {
				window.span_nb_have_coordinates.innerHTML = parseInt(window.span_nb_have_coordinates.innerHTML)+res.length;
				span_nb_found.innerHTML = parseInt(span_nb_found.innerHTML)+res.length;
				for (var i = 0; i < res.length; ++i) {
					var area_id = res[i].id;
					var index = result.findIndex(area_id);
					if (index.division_index == division_index && index.area_index == area_index)
						found = true;
					var a = result[index.division_index].areas[index.area_index];
					a.north = res[i].north;
					a.south = res[i].south;
					a.east = res[i].east;
					a.west = res[i].west;
					if (a.coordinates_button)
						tr.setCoordinateButton(a.coordinates_button, index.division_index, index.area_index);
				}
			}
			var progress = 0;
			if (found) {
				progress = res.length;
			} else {
				progress = res ? res.length+1 : 1;
			}
			span_nb_remaining.innerHTML = parseInt(span_nb_remaining.innerHTML)-progress;
			pb.addAmount(progress);
			ondone();
		}, false, null, function(error) {
			stop_searching = true;
		});
	};

	result.checkCoordinates = function() {
		var lock = lock_screen("", "Checking coordinates...");
		var ul = document.createElement("UL");
		setTimeout(function() { 
			result._checkCoordinates(0,null,ul);
			unlock_screen(lock);
			if (ul.childNodes.length == 0) {
				window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK,"No problem found in the coordinates",[{action:"close"}],5000));
			} else {
				require("popup_window.js",function() {
					var content = document.createElement("DIV");
					content.appendChild(ul);
					var popup = new popup_window("Coordinates problems",null,content);
					popup.show();
				});
			} 
		}, 1);
	};
	result._checkCoordinates = function(division,parent_id,ul) {
		if (division >= res_size) return [];
		var list = [];
		var t=this;
		var createLink = function(area, division_index, area_index) {
			var link = document.createElement("A");
			link.href = "#";
			link.appendChild(document.createTextNode(area.area_name));
			link.onclick = function() {
				t.dialogCoordinates(division_index, area_index);
				return false;
			};
			return link;
		};
		for (var i = 0; i < this[division].areas.length; ++i) {
			var area = this[division].areas[i];
			if (division != 0 && area.area_parent_id != parent_id) continue;
			list.push({area:area,area_index:i});
			var sub_ul = document.createElement("UL");
			var children = this._checkCoordinates(division+1,area.area_id,sub_ul);
			var outside = [];
			if (area.north) {
				// check if parent is containing all children
				for (var j = 0; j < children.length; ++j) {
					if (children[j].area.north && !this.boxContains(area, children[j].area))
						outside.push(children[j]);
				}
			}
			if (sub_ul.childNodes.length > 0 || outside.length > 0) {
				var li = document.createElement("LI");
				li.appendChild(document.createTextNode("Area "));
				var link = createLink(area,division,i);
				li.appendChild(link, division, i);
				li.appendChild(document.createTextNode(" has problems:"));
				if (outside.length > 0) {
					var ul2 = document.createElement("UL");
					li.appendChild(ul2);
					for (var j = 0; j < outside.length; ++j) {
						var li2 = document.createElement("LI");
						li2.appendChild(document.createTextNode("Sub area "));
						li2.appendChild(createLink(outside[j].area, division+1, outside[j].area_index));
						li2.appendChild(document.createTextNode(" is outside"));
						ul2.appendChild(li2); 
					}
					if (sub_ul.childNodes.length > 0) {
						var li2 = document.createElement("LI");
						li2.appendChild(document.createTextNode("Sub areas contain problems:"));
						li2.appendChild(sub_ul);
						ul2.appendChild(li2);					
					}
				} else
					li.appendChild(sub_ul);
				ul.appendChild(li);
			}
		}
		return list;
	};
	result.boxContains = function(area1, area2) {
		var b1 = parseFloat(area2.south) >= parseFloat(area1.south);
		var b2 = parseFloat(area2.south) <= parseFloat(area1.north);
		var b3 = parseFloat(area2.north) >= parseFloat(area1.south);
		var b4 = parseFloat(area2.north) <= parseFloat(area1.north);
		var b5 = parseFloat(area2.west) >= parseFloat(area1.west);
		var b6 = parseFloat(area2.west) <= parseFloat(area1.east);
		var b7 = parseFloat(area2.east) >= parseFloat(area1.west);
		var b8 = parseFloat(area2.east) <= parseFloat(area1.east);
		var b = b1 && b2 && b3 && b4 && b5 && b6 && b7 && b8;
		return b;
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

	tr.addCoordinatesButton = function(r, division_index, area_index, div){
		if(division_index == null && area_index == null) return;
		var area = r[division_index].areas[area_index];
		var button = document.createElement('BUTTON');
		button.className = 'flat small_icon';
		this.setCoordinateButton(button, division_index, area_index);
		area.coordinates_button = button;
		button.onclick = function() { r.editCoordinates(division_index, area_index); return false; }
		div.appendChild(button);
	};
	tr.setCoordinateButton = function(button, division_index, area_index) {
		var area = result[division_index].areas[area_index];
		if (area.north != null) {
			button.innerHTML = "<img src='"+theme.build_icon('/static/geography/earth_12.png',theme.icons_10.edit)+"'/>";
			button.title = "Edit geographic coordinates";
		} else {
			button.innerHTML = "<img src='"+theme.build_icon('/static/geography/earth_12.png','/static/geography/add_9.png')+"'/>";
			button.title = "Set geographic coordinates";
		}
	};
	tr.addSearchCoordinatesButton = function(r, division_index, area_index, div){
		if(division_index == null && area_index == null) return;
		var area = r[division_index].areas[area_index];
		var button = document.createElement('BUTTON');
		button.className = 'flat small_icon';
		button.title = "Search missing coordinates inside "+area.area_name;
		button.innerHTML = "<img src='"+theme.build_icon('/static/geography/earth_12.png','/static/geography/search_9.png')+"'/>";
		button.onclick = function() { 
			var p_ids = [area.area_id];
			var p_div = division_index;
			var todo = [];
			while (p_div < r.length-1) {
				var ids = [];
				for (var i = 0; i < r[p_div+1].areas.length; ++i) {
					var a = r[p_div+1].areas[i];
					if (!p_ids.contains(a.area_parent_id)) continue;
					ids.push(a.area_id);
					if (a.north) continue;
					todo.push({area:a,division_index:p_div+1,area_index:i});
				}
				p_ids = ids;
				p_div++;
			}
			stop_searching = false;
			var lock = lock_screen(null, "");
			set_lock_screen_content_progress(lock, todo.length, "Searching missing coordinates from Internet...<br/>This will take a while, please be patient", true, function(span, pb, sub) {
				var span_nb_found = document.createElement("SPAN");
				span_nb_found.innerHTML = "0";
				var span_nb_remaining = document.createElement("SPAN");
				span_nb_remaining.innerHTML = todo.length;
				sub.appendChild(span_nb_found);
				sub.appendChild(document.createTextNode(" found, "));
				sub.appendChild(span_nb_remaining);
				sub.appendChild(document.createTextNode(" remaining."));
				sub.appendChild(document.createElement("BR"));
				var button = document.createElement("BUTTON");
				button.innerHTML = "<img src='"+theme.icons_16.cancel+"'/> Stop";
				button.onclick = function() {
					this.innerHTML = "<img src='"+theme.icons_16.loading+"'/> Please wait while we are waiting for pending results...";
					this.disabled = "disabled";
					stop_searching = true;
				};
				sub.appendChild(button);
				// search
				var next = function() {
					if (todo.length == 0 || stop_searching) {
						unlock_screen(lock);
						return;
					}
					var a = todo[0];
					todo.splice(0,1);
					if (a.area.north) { next(); return; }
					result._seachCoordinatesArea(a.division_index, a.area_index, pb, span_nb_found, span_nb_remaining, next);
				};
				next();
			});
			return false; 
		};
		div.appendChild(button);
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
		content.appendChild(document.createElement("BR"));
		var link_clean = document.createElement("A");
		link_clean.className = "black_link";
		link_clean.appendChild(document.createTextNode("Clean after copy paste from a table"));
		link_clean.href = '#';
		link_clean.onclick = function() {
			var s = text_area.value;
			var lines = s.split("\n");
			s = "";
			for (var i = 0; i < lines.length; ++i) {
				var line = lines[i].trim();
				if (line.length == 0) continue;
				var j = line.indexOf('\t');
				if (j > 0) line = line.substring(0,j);
				s += line+"\n";
			}
			text_area.value = s;
			return false;
		};
		content.appendChild(link_clean);
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
		var item = new TreeItem([new TreeCell(div)], false, null, function(root_item) {
			if (typeof r[0] == 'undefined') return;
			for (var i = 0; i < r[0].areas.length; ++i)
				root_item.addItem(tr.createItem(r, 0, i));
		});
		this.root = item;
		this.addItem(item);
		this.addAddButton(r,null,null,div);
		var nb_to_set = 0;
		var nb_total = 0;
		for(var division_index=0; division_index < r.length; division_index++){
			for (var area_index = 0; area_index < r[division_index].areas.length; ++area_index) {
				var area = r[division_index].areas[area_index];
				nb_total++;
				if (area.north == null) nb_to_set++;
			}
		}
		var span = document.createElement("SPAN");
		window.span_nb_have_coordinates = document.createElement("SPAN");
		window.span_nb_have_coordinates.innerHTML = (nb_total-nb_to_set);
		span.appendChild(window.span_nb_have_coordinates);
		span.appendChild(document.createTextNode("/"));
		window.span_nb_total = document.createElement("SPAN");
		window.span_nb_total.innerHTML = nb_total;
		span.appendChild(window.span_nb_total);
		span.appendChild(document.createTextNode(" area(s) have geographic coordinates"));
		span.style.margin = "4px 5px 0px 5px";
		window.areas_section.addToolRight(span);

		var button;
		button = document.createElement("BUTTON");
		button.appendChild(document.createTextNode("Search missing"));
		window.areas_section.addToolRight(button);
		button.onclick = function() {
			var button = this;
			require("context_menu.js", function() {
				var menu = new context_menu();
				menu.addIconItem(null, "Search all", function() {
					r.searchMissingCoordinates();
				});
				for (var i = 0; i < r.length; ++i) {
					menu.addIconItem(null, "Search only: "+r[i].division_name, function(division) {
						r.searchMissingCoordinates(division);
					},i);
				}
				menu.showBelowElement(button);
			});
			
			return false; 
		};

		button = document.createElement("BUTTON");
		button.appendChild(document.createTextNode("Open next missing"));
		window.areas_section.addToolRight(button);
		button.onclick = function() { r.nextMissingCoordinates(); return false; };

		button = document.createElement("BUTTON");
		button.appendChild(document.createTextNode("Check coordinates"));
		window.areas_section.addToolRight(button);
		button.onclick = function() { r.checkCoordinates(); return false; };
	};

	tr.createItem = function(r, division_index, area_index) {
		var div = document.createElement('DIV');
		div.style.display ='inline-block';
		div.id = r[division_index].areas[area_index].area_id;
		var item = new TreeItem([new TreeCell(div)], false, null, function(item) {
			if (division_index == r.length-1) return;
			for (var i = 0; i < r[division_index+1].areas.length; ++i)  {
				if (r[division_index+1].areas[i].area_parent_id != div.id) continue;
				item.addItem(tr.createItem(r, division_index+1, i));
			}
		});
		r[division_index].areas[area_index].item = item;
		r.createEditable(div, division_index, area_index);
		tr.addAddButton(r, division_index, area_index, div);
		tr.addRemoveButton(r, division_index, area_index, div);
		tr.addCoordinatesButton(r, division_index, area_index, div);
		tr.addSearchCoordinatesButton(r, division_index, area_index, div);
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