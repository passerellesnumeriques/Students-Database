/*
 * Create data list for showing applicants attached to an exam session
 */
function createDataList(campaign_id)
{
   window.dl = new data_list(
                   "session_applicants_listDiv",
                   "Applicant", campaign_id,
                   [
                           "Personal Information.First Name",
                           "Personal Information.Last Name"
                   ],
                   [],
                   -1,
                   function(list) {
                           
                   }
           );
}

if (typeof $(document).ready != 'undefined') // handle background loading case
$(document).ready(function(){

   $("#session_info_locationDiv").hide();
   $("#session_applicants_listDiv").hide();
   

// Update the exam session info and applicants list boxes
   $("tr.clickable_row").click(function(){
      
      $("#session_info_locationDiv").show();
      
      $("#session_infoDiv").attr("collapsed","false");
      $("#session_applicantsDiv").attr("collapsed","false");
      
      // display selected row 
      $(this).addClass("selectedRow");
      $(this).siblings().removeClass("selectedRow");
      
      // get the exam session's data for the selected row
      var session_name= $(this).children().eq(0).text();
      var room_name= $(this).children().eq(1).text();      
      var exam_center_name =$(this).prevAll(".exam_center_row").first().children().eq(0).text();

      //Show a loader gif while waiting for updating
      var loader_img = $("<img>", {class: "loader-image", id: "loaderResultsImg", src: "/static/application/loading_100.gif"});
      $("#session_info_locationDiv").html(loader_img);
      
      // update exam session information box
      updateExamSessionInfo(session_name,room_name,exam_center_name);
      
      // update applicants list
      updateApplicantsList(this.getAttribute("session_id"),this.getAttribute("room_id"));
      
      $("#session_applicants_listDiv").show();
      
   });
   
/*
*
* update display of exam session information box
*/
function updateExamSessionInfo(session_name,room_name,exam_center_name) { 

   // Get exam center location from exam center name
      service.json("selection","exam/get_exam_center_location",exam_center_name,function(host_addr){   
      
      // Get the Postal Address from host address id's  
         service.json("contact","get_address",{"id":host_addr},function(postal_addr){
                var ec_addr_Div= new address_text(postal_addr);
                $(ec_addr_Div.element).prepend("<span><strong>Location :</strong></span>");
                $(ec_addr_Div.element).append("<span><strong> Status :</strong>TODO !</span>");
                $("#session_info_locationDiv").html(ec_addr_Div.element);
         });
      });
}

function updateApplicantsList(session_id,room_id) {
   
   window.dl.resetFilters();
   window.dl.addFilter({category:"Selection",name:"Exam Session",force:true,data:{values:[session_id]}});
   window.dl.addFilter({category:"Selection",name:"Exam Center Room",force:true,data:{values:[room_id]}});

   window.dl.reloadData();
}
});