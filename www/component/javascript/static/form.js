/**
 * Retrieve the value for radio buttons on a form (search the selected one)
 * @param {Element} form the FORM
 * @param {String} radio_name the name of the radio buttons
 * @returns {String} the value of the selected one, or null if none selected
 */
function getRadioValue(form, radio_name) {
	if (typeof form == 'string') form = document.forms[form];
	var radios = form.elements[radio_name];
	if (radios instanceof Element) {
		if (radios.checked) return radios.value;
		return null;
	}
	for (var i = 0; i < radios.length; ++i)
		if (radios[i].checked)
			return radios[i].value;
	return null;
}