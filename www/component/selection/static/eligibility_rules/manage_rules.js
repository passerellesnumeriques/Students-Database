function manage_rules(container, all_rules, all_topics, can_edit){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	
	t._init = function(){
		
	};
	
	require("diagram_display_manager.js",function(){
		t._init();
	});
}