/** Area field: if editable, it will be a text input with the geographic_area_selection screen, else only a simple text node
 * @constructor
 * @param config.country_code = the country code for the geographic_area_selection
 */
function field_area(data,editable,onchanged,onunchanged,config){
	if (data != null && data.length == 0) data = null;
	typed_field.call(this, data, editable, onchanged, onunchanged);
	if(editable){
		var t = this;
		t.field_text = null;
		var input = document.createElement("input");
		input.type = "text";
		input.style.margin = "0px";
		input.style.padding = "0px";
		var f = function() {
			setTimeout(function() {
				if (t.selectedAreaId != data) {
					if (onchanged)
						onchanged(t,t.selectedAreaId,t.field_text);
				} else {
					if (onunchanged)
						onunchanged(t);
				}
			},1);
		};
		this.setData = function(data,first){
			t.selectedAreaId = data;
			if(data == null){
				input.value = "No area";
				input.style.fontStyle = "italic";
			} else {
				service.json("geography","get_area_parents_names",{area_id:data},function(result){
					if(!result) return;
					var display = result[0];
					for(var i = 1; i < result.length; i++){
						display += ", " + result[i];
					}
					input.value = display;
				});
			}
			f();
		}
		this.setData(data,true);
		
		input.onclick = function(event){
			stopEventPropagation(event);
			input.blur();
			require("geographic_area_selection.js",function(){
				require("context_menu.js",function(){
					var menu = new context_menu();
					var div = document.createElement('div');
					t.area = new geographic_area_selection(div, config.country_code, function(){
							if (data != null) t.area.startFilter(data);
							t.area.onchange = function(){
								var selected = t.area.getSelectedArea();
								if(selected == null){
									input.value = "No area";
									input.style.fontStyle = "italic";
									t.selectedAreaId = null;
									t.field_text = null;
								}
								else {
									t.selectedAreaId = selected.area_id;
									input.value = selected.field;
									t.field_text = selected.field;
								}
								f();
							};
							menu.addItem(div, true);
							menu.removeOnClose = true;
							menu.showBelowElement(input);
						}
					);
				});
			});
			return false;
		};
		this.element = input;
		this.element.typed_field = this;
		this.getCurrentData = function(){
			if(input.value.length == 0) return null;
			return t.selectedAreaId;
		}
		this.signal_error = function(error){
			input.style.border = error ? "1px solid red" : "";
		}
	} else {
		this.element = document.createElement('span');
		
		this.element.typed_filed = this;
		this.setData = function(data, first){
			this.selectedAreaId = data;
			if(data == null){
				this.element.innerHTML = "No area";
				this.element.style.fontStyle = "italic";
			} else {
				var t = this;
				service.json("geography","get_area_parents_names",{area_id:data},function(result){
					if(!result) return;
				
					var display = result[0];
					for(var i = 1; i < result.length; i++){
						display += ", " + result[i];
					}
					t.element.innerHTML = display;
				});
			}
			if (!first){
				if (data == this.originalData) {
					if (onunchanged) onunchanged(this);
				} else {
					if (onchanged) onchanged(this, val);
				}
			}
		}
		this.setData(data,true);
		this.getCurrentData = function(){
			return t.selectedAreaId;
		}
	}
	
}

if (typeof require != 'undefined')
	require("typed_field.js",function(){
		field_area.prototype = new typed_field();
		field_area.prototype.constructor = field_area;		
	});