function center_applicants_list(container,EC_id,can_edit){
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;
	container.style.margin = "10px";
	container.style.marginLeft = "15px";
	
	t.reset = function(){
		while(t._section_content_container.firstChild)
			t._section_content_container.removeChild(t._section_content_container.firstChild);
		t._init();
	};
	
	t._init = function(){
		t._total_row = document.createElement('div');
		t._section_content_container.appendChild(t._total_row);
		t._setTotalRow();
		t._setButtonRow();
	};
	
	t._setTotalRow = function(){
		t._total_row.appendChild(document.createTextNode("Applicants assigned: "));
		t._total_row.style.padding = "5px";
		t._loading = document.createElement("img");
		t._loading.src = theme.icons_16.loading;
		t._total_row.appendChild(t._loading);
		service.json("selection","exam/get_applicants_assigned_to_center_entity",{EC_id:EC_id,count:true},function(res){
			if(!res)
				return;
			t._total_row.removeChild(t._loading);
			t._total_row.appendChild(document.createTextNode(res.count));
		});
	};
	
	t._setButtonRow = function(){
		var div = document.createElement("div");
		div.style.textAlign = "center";
		t._section_content_container.appendChild(div);
		var b = document.createElement("div");
		b.className = "button";
		b.appendChild(document.createTextNode("See / Edit List"));
		b.onclick = function(){
			var pop = new pop_applicants_list_in_center_entity(EC_id,null,null,can_edit);
			pop.pop.onclose = t.reset;
		};
		var b_export = document.createElement("div");
		b_export.className = "button";
		b_export.innerHTML = "<img src = '"+theme.icons_16._export+"'/> Export List";
		b_export.onclick = function(){
			var button = this;
			require("context_menu.js",function(){
				var menu = new context_menu();
				menu.addTitleItem(null,"Export Format");
				var old = document.createElement("div");
				old.className = "context_menu_item";
				old.innerHTML = "<img src = '/static/excel/excel_16.png'/> Excel 5 (.xls)";
				old.onclick = function(){
					export_applicant_list("excel5",null,null,EC_id,null,null,'name');
				};
				menu.addItem(old);
				var new_excel = document.createElement("div");
				new_excel.className = "context_menu_item";
				new_excel.innerHTML = "<img src = '/static/excel/excel_16.png'/> Excel 2007 (.xlsx)";
				new_excel.onclick = function(){
					export_applicant_list("excel2007",null,null,EC_id,null,null,'name');
				};
				menu.addItem(new_excel);				
				menu.showBelowElement(button);
			});
		};
		div.appendChild(b);
		div.appendChild(b_export);
	};
	
	require(["section.js","pop_applicants_list_in_center_entity.js"],function(){
		t._section_content_container = document.createElement("div");
		t.section = new section(null,"Applicants assigned",t._section_content_container,false,false,"soft");
		container.appendChild(t.section.element);
		t._init();
	});
}