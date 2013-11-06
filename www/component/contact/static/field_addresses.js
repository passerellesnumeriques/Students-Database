function field_addresses(data,editable,config){
	typed_field.call(this, data, editable, config);
}
field_addresses.prototype = new typed_field();
field_addresses.prototype.constructor = field_addresses;		
field_addresses.prototype._create = function(data) {
	if (this.editable) {
		this.table = document.createElement("TABLE"); this.element.appendChild(this.table);
		var t=this;
		require("addresses.js",function() {
			new addresses(t.table, false, data.table, data.key_name, data.key_value, data.addresses, true, true, true);
		});
	} else {
		this.table = document.createElement("TABLE"); this.element.appendChild(this.table);
		this.table.appendChild(this.tr = document.createElement("TR"));
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
};