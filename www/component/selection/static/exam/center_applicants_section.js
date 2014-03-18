function center_applicants_section(container,EC_id){
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;
	t._init = function(){
		t._setListContent();
	};
	
	t._setListContent = function(){
		var div_list = document.createElement("div");
		container.appendChild(div_list);
		div_list.style.display = "inline-block";
		div_list.margin = "10px";
		new center_applicants_list(div_list,EC_id);
	};
	
	require("center_applicants_list.js",function(){
		t._init();
	});
}