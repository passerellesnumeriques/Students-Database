function center_applicants_list(container,EC_id){
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;
	t._init = function(){
		t._total_row = document.createElement('div');
		t._section_content_container.appendChild(t._total_row);
		t._setTotalRow();
		t._setButtonRow();
	};
	
	t._setTotalRow = function(){
		t._total_row.appendChild(document.createTextNode("Applicants assigned: "));
		t._loading = document.createElement("img");
		t._loading.src = theme.icons_16.loading;
		t._total_row.appendChild(t._loading);
		service.json("selection","exam/get_applicants_assigned_to_center",{EC_id:EC_id,count:true},function(res){
			if(!res)
				return;
			t._total_row.removeChild(t._loading);
			t._total_row.appendChild(document.createTextNode(res.count));
		});
	};
	
	t._setButtonRow = function(){
		var div = document.createElement("div");
		t._section_content_container.appendChild(div);
		var b = document.createElement("div");
		b.className = "button";
		b.appendChild(document.createTextNode("See / Edit List"));
		b.onclick = function(){
			var list = document.createElement("table");
			var pop = new popup_window("Applicants List","",list);
			pop.show();
		};
	};
	
	require(["section.js","popup_window.js"],function(){
		t._section_content_container = document.createElement("div");
		t.section = new section(null,"Applicants assigned",t._section_content_container,false,false,"soft");
		container.appendChild(t.section.element);
		t._init();
	});
}