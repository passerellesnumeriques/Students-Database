function people_search(container, people_types, onselected) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (typeof people_types == 'string') people_types = [people_types];
	var t=this;
	
	this.onselected = new Custom_Event();
	if (onselected) this.onselected.add_listener(onselected);
	onselected = null;
	
	this.getSelectedPeople = function() { return this._selected; };
	
	this._selected = null;
	this._menu = null;
	this._search = null;
	this._init = function() {
		this._cs = new custom_search(container, 2, 'Search by name', function(name, ondone) {
			t._selected = null;
			if (t._menu) t._menu.close();
			t._menu = new context_menu();
			var searching = document.createElement("DIV");
			searching.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> ";
			searching.appendChild(document.createTextNode("Searching "+name+"..."));
			t._menu.addItem(searching, true);
			t._menu.showBelowElement(t._cs.input);
			t._search = name;
			service.json("people","search",{name:name,types:people_types},function(peoples) {
				if (t._search != name) return; // search something else already
				t._menu.clearItems();
				if (peoples.length == 0) {
					var div = document.createElement("DIV");
					div.style.fontStyle = "italic";
					div.appendChild(document.createTextNode("No result found"));
					t._menu.addItem(div);
					return;
				}
				for (var i = 0; i < peoples.length; ++i) {
					t._menu.addIconItem(null, peoples[i].last_name+' '+peoples[i].first_name, function(ev, people) {
						t._selected = people;
						t.onselected.fire(people);
						t._cs.input.value = people.last_name+' '+people.first_name;
					}, peoples[i]);
				}
			});
			ondone();
		});
		container.ondomremoved(function() {
			t._cs = null;
			t._menu = null;
			t = null;
		});
		require("context_menu.js");
	};
	require("custom_search.js",function() { t._init(); });
}