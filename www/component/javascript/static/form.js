function get_radio_value(form, radio_name) {
	if (typeof form == 'string') form = document.forms[form];
	var radios = form.elements[radio_name];
	for (var i = 0; i < radios.length; ++i)
		if (radios[i].checked)
			return radios[i].value;
	return null;
}