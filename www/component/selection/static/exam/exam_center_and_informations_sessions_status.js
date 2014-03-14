/**
 * Populate the container with the exam centers and informations sessions statistics
 * This function retrieves the data from database with selection/exam/center_and_informations_sessions_status service
 * @param {HTMLElement | String} container element or its ID
 */
function exam_center_and_informations_sessions_status(container){
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;
	
	/**
	 * Launch the process, create 2 statistics rows and a button (link to convert_IS_into_center page) on a third one
	 */
	t._init = function(){
		var div1 = document.createElement("div");
		var div2 = document.createElement("div");
		div1.style.paddingLeft = "5px";
		div2.style.paddingLeft = "5px";		
		var div3 = document.createElement("div");
		div1.appendChild(document.createTextNode("Exam Centers linked to any Information Session "));
		var img = document.createElement("img");
		img.style.cursor = "pointer";
		img.src = theme.icons_16.info;
		var tip = "<div>When an exam center is linked to any information sessions, all the applicants declared to these informations sessions will be automatically assigned to this exam center</div>";
		tooltip(img,tip);
		div1.appendChild(img);
		div1.appendChild(document.createTextNode(" : "+t._linked_EC+" / "+t._total_EC));
		container.appendChild(div1);		
		div2.appendChild(document.createTextNode("Information Sessions not linked to any Exam Center: "+t._not_linked_IS+" / "+t._total_IS));
		container.appendChild(div2);
		var b = document.createElement("div");
		b.className = "button";
		b.appendChild(document.createTextNode("Assign Informations Sessions to Exams Centers"));
		b.onclick = function(){
			require("popup_window.js",function(){
				var pop = new popup_window("Create Exam Center from Information Session","");
				pop.setContentFrame("/dynamic/selection/page/exam/convert_IS_into_center");
				pop.onclose = function(){ //Refresh in the case of any centers have been created
					location.reload();
				};
				pop.show();
			});
		};
		div3.appendChild(b);
		div3.style.textAlign = "center";
		container.appendChild(div3);
	};
	
	service.json("selection","exam/center_and_informations_sessions_status",{},function(res){
		if(res){
			t._total_IS = res.total_IS == null ? 0 : res.total_IS;
			t._total_EC = res.total_EC == null ? 0 : res.total_EC;
			t._linked_EC = res.linked_EC == null ? 0 : res.linked_EC;
			t._not_linked_IS = res.not_linked_IS  == null ? 0 : res.not_linked_IS;
			t._init();
		}
	});
}