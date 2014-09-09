/* #depends[/static/widgets/typed_field/typed_field.js] */
/**
 * @param data
 * @param editable
 * @param config max: maximum grade, passing: passing grade, system: grading system
 * @returns
 */
function field_grade(data,editable,config) {
	if (typeof config.max == 'string') config.max = parseFloat(config.max);
	if (typeof config.passing == 'string') config.passing = parseFloat(config.passing);
	if (typeof data == 'string') data = parseFloat(data);
	if (isNaN(data)) data = null;
	typed_field.call(this, data, editable, config);
}
field_grade.prototype = new typed_field();
field_grade.prototype.constructor = field_grade;		
field_grade.prototype.canBeNull = function() { return true; };
field_grade.prototype.compare = function(v1,v2) {
	if (v1 == null) return v2 == null ? 0 : -1;
	if (v2 == null) return 1;
	v1 = parseFloat(v1);
	if (isNaN(v1)) return 1;
	v2 = parseFloat(v2);
	if (isNaN(v2)) return -1;
	if (v1 < v2) return -1;
	if (v1 > v2) return 1;
	return 0;
};
field_grade.prototype.init_system = function() {
	this.steps = [];
	if (this.config.system == null || this.config.system.length == 0) return;
	var i = this.config.system.indexOf('/');
	var s;
	this.digits = 2;
	if (i < 0) {
		s = this.config.system;
	} else {
		s = this.config.system.substr(0,i);
		var cfg = this.config.system.substring(i+1).split(",");
		for (var j = 0; j < cfg.length; ++j) {
			i = cfg[j].indexOf('=');
			if (i < 0) continue;
			var name = cfg[j].substr(0,i);
			var value = cfg[j].substr(i+1);
			if (name == "digits") this.digits = parseInt(value);
		}
	}
	var steps_str = s.split(",");
	for (i = 0; i < steps_str.length; ++i) {
		var j = steps_str[i].indexOf("=");
		this.steps.push({percent:steps_str[i].substr(0,j),value:parseFloat(steps_str[i].substr(j+1))});
	}
};
field_grade.prototype.setGradingSystem = function(system) {
	this.config.system = system;
	this.init_system();
	this._setData(this.getCurrentData());
	this.validate();
};
field_grade.prototype.setMaxAndPassingGrades = function(max,passing) {
	this.config.max = max;
	this.config.passing = passing;
	this._setData(this.getCurrentData());
	this.validate();
};
field_grade.prototype.get_step_grade = function(step_index) {
	var grade = this.steps[step_index].percent;
	if (grade == "passing") return this.config.passing != null ? this.config.passing : this.config.max != null ? this.config.max/2 : 0.5;
	if (grade == "max") return this.config.max != null ? this.config.max : 1;
	return 0;
};
/** Converts a grade into a displayable value according to the grading system */
field_grade.prototype.get_value_from_system = function(grade) {
	if (this.steps.length == 0)
		return (grade*this.config.max/100).toFixed(2); // custom display
	var step = 0;
	do {
		var step_grade = this.get_step_grade(step);
		var next_step_grade = this.get_step_grade(step+1);
		if (this.config.max) {
			step_grade = step_grade*100/this.config.max;
			next_step_grade = next_step_grade*100/this.config.max;
		}
		var step_value = this.steps[step].value;
		var next_step_value = this.steps[step+1].value;
		if (grade >= step_grade && grade <= next_step_grade) {
			if (step_value < next_step_value) {
				// ascending order
				return (step_value+((grade-step_grade)/(next_step_grade-step_grade))*(next_step_value-step_value)).toFixed(this.digits);
			}
			// descending order
			return (step_value-((grade-step_grade)/(next_step_grade-step_grade))*(step_value-next_step_value)).toFixed(this.digits);
		}
		step++;
	} while (step < this.steps.length-1);
	// should never happen
	return "?";
};
/** Converts a displayable value into a grade, according to the grading system */
field_grade.prototype.get_grade_from_system = function(value) {
	if (this.steps.length == 0)
		return value*100/this.config.max;// custom display
	var step = 0;
	do {
		var step_grade = this.get_step_grade(step);
		var next_step_grade = this.get_step_grade(step+1);
		if (this.config.max) {
			step_grade = step_grade*100/this.config.max;
			next_step_grade = next_step_grade*100/this.config.max;
		}
		var step_value = this.steps[step].value;
		var next_step_value = this.steps[step+1].value;
		if (step_value < next_step_value) {
			// ascending order
			if (value >= step_value && value <= next_step_value) {
				return step_grade+((value-step_value)/(next_step_value-step_value))*(next_step_grade-step_grade);
			}
		} else {
			// descending order
			if (value >= next_step_value && value <= step_value) {
				return next_step_grade-((value-next_step_value)/(step_value-next_step_value))*(next_step_grade-step_grade);
			}
		}
		step++;
	} while (step < this.steps.length-1);
	// should never happen
	return 0;
};
field_grade.prototype.getGradeColor = function(grade) {
	return grade === null ? "#C0C0C0" :
		grade < this.config.passing*100/this.config.max ? "#FF8080" :
		grade <= (this.config.passing+(25*(this.config.max-this.config.passing)/100))*100/this.config.max ? "#FFC000" :
		"#80FF80";
};
field_grade.prototype.exportCell = function(cell) {
	var val = this.getCurrentData();
	if (val == null)
		cell.value = "";
	else {
		cell.value = this.get_value_from_system(val);
		cell.format = "number:"+this.digits;
	}
	if (typeof this.config.color == 'undefined' || this.config.color)
		cell.style = {backgroundColor:this.getGradeColor(val)};
};
field_grade.prototype._create = function(data) {
	var t=this;
	this.init_system();
	if (this.editable) {
		var input = document.createElement("INPUT");
		input.type = "text";
		input.onclick = function(ev) { this.focus(); stopEventPropagation(ev); return false; };
		input.maxLength = 6;
		input.size = 3;
		input.style.margin = "0px";
		input.style.padding = "0px";
		input.style.border = "1px solid black";
		var onkeyup = new Custom_Event();
		input.onkeyup = function(e) { onkeyup.fire(e); };
		input.onkeydown = function(e) {
			var ev = getCompatibleKeyEvent(e);
			if (ev.isPrintable) {
				if (!isNaN(parseInt(ev.printableChar)) || ev.ctrlKey) {
					// digit: ok
					return true;
				}
				// not a digit
				if (ev.printableChar == "-" && input.value.length == 0) {
					// - at the beginning: ok
					return true;
				}
				if (ev.printableChar == ",") ev.printableChar = e.keyCode = ".";
				if (ev.printableChar == ".") {
					if (input.value.length > 0 && input.value.indexOf('.') < 0) {
						return true;
					}
				}
				stopEventPropagation(e);
				return false;
			}
			return true;
		};
		input.onblur = function(ev) {
			t.setData(t._getEditedData());
		};
		listenEvent(input, 'focus', function() { t.onfocus.fire(); });
		this.onenter = function(listener) {
			onkeyup.add_listener(function(e) {
				var ev = getCompatibleKeyEvent(e);
				if (ev.isEnter) listener(t);
			});
		};
		this.element.appendChild(input);
		
		this._setData = function(data, from_input) {
			if (typeof data == 'string') data = parseFloat(data);
			if (isNaN(data)) data = null;
			if (from_input && data !== null) data = this.get_grade_from_system(data);
			input.value = data != null ? this.get_value_from_system(data) : "";
			return data;
		};
		this._getEditedData = function() {
			if (input.value == "") return null;
			var value = parseFloat(input.value);
			if (isNaN(value)) return null;
			return this.get_grade_from_system(value);
		};

		this.signal_error = function(error) {
			this.error = error;
			input.style.border = error ? "1px solid red" : "1px solid black";
			input.title = error ? error : "";
		};
		this.fillWidth = function() {
			this.element.style.width = "100%";
			input.style.width = "100%";
		};
		this.focus = function() { input.focus(); };
		this.validate = function() {
			var grade = this._getEditedData();
			if (grade < 0) this.signal_error("Must be minimum "+this.get_value_from_system(0));
			else if (grade > 100) this.signal_error("Must be maximum "+this.get_value_from_system(t.config.max));
			else this.signal_error(null);
			this.element.style.backgroundColor =
				grade == null ? "#C0C0C0" :
				grade < t.config.passing*100/t.config.max ? "#FFA0A0" :
				grade <= (t.config.passing+(25*(t.config.max-t.config.passing)/100))*100/t.config.max ? "#FFC000" :
				"#A0FFA0";
			input.style.backgroundColor = this.element.style.backgroundColor;
		};
		this._setData(data);
		this.validate();
	} else {
		this._setData = function(grade, from_input) {
			if (from_input && grade !== null) grade = this.get_grade_from_system(grade);
			var display_value = grade !== null ? this.get_value_from_system(grade) : "";
			this.element.removeAllChildren();
			this.element.appendChild(document.createTextNode(display_value));
			if (typeof this.config.color == 'undefined' || this.config.color) {
				this.element.style.height = "100%";
				this.element.style.display = "flex";
				this.element.style.alignItems = "center";
				this.element.style.justifyContent = "center";
				this.element.style.position = "absolute";
				this.element.style.top = "0px";
				this.element.style.left = "0px";
				this.element.style.backgroundColor = this.getGradeColor(grade);
			}
			return grade;
		};
		this._setData(data);
	}
};
