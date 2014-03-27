function applicants_assignment_to_EC_status(container){
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;
	
	t._init = function(){
		//Set the info row
		var div1 = document.createElement("div");
		div1.style.paddingLeft = "5px";		
		if(t._not_assigned == 0 && t._total != 0){
			div1.appendChild(document.createTextNode("All the applicants are assigned to an exam center!"));
			div1.style.color = "green";
		} else if (t._total == 0){
			div1.appendChild(document.createTextNode("There is no applicant yet"));
			div1.style.fontStyle = "italic";
		} else {
			div1.appendChild(document.createTextNode("Applicants not assigned to any exam center: "+t._not_assigned+" / "+t._total));
			div1.style.color = "red";
		}
		container.appendChild(div1);
		//Set the assign remaining applicants button
		if(t._not_assigned != 0){
			require("popup_window.js",function(){
				var b = document.createElement('div');
				var div = document.createElement('div');
				div.style.textAlign = "center";
				b.className = "button";
				b.appendChild(document.createTextNode("Assign remaining applicants to Exam Centers"));
				b.style.margin = "5px";
				b.style.textAlign = "center";
				div.appendChild(b);
				container.appendChild(div);
				b.onclick = function(){
					var pop = new popup_window("Assign applicants to Exam Centers","","");
					pop.setContentFrame("/dynamic/selection/page/applicant/manually_assign_to_exam_entity?mode=center");
					pop.onclose = function(){location.reload();};//Applicants assignment figures may have been updated
					pop.show();
				};
			});
//			var b = document.createElement('div');
//			var div = document.createElement('div');
//			div.style.textAlign = "center";
//			b.className = "button";
//			b.appendChild(document.createTextNode("Assign remaining applicants to Exam Centers"));
//			b.style.margin = "5px";
//			b.style.textAlign = "center";
//			div.appendChild(b);
//			container.appendChild(div);
//			b.onclick = function(){
//				require(["prepare_applicant_list.js","popup_window.js"],function(){
//					var for_list = new prepare_applicant_list();
//					//keep only the applicants assigned to no center
//					for_list.addFilter("Exam Center",null,true);
//					for_list.forbidApplicantCreation();
//					for_list.forbidApplicantImport();
//					for_list.makeApplicantsSelectable();
//					var p = new popup_window("Applicant not assigned to center");
//					p.setContentFrame("/dynamic/selection/page/applicant/list",null,for_list.getDataToPost());
//					p.show();
//				});
//			};
		}
	};
	
	service.json("selection","applicant/get_assignment_to_EC_figures",{},function(res){
		if(!res)
			return;
		t._total = res.total;
		t._not_assigned = res.not_assigned;
		t._init();
	});
}