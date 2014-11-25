/**
 * Display a popup with the given error message
 * @param {string} message error message
 */
function error_dialog(message) {
	require("popup_window.js",function() {
		var p = new popup_window("Error", theme.icons_16.error, "<div style='padding:5px'>"+message+"</div>");
		p.show();
	});
}

/**
 * Display a popup with the given error message
 * @param {HTMLElement} content error message
 */
function error_dialog_html(content){
	require("popup_window.js",function() {
		var div = document.createElement("div");
		div.style.padding = "5px";
		div.appendChild(content);
		var p = new popup_window("Error", theme.icons_16.error, div);
		p.show();
	});
}
/**
 * Display a popup asking for confirmation (Yes and No buttons)
 * @param {string} message the message to display, asking confirmation
 * @param {function} handler called with true if the user answered Yes, or false if the user answered No or closed the window without answering
 */
function confirm_dialog(message, handler) {
	require("popup_window.js",function() {
		var content = document.createElement("DIV");
		content.style.padding = "5px";
		if (typeof message == 'string') content.innerHTML = message;
		else content.appendChild(message);
		var p = new popup_window("Confirmation", theme.icons_16.question, content);
		var result = false;
		p.addYesNoButtons(function() {
			result = true;
			p.close();
		});
		p.onclose = function() {
			handler(result);
		};
		p.show();
	});
}

function info_dialog(message) {
	require("popup_window.js",function() {
		var p = new popup_window("Information", theme.icons_16.info, "<div style='padding:5px'>"+message+"</div>");
		p.show();
	});
}

function choice_buttons_dialog(message, choices, handler) {
	require("popup_window.js",function() {
		var content = document.createElement("DIV");
		content.style.padding = "5px";
		if (typeof message == 'string') content.innerHTML = message;
		else content.appendChild(message);
		var p = new popup_window("Question", theme.icons_16.question, content, true);
		for (var i = 0; i < choices.length; ++i) {
			p.addIconTextButton(null, choices[i], "choice_"+i, function(choice_index) {
				handler(choice_index);
				p.close();
			},i);
		}
		p.show();
	});
}

/**
 * Ask the user to input a value in a popup window.
 * @param {string} icon url of the icon
 * @param {string} title title of the popup
 * @param {string} message message to display, describing the expected input
 * @param default_value
 * @param max_length maximum number of characters
 * @param validation_handler called each time the user change the value: if it returns null it means the value is correct, else it must return the error message describing why the input is not correct
 * @param ok_handler called when the user clicked on Ok or Cancel. If ok the value is given as parameter (and is already validated by the validation_handler), if cancel, null is given as parameter
 * @param oncancel (optional) called when the user clicks on cancel button
 */
function input_dialog(icon,title,message,default_value,max_length,validation_handler,ok_handler, oncancel) {
	require("popup_window.js",function() {
		var content = document.createElement("DIV");
		content.innerHTML = message+"<br/>";
		content.style.margin = "10px";
		var input = document.createElement("INPUT");
		input.type = 'text';
		input.value = default_value;
		input.maxLength = max_length;
		input.style.width = '100%';
		content.appendChild(input);
		var error_div = document.createElement("DIV");
		error_div.style.visibility = 'hidden';
		error_div.style.position = 'absolute';
		error_div.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> ";
		var error_message = document.createElement("SPAN");
		error_message.style.color = 'red';
		error_div.appendChild(error_message);
		content.appendChild(error_div);
		var p = new popup_window(title, icon, content);
		var result = null;
		if(oncancel)
			p.addOkCancelButtons(function() {
				result = input.value;
				p.close();
			},oncancel);
		else
			p.addOkCancelButtons(function() {
				result = input.value;
				p.close();
			});
		input.onkeypress = function(e) {
			var ev = getCompatibleKeyEvent(e);
			if (ev.isEnter && !p.getIsDisabled("ok"))
				p.pressButton('ok');
			if (ev.isEscape && !p.getIsDisabled("cancel"))
				p.pressButton('cancel');
		};
		var validate = function() {
			var error = validation_handler(input.value);
			if (error != null) {
				p.disableButton('ok');
				input.style.border = "1px solid red";
				error_message.innerHTML = error;
				error_div.style.visibility = 'visible';
				error_div.style.position = 'static';
				layout.changed(content);
			} else {
				p.enableButton('ok');
				input.style.border = "";
				error_div.style.visibility = 'hidden';
				//error_div.style.position = 'absolute'; // commented because it is strange to the user to have the popup resized like that...
				layout.changed(content);
			}
		};
		validate();
		input.onkeyup = input.onblur = validate;
		p.onclose = function() {
			var r=result; result=null;
			ok_handler(r,p);
			input.onkeyup = null;
			input.onblur = null;
			p = null;
			content = null;
			input = null;
			error_div = null;
			error_message = null;
			validate = null;
		};
		p.show();
		input.focus();
	});
}

/**
 * TODO: documentation
 */
function multiple_input_dialog(icon,title,inputs,ok_handler) {
	require("popup_window.js",function() {
		var p = null;
		var content = document.createElement("TABLE");
		var update_dialog = function() {
			var ok = true;
			for (var i = 0; i < inputs.length; ++i)
				if (!inputs[i].validation_result) { ok = false; break; }
			if (ok)
				p.enableButton('ok');
			else
				p.disableButton('ok');
			layout.changed(content);
		};
		for (var i = 0; i < inputs.length; ++i) {
			var tr = document.createElement("TR"); content.appendChild(tr);
			var msg = document.createElement("TD");
			msg.innerHTML = inputs[i].message;
			tr.appendChild(msg);
			var td = document.createElement("TD"); tr.appendChild(td);
			inputs[i].input = document.createElement("INPUT");
			inputs[i].input.type = 'text';
			inputs[i].input.value = inputs[i].default_value;
			inputs[i].input.maxLength = inputs[i].max_length;
			td.appendChild(inputs[i].input);
			inputs[i].error_container = document.createElement("TR"); content.appendChild(inputs[i].error_container);
			inputs[i].error_container.style.visibility = 'hidden';
			inputs[i].error_container.style.position = 'absolute';
			td = document.createElement("TD"); inputs[i].error_container.appendChild(td);
			td.colSpan=2;
			td.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> ";
			inputs[i].error_message = document.createElement("SPAN");
			inputs[i].error_message.style.color = 'red';
			td.appendChild(inputs[i].error_message);
			inputs[i].validate = function() {
				var error = this.validation_handler(this.input.value);
				if (error != null) {
					this.input.style.border = "1px solid red";
					this.error_message.innerHTML = error;
					this.error_container.style.visibility = 'visible';
					this.error_container.style.position = 'static';
					this.validation_result = false;
					update_dialog();
				} else {
					this.input.style.border = "";
					this.error_container.style.visibility = 'hidden';
					this.error_container.style.position = 'absolute';
					this.validation_result = true;
					update_dialog();
				}
			};
			inputs[i].input.data = inputs[i];
			inputs[i].input.onkeyup = inputs[i].input.onblur = function() { this.data.validate(); };
		}
		p = new popup_window(title, icon, content);
		var result = null;
		p.addOkCancelButtons(function() {
			result = [];
			for (var i = 0; i < inputs.length; ++i)
				result.push(inputs[i].input.value);
			p.close();
		});
		p.onclose = function() {
			ok_handler(result);
		};
		p.show();
		for (var i = 0; i < inputs.length; ++i)
			inputs[i].validate();
	});
}

function select_dialog(icon,title,message,default_value,possibilities, ok_handler, oncancel) {
	require("popup_window.js",function() {
		var content = document.createElement("DIV");
		content.innerHTML = message;
		content.style.padding = "3px";
		var select = document.createElement("SELECT");
		var selected = -1;
		for (var i = 0; i < possibilities.length; ++i) {
			var p = possibilities[i];
			var o = document.createElement("OPTION");
			o.value = p[0];
			o.text = p[1];
			select.add(o);
			if (p[0] == default_value) selected = select.options.length-1;
		}
		if (selected != -1) select.selectedIndex = selected;
		content.appendChild(select);
		var p = new popup_window(title, icon, content);
		var result = null;
		if(oncancel)
			p.addOkCancelButtons(function() {
				result = select.options[select.selectedIndex].value;
				p.close();
			},oncancel);
		else
			p.addOkCancelButtons(function() {
				result = select.options[select.selectedIndex].value;
				p.close();
			});
		p.onclose = function() {
			var r=result; result=null;
			ok_handler(r,p);
		};
		p.show();
	});
}


function popup_frame(icon, title, url, post_data, percent_w, percent_h, onframecreated) {
	require("popup_window.js", function() {
		var p = new popup_window(title, icon, "");
		var frame = p.setContentFrame(url, null, post_data);
		if (onframecreated) onframecreated(frame, p);
		if (percent_w)
			p.showPercent(percent_w, percent_h);
		else
			p.show();
	});
}