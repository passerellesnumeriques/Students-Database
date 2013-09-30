<?php
class page_set_geography_area extends Page {
	public function get_required_rights(){
	// TODO
	return array();
	}
	public function execute(){
	
	$q = SQLQuery::create()->select("Country")
						->field("Country","id","country_id")
						->field("Country","code","country_code")
						->field("Country","name","country_name");
	$countries = $q->execute();
	echo "<form method='get' action=''>";
	echo "<select name='country_code'>";
	foreach ($countries as $c){
		echo "<option value = '".$c['country_code']."'>".$c['country_name']."</option>";
	}
	echo "</select>";
	echo "<input type = 'submit' value = 'Go'/>";
	echo "</form>";
	
?>
<br/><br/>
<div name = 'container' style = "position:relative">
	<div id ='manage_divisions' ></div>
	<div id='set_geography_area' style = "margin-left:300px"></div>
</div>
<script type = 'text/javascript'>
var result = null;
var tr = null;
var editable = null;

<?php
if(isset($_GET['country_code'])){
?>
var country_code = "<?php echo $_GET['country_code'];?>";
service.json("geography","get_country_data", {country_code:country_code}, function(res){
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
	}
	
	result.removeArea = function(area_id){
		/*We remove from the tree*/
		var index = this.findIndex(area_id);
		tr.removeItem(result[index.division_index].areas[index.area_index].item);
		/*We update result object*/
		this[index.division_index].areas[index.area_index] = null;
	}
	
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
	}
	
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
	}
	
	/**
	* Get the index in the result object of the given area
	* @method set_geographic_area#findIndex
	* @parameter area_id
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
	}
	
	/**
	* Get all the children (just one generation after)
	* @method set_geographic_area#findChildren
	* @parameter area_id
	*/
	result.findChildren = function(area_id){
		var index = this.findIndex(area_id);
		var division_index = index.division_index;
		var area_index = index.area_index;
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
	}
	
	/**
	* Get the parent of the given area
	* @method set_geographic_area#findParent
	* @parameter area_id
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
	}
	
	/**
	* Create editable cells in the tree
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
		else parent_name = '<?php echo $_GET['country_code'];?>';
		edit.onsave = function(text){
						if(result.checkUnicity(text, "area", area_parent_id, null)){
							/*We update result*/
							result[division_index].areas[area_index].area_name = text.uniformFirstLetterCapitalized();
							return text.uniformFirstLetterCapitalized();
						}
						else {error_dialog(parent_name + " already has one child called " + text);
							return result[division_index].areas[area_index].area_name;
						}
						};
		container.appendChild(div);
	}
	
	result.checkNotEmpty = function(text){
		var is_not_empty = true;
		var text_split = text.split(" ");
		alert(text_split.length);
		if (text_split.length == 0) is_not_empty = false;
		return is_not_empty;
	}
	
	result.buildTableDivisions = function(){
		var container = document.getElementById('manage_divisions');
		var table = document.createElement('table');
		table.id = "table_manage_divisions";
		var tbody = document.createElement('tbody');
		var thead = document.createElement('thead');
		var tfoot = document.createElement('tfoot');
		var th = document.createElement('th');
		var th_remove = document.createElement('th');
		th.innerHTML = "Manage country divisions";
		var tr_header = document.createElement('tr');
		tr_header.appendChild(th);
		tr_header.appendChild(th_remove);
		thead.appendChild(tr_header);
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
					if(result.checkUnicity(text,null,null,"division")){
						/*We update result*/
						result[edit.division_index].division_name = text.uniformFirstLetterCapitalized();
						return text.uniformFirstLetterCapitalized();
					}
					else{
						error_dialog("The country <?php echo $_GET['country_code'];?> already has a division called "+ text);
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
		add_button.innerHTML = "<img src='"+theme.icons_16.add+"'/>";
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
	}
	
	result.startAddDivision = function(){
		input_dialog(theme.icons_16.question,
					"Add a new division",
					"Add a new geographic division to this country. You will be redirected after submitting.",
					"",
					50,
					function(text){
						if(text.length != 0){ 
							if(!result.checkUnicity(text, null, null, "division")){ return "This division name is already set for this country";}
							else return;
						}
						else return "You must enter at least one caracter";
					},
					function(text){
						if(text) result.addDivision(text.uniformFirstLetterCapitalized());
					});
	}
	
	result.addDivision = function(division_name){
		var parent_index = this.length -1;
		var parent_id = this[parent_index].division_id;
		<?php
			$q2 = SQLQuery::create()->select("Country")
									->field("id")
									->where("`code` = '".$_GET['country_code']."'");
			$country_ids = $q2->execute();
			$country_id = $country_ids[0];
		?>
		var country_id = "<?php echo $country_id['id'];?>";
		var field_saved_id = null;
		service.json("data_model","save_entity", {table:"Country_division", field_name:division_name, field_parent:parent_id, field_country:country_id}, function(res){
			if(!res) return;
			field_saved_id = res.key;
		},true);
		/*We refresh the page*/
		document.getElementById('form_reload').submit();
	}
	
	result.buttonRemoveDivision = function(td_remove, division_id){
		var remove_button = document.createElement('div');
		remove_button.className = "button";
		remove_button.innerHTML = "<img src ='"+theme.icons_16.remove+"'/>";
		remove_button.onclick = function(){result.askRemoveDivision(division_id);};
		td_remove.appendChild(remove_button);
	}
	
	result.askRemoveDivision = function (division_id){
		confirm_dialog("Are you sure you want to delete this division? All its children will be removed, even the geographic areas",
						function(text){if(text) result.removeDivision(division_id);}
						);
	}
	result.removeDivision = function(division_id){
		service.json("data_model","remove_row",{table:"Country_division", row_key:division_id}, function(res){
			if(!res) return;
		},true);
		/*We refresh the page*/
		document.getElementById('form_reload').submit();
	}
	
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
	}
	
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
			var add_button = document.createElement('div');
			add_button.className = 'button';
			add_button.innerHTML = "<img src='"+theme.icons_16.add+"'/>";
			add_button.onclick = function(){tr.addChild(r, null, 'root');};
			var div = document.getElementById('root');
			div.appendChild(add_button);
		}
		else{
			if(division_index != r.length -1){
				var add_button = document.createElement('div');
				add_button.className = 'button';
				add_button.innerHTML = "<img src='"+theme.icons_16.add+"'/>";
				add_button.area_parent_id = r[division_index].areas[area_index].area_id;
				add_button.onclick = function(){tr.addChild(r, add_button.area_parent_id);};
				var div = document.getElementById(r[division_index].areas[area_index].area_id);
				div.appendChild(add_button);
			}
		}
	}
	
	tr.addRemoveButton = function(r, division_index, area_index){
		var remove_button = document.createElement('div');
		remove_button.className = 'button';
		remove_button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
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
	}
	
	tr.removeChildren = function(r, area_id){
		confirm_dialog("Are you sure you want to delete this area? All its children will also be deleted.", function(text){if(text == true) r.startRemove(area_id);});
	}
	
	tr.addChild = function(r, area_parent_id, root){
		if(root == null){
			input_dialog(theme.icons_16.question,
									'Add a new child',
									'Enter the area name',
									'',
									50,
									function(text){
										if(text.length != 0){ 
											if(!r.checkUnicity(text, "area", area_parent_id, null)){ return "The current area already has a child with this name";}
											else return;
										}
										else return "You must enter at least one caracter";
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
										if(text.length != 0){ 
											if(!r.checkUnicity(text, "area", area_parent_id, null)){ return "The current area already has a child with this name";}
											else return;
										}
										else return "You must enter at least one caracter";
									},
									function(text){if (text) r.addRoot(text);});
		}
	}
	
	tr.buildTree = function (r){
		tr.addColumn(new TreeColumn(""));
		/*We add a root level to be able to manage the first level(add, remove)*/
		var div = document.createElement('div');
		div.id ='root';
		div.style.display ='inline-block';
		div.innerHTML = "<?php echo $_GET['country_code']; ?>";
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
	}
	
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
	}
	
	if (result != null && editable != null) everything_ready();
});

require('editable_cell.js',function(){
	editable = 'ok';
	if (result != null && tr != null) everything_ready();
});

function everything_ready() {
	// if(result == {}){
		// /*We only create the table to manage the divisions*/
		// result.buildTableDivisions();
	// }
	/*We create the tree*/
	tr.buildTree(result);
	/*We create the table to manage the divisions*/
	result.buildTableDivisions();
	/*Set the layout*/
	var div_manage_divisions = document.getElementById('manage_divisions');
	div_manage_divisions.style.position = "absolute";
	div_manage_divisions.style.left = "0px";
	div_manage_divisions.style.width = "250px";
	div_manage_divisions.style.marginLeft = "30px";
	
	result.checkNotEmpty('to ');

}

<?php
}
?>
	

</script>
	<?php
	
	}
}

?>