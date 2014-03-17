function images_tool_people() {
	this.getTitle = function() { return "People"; };
	this.getIcon = function() { return "/static/people/people_16.png"; };
	this.peoples = [];
	this.setValue = function(pic, value, editable) {
		if (!pic)
			this.peoples = value;
	};
	this._words = function(s) {
		var words = [];
		var word = "";
		for (var i = 0; i < s.length; ++i) {
			var c = s.charAt(i);
			if (isLetter(c))
				word += c;
			else if (word.length > 0) {
				words.push(word.toLowerCase());
				word = "";
			}
		}
		if (word.length > 0) words.push(word.toLowerCase());
		return words;
	};
	this.update = function(pic, canvas) {
		if (pic.select_people.selectedIndex <= 0 && pic.name) {
			var name = pic.name;
			// remove extension
			var i = name.lastIndexOf('.');
			if (i > 0) name = name.substring(0,i);
			name = this._words(name);
			for (var i = 0; i < pic.select_people.options.length; ++i) {
				var o = pic.select_people.options[i];
				var words = this._words(o.text);
				// TODO continue;
			}
		}
	};
	this.createContent = function(pic) {
		var select = document.createElement("SELECT");
		var o = document.createElement("OPTION");
		o.value = 0;
		o.text = "";
		for (var i = 0; i < this.peoples.length; ++i) {
			var o = document.createElement("OPTION");
			o.value = this.peoples[i].id;
			o.text = this.peoples[i].first_name+" "+this.peoples[i].last_name;
			select.add(o);
		}
		pic.select_people = select;
		return select;
	};
};
images_tool_people.prototype = new ImageTool;
images_tool_people.prototype.constructor = images_tool_people;
