function geographic_area_selection(container, country_code) {
	if (typeof container == 'string') {
	container = document.getElementById(container);
	}
	var t=this;	
	
	service.json("geography", "get_country_data", {country_code:country_code}, function(result) {
		if (!result) return;
		t.result = result;
		var table = document.createElement('table');
		var tbody = document.createElement('tbody');
		var form = document.createElement('form');
		form.method ="post";
		form.action = "";
		form.id = "area_selection";
		var result_length = result.length;
		for (var j=0; j<result_length; j++) {
			var tr = document.createElement('tr');
			var td_name = document.createElement('td');
			var td_form = document.createElement('td');
			var td_name_text = document.createTextNode(result[j].division_name);
			td_name.appendChild(td_name_text);
			var select = document.createElement('select');
			select.name = result[j].division_id;
			/* selectByNames: will select the division
			* selectById: will select the area
			*/
			var nb_areas = result[j].areas.length;
			var empty_option = document.createElement('option');
			empty_option.value = "";
			select.appendChild(empty_option);
				for (var i = 0; i < nb_areas; i++){
					var option = document.createElement('option');
					option.value = result[j].areas[i].area_id;
					option.id = result[j].areas[i].area_id;
					option.area_name = result[j].areas[i].area_name;
					option.area_parent_id = result[j].areas[i].area_parent_id;
					option.division_id = result[j].division_id;
					option.text = result[j].areas[i].area_name;
					select.appendChild(option);
				}
			select.onchange = function() {
				var option = this.options[this.selectedIndex];
				if(option.value == ""){
					t.unfilter();
				}
				else{
					t.startFilter(option.value);
				}
			}
			td_form.appendChild(select);
			tr.appendChild(td_name);
			tr.appendChild(td_form);
			tbody.appendChild(tr);		
		}
		table.appendChild(tbody);
		form.appendChild(table);
		container.appendChild(form);
		t.createAutoFillInput("area_selection");

	});
	
	this.startFilter = function(area_id){
		/*Find the path to the top*/
		var parent = this.findParent(area_id);
		var pathTop = [];
		if(parent != null){
			var i = 0;
			var current_area = area_id;
			while(parent != null){
				pathTop[i] = parent;
				parent = this.findParent(current_area);
				if(parent != null){
					current_area = parent.area_id;
				}
				i++;
			}
		}
		var nb_parents = pathTop.length;
		for(var i = nb_parents -1; i >= 0; i--){
			this.filter(pathTop[i].area_id);
		}
		this.filter(area_id);
	}
	
	/** Sort all the divisions below according to the given area
	* @method geographic_area_selection#filter
	* @param area_id
	*/
	this.filter = function(area_id){
		var index = this.findIndex(area_id);
		var level = index.division_index;
		var children = [];
		var index_children = 0;
		var child = this.findChildren(area_id);
		children[0]=[]
		for (var l = 0; l < child.length; l++){
			children[0][index_children] = {division_id:child[l].division_id, area_id:child[l].area_id};
			index_children++;
		}
		var k = 0;
		while(level != this.result.length -1){
			children[k+1]=[];
			index_children = 0;
			for(var i = 0; i < children[k].length; i++){
				var c = this.findChildren(children[k][i].area_id);
				for(var j = 0; j < c.length; j++){
					children[k +1][index_children] = c[j];
					index_children++;
				}
			}
			k++;
			level++;
		}
		/* We build a new object, with the same structure as result*/
		var new_result = [];
		for(var i = 0; i < children.length; i++){
			if(children[i].length !=0){
				new_result[i] = [];
				new_result[i] = {division_id:"", division_name:"", areas:[]};
				for(var j = 0; j < children[i].length; j++){
					var current_index = this.findIndex(children[i][j].area_id);
					new_result[i].division_id = children[i][j].division_id;
					new_result[i].division_name = this.result[current_index.division_index].division_name;
					new_result[i].areas[j] = {area_id:this.result[current_index.division_index].areas[current_index.area_index].area_id,
											area_name:this.result[current_index.division_index].areas[current_index.area_index].area_name,
											area_parent_id:this.result[current_index.division_index].areas[current_index.area_index].area_parent_id
											};
				}
			}
		}		
		/*Select the area on the current division*/
		var option = document.getElementById(area_id);
		option.selected = true;
		/*Filter the children*/
		this.buildTable(new_result, area_id);
	}
	
	/** Edit the table from the new_result[0] level, with only the areas set in new_result
	* @method geographic_area_selection#buildTable
	* @param new_result = object with the same structure as result
	* @param area_id = id of the area which started the filter function
	*/
	this.buildTable = function(new_result, area_id){
		index = this.findIndex(area_id);
		level = index.division_index;
		while(level != this.result.length -1){
			var select = document.getElementsByName(this.result[level +1].division_id)[0];
			select.innerHTML = "";
			level++;
		}
		for(var i = 0; i < new_result.length; i++){
			var empty_option = document.createElement('option');
			var select_bis = document.getElementsByName(new_result[i].division_id)[0];
			select_bis.appendChild(empty_option);
			empty_option.value = "";
			for(var j = 0; j < new_result[i].areas.length; j++){
				var option = document.createElement('option');
				option.value = new_result[i].areas[j].area_id;
				option.id = new_result[i].areas[j].area_id;
				option.area_name = new_result[i].areas[j].area_name;
				option.area_parent_id = new_result[i].areas[j].area_parent_id;
				option.division_id = new_result[i].division_id;
				option.text = new_result[i].areas[j].area_name;
				select_bis.appendChild(option);
			}
		}
		
	}
	
	
	/** Get the index in the result object of the given area
	* @method geographic_area_selection#findIndex
	* @parameter area_id
	* @returns an object with two attributes: division_index & area_index
	*/
	this.findIndex = function(area_id){
		var index ={};
		for(var l = 0; l < this.result.length; l++){
			for(var m =0; m < this.result[l].areas.length; m++){
				if(this.result[l].areas[m].area_id == area_id){
					index = {division_index:l, area_index:m};
					break;
				}
			}
		}
		return index;
	}


	/** Get all the children (just one generation after)
	* @method geographic_area_selection#findChildren
	* @parameter area_id
	* @returns an array children, empty if no child. children[i] contains an object with two attributes: area_id & division_id
	*/
	this.findChildren = function(area_id){
		var index = this.findIndex(area_id);
		var division_index = index.division_index;
		var area_index = index.area_index;
		var children = [];
		
		if (division_index == this.result.length -1){
			return children;
		}
		else{
			if(this.result[division_index +1].areas.length == 0){return children;}
			else{
				var k=0;
				for(var n = 0; n < this.result[division_index +1].areas.length; n++){
					if(this.result[division_index +1].areas[n].area_parent_id == area_id){
						children[k] = {division_id:this.result[division_index +1].division_id, area_id:this.result[division_index +1].areas[n].area_id};
						k++;
					}
				}
				return children;
			}
		}
	}
	
	/** Get the parent of the given area
	* @method geographic_area_selection#findParent
	* @parameter area_id
	* @returns an object parent, null if no parent, with two attributes otherwise: division_id & area_id
	*/
	this.findParent = function(area_id){
		var parent ={};
		var index = this.findIndex(area_id);
		if(index.division_index == 0){
			parent = null;
		}
		else{
			for(var i=0; i < this.result[index.division_index -1].areas.length; i++){
				if(this.result[index.division_index -1].areas[i].area_id == this.result[index.division_index].areas[index.area_index].area_parent_id){
					parent = {division_id:this.result[index.division_index -1].division_id, area_id:this.result[index.division_index -1].areas[i].area_id};
					break;
				}
			}
		}
		return parent;
	}
	
	/** Re initialize the table
	* @method geographic_area_selection#unfilter
	*/
	this.unfilter = function(){
		var area_id = this.result[0].areas[0].area_id;
		var index = this.findIndex(area_id);
		var select = document.getElementsByName(this.result[index.division_index].division_id)[0];
		select.innerHTML = "";
		this.buildTable(this.result, area_id);
		var option = document.getElementById(area_id);
		option.selected = false;
	}
	
	/** Create the input node which calls the auto fill function
	* @method geographic_area_selection#createAutoFillInput
	* @parameter id = the id of the node to which the function will append a child
	*/
	this.createAutoFillInput = function(id){
		var parent = document.getElementById(id);
		require("autocomplete.js",function(){
			new autocomplete(parent, function(val){
				return t.autoFill(val);
			}, 3, 'Manually search', function(item){
				t.startFilter(item.area);
			});
		});
	}
	
	/** Find the string needle in the areas list. Creates two arrays: one with the areas which name begins with
	* needle; a second one with the areas which name contains needle
	* @method geographic_area_selection#autoFill
	* @param needle = the needle to find in the result object
	*/
	this.autoFill = function (needle){
		var all_areas = [];
		var area_index = 0;
		for (var i =0; i<this.result.length; i++){
			for(var j = 0; j < this.result[i].areas.length; j++){
				var name = this.result[i].areas[j].area_name;
				name = name.toLowerCase();
				all_areas[area_index] = {
										area_id: this.result[i].areas[j].area_id,
										area_name: name,
										division_index: i,
										area_index: j
										};
				area_index++;
			}
		}
		var areaStart = [];
		var areaBelong = [];
		var area_start_index = 0;
		var area_belong_index = 0;
		for(var k = 0; k < all_areas.length; k++){
			if(this.startWith(all_areas[k].area_name, needle)){
				var name = all_areas[k].area_name.uniformFirstLetterCapitalized();
				areaStart[area_start_index] = {
												area_id: all_areas[k].area_id,
												area_name: name
												};
				area_start_index++;
			}
			if(this.belong(all_areas[k].area_name, needle)){
				var name = all_areas[k].area_name.uniformFirstLetterCapitalized();
				areaBelong[area_belong_index] = {
												area_id: all_areas[k].area_id,
												area_name: name
												};
				area_belong_index++;
			}
		}
		var areaStartField = []; /*contains the name of the area, followed by its parents names*/
		var areaStartValue = []; /*contains the id of the area*/
		if(areaStart.length > 0){
			this.setAreaField(areaStartField, areaStartValue, areaStart);
		}
		var areaBelongField = []; /*contains the name of the area, followed by its parents names*/
		var areaBelongValue = []; /*contains the id of the area*/
		if(areaBelong.length > 0){
			this.setAreaField(areaBelongField, areaBelongValue, areaBelong);
		}
		var items = [];
		t.setContextMenu(areaStartField, areaStartValue, items);
		t.setContextMenu(areaBelongField, areaBelongValue, items);
		return items;
	}

	
	/** Set areaField and areaValue
	* @method geographic_area_selection#setAreaField
	* @param areaField
	* @param areaValue
	* @param areas
	*/
	this.setAreaField = function (areaField, areaValue, areas){
		for(var i = 0; i < areas.length; i++){
			areaField[i] = areas[i].area_name.uniformFirstLetterCapitalized();
			areaValue[i] = areas[i].area_id;
			var parent = t.findParent(areas[i].area_id);
			var parent_index = parent != null ? t.findIndex(parent.area_id) : null;
			while(parent != null){
				areaField[i] += ", ";
				var name = t.result[parent_index.division_index].areas[parent_index.area_index].area_name.uniformFirstLetterCapitalized();
				areaField[i] += name;
				parent = t.findParent(parent.area_id);
				if(parent != null){
					parent_index = t.findIndex(parent.area_id);
				}
			}
		}
	}
	
	/** Set the context_menu: add the given fields to the context menu
	* @method geographic_area_selection#setContextMenu
	* @param areaField = array, each field contains the string to be displayed on a context_menu row
	* @param areaValue = array, each field contains the value of the context_menu row (not the displayed one)
	* @param items = array to be filled with autocomplete items
	*/
	this.setContextMenu = function(areaField, areaValue, items){
		if(areaField.length > 0){
			for(var i = 0; i < areaField.length; i++){
				items.push({
					text: areaField[i],
					area: areaValue[i]
				});
			}
		}
	}
	
	
	/** Test if one string starts with an other one
	* @method geographic_area_selection#startWith
	* @param {string} ref
	* @param {string} str
	* @returns {boolean} true if ref starts with str, else return false
	*/	
	this.startWith = function(ref, str){
		var answer = false;
		if(str.length <= ref.length){
			var ref_split = ref.split("");
			var str_split = str.split("");
			var temp_answer = true;
			for(var i = 0; i < str.length; i++){
				if(ref_split[i] != str_split[i]){
					temp_answer = false;
				}
			}
			answer = temp_answer;
		}
		return answer;
	}
	
	/** Test if a string contains an other one
	* @method geographic_area_selection#belong
	* @param {string} ref
	* @param {string} str
	* @returns {boolean} true if str belongs to ref, false if ref starts with str, else return false
	*/
	this.belong = function(ref, str){
		var answer = false;
		if(str.length <= ref.length){
			if(!t.startWith(ref, str)){
				var ref_split = ref.split("");
				var start_index = 1;
				var end_index = ref_split.length -1;
				while(start_index + str.length < ref.length +1){
					var temp_ref = "";
					for(var j = start_index; j < end_index +1; j++){
						temp_ref += ref_split[j];
					}
					if(t.startWith(temp_ref, str)){
						answer = true;
						break;
					}
					start_index++;
				}
			}
		}
		return answer;
	}
	
	
}

