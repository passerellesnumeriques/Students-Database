function center_applicants_section(container,EC_id,EC_name,can_edit){
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;
	t._init = function(){
		t._table = document.createElement("table");
//		t._setTable();
		t._sessions_cont = document.createElement("div");
		container.appendChild(t._sessions_cont);
		t._setSessionsContent();
	};
	
//	t._setTable = function(){
//		var tr_head = document.createElement("tr");
//		var tr_body = document.createElement("tr");
//		var th1 = document.createElement("th");
//		var th2 = document.createElement("th");
//		t._td_list_center = document.createElement("td");
//		t._td_sessions = document.createElement("td");
//		tr_head.appendChild(th1);
//		tr_head.appendChild(th2);
//		tr_body.appendChild(t._td_list_center);
//		tr_body.appendChild(t._td_sessions);
//		t._table.appendChild(tr_head);
//		t._table.appendChild(tr_body);
//		container.appendChild(t._table);
//		//Set the border
//		t._td_list_center.style.borderRight = '1px solid #808080';
//	};
	
	t._setSessionsContent = function(){
//		new center_exam_sessions_planned(t._td_sessions,EC_id,can_edit);
		new center_exam_sessions_planned(t._sessions_cont,EC_id,EC_name,can_edit);
	};
	
	require(["center_exam_sessions_planned.js"],function(){
		t._init();
	});
}