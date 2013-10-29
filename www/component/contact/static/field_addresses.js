function field_addresses(data,editable,onchanged,onunchanged,config){
	
	if (editable) {
		this.element = document.createElement("TABLE");
		var t=this;
		require("addresses.js",function() {
			new addresses(t.element, false, data.table, data.key_name, data.key_value, data.addresses, true, true, true);
		});
	} else {
		this.element = document.createElement("TABLE");
		this.element.appendChild(this.tr = document.createElement("TR"));
		this.setData = function(data) {
			this.data = data;
			while (this.tr.childNodes.length > 0) this.tr.removeChild(this.tr.childNodes[0]);
			var t=this;
			require("address_text.js",function() {
				for (var i = 0; i < data.addresses.length; ++i) {
					var text = new address_text(data.addresses[i]);
					var td = document.createElement("TD");
					t.tr.appendChild(td);
					td.appendChild(text.element);
					td.style.verticalAlign = "top";
					if (t.tr.childNodes.length > 1) td.style.borderLeft = "1px solid #808080";
				}
			});
		};
		this.setData(data);
		this.getCurrentData = function(){
			return this.data;
		};
	}
	
}
if (typeof typed_field != 'undefined') {
	field_addresses.prototype = new typed_field();
	field_addresses.prototype.constructor = field_addresses;		
}
