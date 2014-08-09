// #depends[/static/images_tool/images_tool.js]

function images_tool_people() {
	this.getTitle = function() { return "People"; };
	this.getIcon = function() { return "/static/people/people_16.png"; };
	this.peoples = [];
	this.setValue = function(pic, value, editable) {
		if (!pic)
			this.peoples = value;
	};
	this.getPeople = function(pic) {
		if (pic.select_people.selectedIndex <= 0) return null;
		var people_id = pic.select_people.value;
		for (var i = 0; i < this.peoples.length; ++i)
			if (this.peoples[i].id == people_id)
				return this.peoples[i];
		return null;
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
	this._almostSameWord = function(s1,s2) {
		for (var i = 0; i < s1.length; ++i) {
			var ss1 = (i > 0 ? s1.substring(0,i) : "")+(i < s1.length-1 ? s1.substring(i+1) : "");
			if (ss1 == s2) return true;
			for (var j = 0; j < s2.length; ++j) {
				var ss2 = (j > 0 ? s2.substring(0,j) : "")+(j < s2.length-1 ? s2.substring(j+1) : "");
				if (ss2 == s1) return true;
				if (ss1 == ss2) return true;
			}
		}
		return false;
	};
	this.update = function(pic, canvas) {
		if (pic.select_people.selectedIndex <= 0 && pic.name) {
			var name = pic.name;
			// remove extension
			var i = name.lastIndexOf('.');
			if (i > 0) name = name.substring(0,i);
			name = this._words(name);
			for (var i = 1; i < pic.select_people.options.length; ++i) {
				var o = pic.select_people.options[i];
				var words = this._words(o.text);
				var complete = 0;
				var almost = 0;
				for (var j = 0; j < words.length; ++j) {
					if (name.contains(words[j])) complete++;
					else {
						for (var k = 0; k < name.length; ++k)
							if (this._almostSameWord(name[k], words[j])) {
								almost++;
								break;
							}
					}
				}
				if (complete == words.length) {
					// perfect match
					pic.select_people.selectedIndex = i;
					this._updateAvailablePeoples();
					return;
				}
				if (complete + almost == words.length) {
					// almost perfect match => it's ok
					pic.select_people.selectedIndex = i;
					this._updateAvailablePeoples();
					return;
				}
				// TODO continue;
			}
		}
	};
	this.createContent = function(pic) {
		var select = document.createElement("SELECT");
		var o = document.createElement("OPTION");
		o.value = 0;
		o.text = "";
		select.add(o);
		for (var i = 0; i < this.peoples.length; ++i) {
			var o = document.createElement("OPTION");
			o.value = this.peoples[i].id;
			o.text = this.peoples[i].last_name+" "+this.peoples[i].first_name;
			select.add(o);
		}
		select.t = this;
		select.onchange = function() {
			this.t._updateAvailablePeoples();
		};
		pic.select_people = select;
		return select;
	};
	this._updateAvailablePeoples = function() {
		var pics = this.images_tool.getPictures();
		var available_peoples = [];
		for (var i = 0; i < this.peoples.length; ++i) available_peoples.push(this.peoples[i]);
		// remove people already selected
		for (var i = 0; i < pics.length; ++i) {
			var p = this.getPeople(pics[i]);
			if (p != null)
				available_peoples.remove(p);
		}
		// sort people by last name
		available_peoples.sort(function(p1,p2) {
			var n1 = p1.last_name+" "+p1.first_name;
			var n2 = p2.last_name+" "+p2.first_name;
			return n1.localeCompare(n2);
		});
		// update the selects
		for (var i = 0; i < pics.length; ++i) {
			var selected = this.getPeople(pics[i]);
			var select = pics[i].select_people;
			var new_selected = -1;
			while (select.options.length > 1) select.remove(1);
			for (var j = 0; j < this.peoples.length; ++j) {
				if (available_peoples.contains(this.peoples[j]) || selected == this.peoples[j]) {
					var o = document.createElement("OPTION");
					o.value = this.peoples[j].id;
					o.text = this.peoples[j].first_name+" "+this.peoples[j].last_name;
					select.add(o);
					if (selected == this.peoples[j]) new_selected = select.options.length-1;
				}
			}
			if (new_selected > 0) select.selectedIndex = new_selected;
		}
	};
};
images_tool_people.prototype = new ImageTool;
images_tool_people.prototype.constructor = images_tool_people;
