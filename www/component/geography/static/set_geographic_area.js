function set_geographic_area(container, country_code){
	if (typeof container == 'string') {
	container = document.getElementById(container);
	}
	var t=this;
	
	service.json("geography", "get_country_data", {country_code:country_code}, function(result){
		if(!result)return;
		t.result = result;
		t.startRemove(5);
	
	});
	
	
	
	
	//Problem of rights???
	
	
	
	/** Call the remove function for one area and all its children
	* @method set_geographic_area#startRemove
	* @parameter area_id
	*/
	this.startRemove = function(area_id){
		var child = t.findChildren(area_id);
		var children = [];
		children[0] = [];
		var index = t.findIndex(area_id);
		var level = index.division_index;
		/*we initialize children*/
		for(var i = 0; i < child.length; i++){
			children[0][i] = child[i].area_id;
		}
		var k = 0;
		while(level != t.result.length -1){
			children[k+1] = [];
			var child_index = 0;
			for(var j = 0; j < children[k].length; j++){
				var c = t.findChildren(children[k][j]);
				for(var l = 0; l < c.length; l++){
					children[k+1][child_index] = c[l].area_id;
					child_index++;
				}
			}
			k++;
			level++;
		}
		/*We now start removing all the areas contained by children*/
		for(var i = 0; i < children.length; i++){
			for(var j = 0; j < children[i].length; j++){
				t.removeArea(children[i][j]);
			}
		}
 	}
	
	//???????remove one data ==> what about other tables (linked data)??????
	
	this.removeArea = function(area_id){
	
	}
	
	this.editArea = function(area_id){
	
	}
	
	this.addArea = function(area_parent_id){
	
	}
	
	/** Get the index in the result object of the given area
	* @method set_geographic_area#findIndex
	* @parameter area_id
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
	* @method set_geographic_area#findChildren
	* @parameter area_id
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
	* @method set_geographic_area#findParent
	* @parameter area_id
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
	
	/** Set a uniform case
	* @method set_geographic_area#uniformCase
	* @parameter {string} text
	* @returns the same string with a capitalized first letter, and other letters or lowered
	*/
	this.uniformCase = function(text) {
		text.split
		//TO DO regexp
		return t.join('');
	}
	
	
}