function multiple_choice_other(container, choices, value, can_edit, onchange) {
	var values = value ? value.split(",") : [];
	this.element = document.createElement("DIV");
	container.appendChild(this.element);
	if (can_edit) {
		var checkboxes = [];
		var input;
		var changed = function() {
			var s = "";
			for (var i = 0; i < checkboxes.length; ++i) {
				if (!checkboxes[i].checked) continue;
				if (s != "") s += ", ";
				s += checkboxes[i]._value;
			}
			var o = input.value.trim();
			if (o.length > 0) {
				if (s.length > 0) s += ", ";
				s += o;
			}
			onchange(s);
		};
		for (var i = 0; i < choices.length; i+=2) {
			var div = document.createElement("DIV");
			this.element.appendChild(div);
			var cb = document.createElement("INPUT");
			cb.type = "checkbox";
			div.appendChild(cb);
			cb.style.verticalAlign = "middle";
			cb.style.marginBottom = "4px";
			cb.style.marginRight = "3px";
			div.appendChild(document.createTextNode(choices[i]));
			for (var j = 0; j < values.length; ++j)
				if (values[j].isSame(choices[i].trim())) {
					values.splice(j,1);
					cb.checked = "checked";
					break;
				}
			cb._value = choices[i];
			cb.onchange = changed;
			checkboxes.push(cb);
			if (choices[i+1]) {
				var help = document.createElement("IMG");
				help.style.verticalAlign = "middle";
				help.style.marginLeft = "5px";
				help.src = theme.icons_16.help;
				div.appendChild(help);
				tooltip(help, "<img src='/static/selection/si/"+choices[i+1]+"'/>");
			}
		}
		var div = document.createElement("DIV");
		this.element.appendChild(div);
		div.appendChild(document.createTextNode("Other: "));
		div.appendChild(input = document.createElement("INPUT"));
		input.type = "text";
		input.value = "";
		for (var i = 0; i < values.length; ++i) {
			if (input.value != "") input.value += ", ";
			input.value += values[i].trim();
		}
		input.onchange = changed;
	} else {
		var first = true;
		for (var i = 0; i < choices.length; i+=2) {
			var found = false;
			for (var j = 0; j < values.length; ++j)
				if (values[j].isSame(choices[i].trim())) {
					values.splice(j,1);
					found = true;
					break;
				}
			if (!found) continue;
			if (first) first = false;
			else this.element.appendChild(document.createTextNode(", "));
			this.element.appendChild(document.createTextNode(choices[i]));
			if (choices[i+1]) {
				var help = document.createElement("IMG");
				help.style.verticalAlign = "bottom";
				help.style.marginLeft = "2px";
				help.src = theme.icons_16.help;
				this.element.appendChild(help);
				tooltip(help, "<img src='/static/selection/si/"+choices[i+1]+"'/>");
			}
		}
		for (var i = 0; i < values.length; ++i) {
			if (first) first = false;
			else this.element.appendChild(document.createTextNode(", "));
			this.element.appendChild(document.createTextNode(values[i]));
		}
	}
}
