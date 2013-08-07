function validation_error(input, message) {
	input.className = 'validation_error';
	var id = input.id ? input.id : input.name;
	var e = document.getElementById(id+'_validation');
	if (e) {
		e.innerHTML = "<img src='"+theme.icons_16.error+"'/> "+message;
		e.style.position = 'static';
		e.style.visibility = 'visible';
	}
}
function validation_ok(input) {
	input.className = "";
	var id = input.id ? input.id : input.name;
	var e = document.getElementById(id+'_validation');
	if (e) {
		e.innerHTML = "";
		e.style.position = 'absolute';
		e.style.visibility = 'hidden';
		e.style.top = '-10000px';
	}
}