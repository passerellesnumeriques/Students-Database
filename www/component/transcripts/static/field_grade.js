/* #depends[/static/widgets/typed_field/typed_field.js] */
/**
 * @param data
 * @param editable
 * @param config max: maximum grade, passing: passing grade, system: grading system
 * @returns
 */
function field_grade(data,editable,config) {
	if (typeof data == 'string') data = parseFloat(data);
	if (isNaN(data)) data = null;
	typed_field.call(this, data, editable, config);
}
field_grade.prototype = new typed_field();
field_grade.prototype.constructor = field_grade;		
field_grade.prototype.canBeNull = function() { return true; };		
field_grade.prototype._create = function(data) {
	var t=this;
	var init_system = function() {
		t.steps = [];
		var steps_str = t.config.system.split(",");
		for (var i = 0; i < steps_str.length; ++i) {
			var j = steps_str[i].indexOf("=");
			t.steps.push({percent:steps_str[i].substr(0,j),value:parseFloat(steps_str[i].substr(j+1))});
		}
	};
	init_system();
	this.setGradingSystem = function(system) {
		t.config.system = system;
		init_system();
		t._setData(t.getCurrentData());
		t.validate();
	};
	var get_step_grade = function(step_index) {
		var grade = t.steps[step_index].percent;
		if (grade == "passing") return t.config.passing != null ? t.config.passing : t.config.max != null ? t.config.max/2 : 0.5;
		if (grade == "max") return t.config.max != null ? t.config.max : 1;
		return 0;
	};
	/** Converts a grade into a displayable value according to the grading system */
	var get_value_from_system = function(grade) {
		var step = 0;
		do {
			var step_grade = get_step_grade(step);
			var next_step_grade = get_step_grade(step+1);
			var step_value = t.steps[step].value;
			var next_step_value = t.steps[step+1].value;
			if (grade >= step_grade && grade <= next_step_grade) {
				if (step_value < next_step_value) {
					// ascending order
					return step_value+((grade-step_grade)/(next_step_grade-step_grade))*(next_step_value-step_value);
				}
				// descending order
				return step_value-((grade-step_grade)/(next_step_grade-step_grade))*(step_value-next_step_value);
			}
			step++;
		} while (step < t.steps.length-1);
		// should never happen
		return "?";
	};
	/** Converts a displayable value into a grade, according to the grading system */
	var get_grade_from_system = function(value) {
		var step = 0;
		do {
			var step_grade = get_step_grade(step);
			var next_step_grade = get_step_grade(step+1);
			var step_value = t.steps[step].value;
			var next_step_value = t.steps[step+1].value;
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
		} while (step < t.steps.length-1);
		// should never happen
		return 0;
	};
	if (this.editable) {
		var input = document.createElement("INPUT");
		input.type = "text";
		input.onclick = function(ev) { this.focus(); stopEventPropagation(ev); return false; };
		input.maxLength = 6;
		input.size = 3;
		input.style.margin = "0px";
		input.style.padding = "0px";
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
		
		this._setData = function(data) {
			if (typeof data == 'string') data = parseFloat(data);
			if (isNaN(data)) data = null;
			input.value = data != null ? get_value_from_system(data) : "";
		};
		this._getEditedData = function() {
			if (input.value == "") return null;
			var value = parseFloat(input.value);
			if (isNaN(value)) return null;
			return get_grade_from_system(value);
		};

		this.signal_error = function(error) {
			this.error = error;
			input.style.border = error ? "1px solid red" : "";
			input.title = error ? error : "";
		};
		this.fillWidth = function() {
			this.element.style.width = "100%";
			input.style.width = "100%";
		};
		this.validate = function() {
			var grade = this._getEditedData();
			if (grade < 0) this.signal_error("Must be minimum "+get_value_from_system(0));
			else if (grade > t.config.max) this.signal_error("Must be maximum "+get_value_from_system(t.config.max));
			else this.signal_error(null);
			this.element.style.backgroundColor =
				grade == null ? "#C0C0C0" :
				grade < t.config.passing ? "#FFA0A0" :
				grade <= t.config.passing+(25*(t.config.max-t.config.passing)/100) ? "#FFC000" :
				"#A0FFA0";
			input.style.backgroundColor = this.element.style.backgroundColor;
		};
		this._setData(data);
		this.validate();
	} else {
		this._setData = function(grade) {
			var display_value = grade != null ? get_value_from_system(grade) : "";
			this.element.removeAllChildren();
			this.element.appendChild(document.createTextNode(display_value));
			this.element.style.height = "100%";
			this.element.style.display = "flex";
			this.element.style.alignItems = "center";
			this.element.style.justifyContent = "center";
			this.element.style.position = "absolute";
			this.element.style.top = "0px";
			this.element.style.left = "0px";
			this.element.style.backgroundColor =
				grade == null ? "#C0C0C0" :
				grade < t.config.passing ? "#FFA0A0" :
				grade <= t.config.passing+(25*(t.config.max-t.config.passing)/100) ? "#FFC000" :
				"#A0FFA0";
		};
		this._setData(data);
	}
};
