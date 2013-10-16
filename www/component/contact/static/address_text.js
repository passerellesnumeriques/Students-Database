function address_text(address, onready){
	var t = this;
	//var to_return = null;
	t.result = null;
	t.firstRow = null;
	t.secondRow = null;
	t.thirdRow = null;
	t.fourthRow = null;
	t.area_name = null;
	t.country = null;


	this.init = function(){
		//if(typeof(only_country) != 'boolean') only_country = false;
		//if(!only_country){
			t.setFirstRow();
			t.setSecondRow();
			t.setThirdRow();
			/*We wait for the service */
			// if(t.result.area_id != null && t.result.area_name != null) t.setToReturn();
			// if(t.result.area_id == null) t.settoReturn();
		// } else {
			//TODO
			// t.setToReturn();
		// }
		
	}
	
	
	this.getAddressText = function(container){
		var to_return = t.setToReturn(container);
		return to_return;
	}
	
	this.setFirstRow = function(){
		if(t.result.building != null || t.result.unit != null || t.result.street != null || t.result.street_number != null){
			t.firstRow = "";
			var first = true;
			if(t.result.building != null){
				if(!first) t.firstRow += ", ";
				t.firstRow += t.result.building.uniformFirstLetterCapitalized();
				first = false;
			}
			if(t.result.unit != null){
				if(!first) t.firstRow += ", ";
				t.firstRow += t.result.unit.uniformFirstLetterCapitalized();
				first = false;
			}
			if(t.result.street_number != null){
				if(!first) t.firstRow += ", ";
				t.firstRow += t.result.street_number.uniformFirstLetterCapitalized();
				first = false;
			}
			if(t.result.street != null){
				if(!first) t.firstRow += ", ";
				t.firstRow += t.result.street.uniformFirstLetterCapitalized();
				first = false;
			}
		}
	}
	
	this.setSecondRow = function(){
		if(t.result.additional != null){
			t.secondRow = t.result.additional.uniformFirstLetterCapitalized();
		}
	}
	
	/**
	 * 
	 */
	this.setThirdRow = function(){
		if(t.result.area_id != null){
			t.area_name = t.result.area_text;
			t.country = t.result.country_name;
			t.setAreaName();
			t.setFourthRow();
			if(onready) onready(t);
		} else
			if(onready) onready(t);
	}
	
	this.setFourthRow = function(){
		t.fourthRow = t.country;
	}
	
	this.setAreaName = function(){
		t.thirdRow = "";
		var first = true;
		for(var i = 0; i < t.area_name.length; i++){
			if(!first) t.thirdRow += ", ";
			t.thirdRow += t.area_name[i];
			first = false;
		}
	}
	
	this.setToReturn = function(container){
		var p1 = document.createElement('div');
		var p2 = document.createElement('div');
		var p3 = document.createElement('div');
		var p4 = document.createElement('div');
		if(t.firstRow != null){
			p1.innerHTML = t.firstRow;
			container.appendChild(p1);
		}
		if(t.secondRow != null){
			p2.innerHTML = t.secondRow;
			container.appendChild(p2);
		}
		if(t.thirdRow != null){
			p3.innerHTML = t.thirdRow;
			container.appendChild(p3);
		}
		if(t.fourthRow != null){
			p4.innerHTML = t.fourthRow;
			container.appendChild(p4);
		}
		return container;
	}
	
	if(typeof(address) == "object"){
		/*In that case, the structure of the address has already been given in parameter*/
		t.result = address;
		t.init();
	} else {
		service.json("contact","get_address",{address_id:address},function(res){
			if(!res) return;
			t.result = res;
			if(typeof(t.result.id) != 'undefined') t.init();
			
		});
	}
}