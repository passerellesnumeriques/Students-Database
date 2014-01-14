function create_applicants(applicants_data, all_ids, generate_id, check_date, limit_date, db_lock_id, screen_locker) {
	var t = this;
	t.biggest_id = null;
	
	t._init = function(){
		
		if(applicants_data.length == 0)
			return;
		var errors = [];
		var error_new_ids = false;
		var error_dates = false;
		if(!generate_id){
			for(var i = 0; i < applicants_data.length; i++){
				if(!t._checkApplicantId(applicants_data[i].applicant_id))
					errors.push(applicants_data[i].applicant_id);
			}
			//check that there is no double entry in the new applicants ids
			error_new_ids = t._checkNoDoubleEntryInNewIds();
		} else {
			t._setBiggestId();
			for(var i = 0; i < applicants_data.length; i++)
				applicants_data[i].applicant_id = t._generateApplicantId();
			// as the id have been generated, no need to check their unicity
		}
		if(check_date)
			error_dates = t._checkBirthDates();
		t._end(errors,error_new_ids,error_dates);
	}
	
	t._end = function(errors,error_new_ids,error_dates){
		if(typeof(error_new_ids) == "string" || errors.length > 0 || typeof(error_dates) == "string"){
			unlock_screen(screen_locker);
			if(typeof(error_dates) == "string")
				error_dialog(error_dates);
			if(typeof(error_new_ids) == "string")
				error_dialog(error_new_ids);
			if(errors.length > 0){
				var text = "Error: the applicants ID below already exist in the database:<br><ul>";
				for(var i = 0; i < errors.length; i++)
					text += "<li>"+errors[i]+"</li>";
				text += "</ul>";
				error_dialog(text);
			}
		} else {
			service.json("selection", "import_applicants",{data:applicants_data, db_lock:db_lock_id},function(res){
				unlock_screen(screen_locker);
				if(!res)
					error_dialog("An error occured, the applicants were not imported");
				else
					// /*must redirect because all_ids is no more updated*/
					location.assign("/dynamic/selection/page/selection_main_page");
			});
		}
	}
	
	t._checkApplicantId = function(id){
		var unique = true;
		for(var i = 0; i < all_ids.length; i++){
			if(parseInt(all_ids[i]) == parseInt(id)){
				unique = false;
				break;
			}
		}
		return unique;
	};
	
	t._checkNoDoubleEntryInNewIds = function(){
		var errors = "";
		var double_ids = {};
		var first = true;
		if(applicants_data.length == 1)
			return true;
		for(var i = 0; i < applicants_data.length; i++){
			var value = applicants_data[i].applicant_id;
			var others = [];
			for(var k = 0; k < applicants_data.length; k++){
				if(k != i)
					others.push(applicants_data[k].applicant_id);
			}
			for(var j = 0; j < others.length; j++){
				if(others[j] == value){
					if(double_ids[value] == null)
						double_ids[value] = 1;
					else
						double_ids[value]++;
					break;
				}
			}
		}
		if(double_ids != {}){
			for(i in double_ids){
				if(first)
					errors += "The following IDs are redundant:<br/><ul>";
				first = false;
				errors += "<li>"+i+", <i>"+double_ids[i]+" times</i></li>";
			}
		}
		if(errors.length > 0){
			errors += "</ul>";
			return errors;
		} else
			return true;
	}
	
	t._setBiggestId = function(){
		if(all_ids.length == 0)
			t.biggest_id = 0;
		else {
			t.biggest_id = all_ids[0];
			for(var i = 0; i < all_ids.length; i++){
				if(parseInt(all_ids[i]) > parseInt(t.biggest_id))
					t.biggest_id = parseInt(all_ids[i]);
			}
		}
	}
	
	t._generateApplicantId = function(number_of_digits){
		t.biggest_id++;
		return t.biggest_id;
	}
	
	t._addZeros = function(text,how_many){
		text = parseString(text);
		for(var i = 0; i <= how_many; i++)
			text = "0" + text;
		return text;
	}
	
	t._checkBirthDates = function(){
		errors = "";
		first = true;
		var date_splitted = limit_date.split("-");
		var limit_year = parseInt(date_splitted[0]);
		var limit_month = parseInt(date_splitted[1]);
		var limit_day = parseInt(date_splitted[2]);
		for(var i = 0; i < applicants_data.length; i++){
			var is_ok = t._checkBirthDate(applicants_data[i].birth,limit_year, limit_month, limit_day);
			if(!is_ok){
				if(first)
					errors += "Some students are over age limit:<ul>";
				first = false;
				errors += "<li>Student ID number "+applicants_data[i].applicant_id+" ("+applicants_data[i].last_name+", "+applicants_data[i].first_name+")</li>";
			}
		}
		if(errors.length > 0){
			errors += "</ul>";
			return errors;
		} else
			return true;
	}
	
	t._checkBirthDate = function(applicant_date, limit_year, limit_month, limit_day){
		var ap_splitted = applicant_date.split("-");
		var ap_year = parseInt(ap_splitted[0]);
		var ap_month = parseInt(ap_splitted[1]);
		var ap_day = parseInt(ap_splitted[2]);
		if(ap_year < limit_year)
			return false;
		else if(ap_year == limit_year){
			if(ap_month < limit_month)
				return false;
			else if(ap_month == limit_month){
				if(ap_day < limit_day)
					return false;
			}
		}
		return true;
	}
	
	t._init();
}