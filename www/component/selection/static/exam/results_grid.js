if (typeof require != 'undefined'){
   require ("exam_objects.js");
   require("applicant_objects.js");
   theme.css("grid.css");
   theme.css("results_grid.css"); // redefine some css property
}


/* results_grid : class to manage the exam subject results
 * @param {object} subject  : exam subject
 * @param {array} applicants : array of applicants objects
 * @param {array} versions : array of versions id for this subject
 * @param {String} grid_height : the height of the inside grid 
*/

function results_grid(subject,applicants,versions,grid_height) {
   var t=this;
   
    /* html elements */   
   t.elt = {
      container : $("<div class='container_results'></div>")[0],
      grid : $("<div class='grid_results'></div>")[0],
      footer : $("<div class='footer_results'></div>")[0],
   };
      
   t.subject=subject;
   t.applicants = applicants;
   t.versions=versions;
   
   t.grid_res=new grid (t.elt.grid); // creating grid   
   t.index_applicant=-1; // index of selected applicant
   t.applicants_exam=null;
   

   /* Getting current applicant
    * return people object matching the applicant
   */
   t.getCurrentApplicant=function(){
      
       if(t.index_applicant==-1) // no applicant selected
         return null;
      
      return t.applicants[t.index_applicant].people;
   }
   
   /* handling the selection of a new applicant's row
   * @param user_func : user function to call (with parameter people object)
   */
   t.onRowApplicantSelection=function(user_func){
      /* making rows selectable and adding listeners */
      t.grid_res.setSelectable(true,true);
      
      /* Adding listener on row focus */
      t.grid_res.onrowfocus.add_listener(function(row) {
         t.grid_res.selectByRowId(row.row_id,true); 
      });
      
      /* handling 'new row selected' event */
      t.grid_res.onrowselectionchange = function(row_id, selected) {

         if (!selected) return; // (unselected event => don't care)       
         
         t.index_applicant=row_id;
         
         /* Call user function, passing people object as parameter*/
         user_func(t.applicants[row_id].people);
      }
      
   }
   
   /* get container html element */
   t.getContainer=function(){
      return t.elt.container;
   }

   
   /* --- internal functions --- */
   
   t._init=function(){
      
      /* Appending html elements into main container */
      t.elt.container.appendChild(t.elt.grid);
      t.elt.container.appendChild(t.elt.footer);
      
      /* making rows scrollable but fixed header */
      t.grid_res.makeScrollable();
      
    /* creating applicant ID column */  
      t._createApplicantColumn();
    
    /* Creating Subject version column (if more than one version) */  
    if (t.versions.length>1) {
      t._createVersionColumn();
    }
      
      /* creating all the columns (one for each question) */
      t._createQuestionsColumns();
      
       /* add the applicants rows (one for each applicant) */
      t._createRowsApplicants();
      
      /* creating footer toolbar */
      t._createFooterToolbar();
      
      /* set grid height */
      $(t.elt.grid).height(grid_height);
      
      /* Clear previously set grid width css property (from grid.css) */
      $(t.elt.grid).find("table.grid").css("width","");

      /* Inserting some cell wrappers (in order to set table columns width) */
      t.grid_res.onallrowsready(function(){
            t._fixColumnsWidth();
          });
      }
   
   /* creating applicant ID column */
   t._createApplicantColumn=function(){
      var col_applicant=new GridColumn('col_applic','Applicant ID',null,'center','field_text',false,null,null,{},{});
      
      /* some CSS needed here (to set column width) */
      //col_applicant.col.className='applicant_id';
      //col_applicant.th.className='applicant_id';
      
      /* adding column into the grid */
      t.grid_res.addColumn(col_applicant);
      
   }
   
   /* creating version column */
   t._createVersionColumn=function(){
      var field_args={
         "possible_values":[],
          "can_be_empty":false
          };            
          
         for(var j=0;j<t.versions.length;++j)
           field_args.possible_values.push(new Array(t.versions[j],String.fromCharCode(j+65)));// push key (id) ,value to display
        
      var col_version=new GridColumn('col_version','Version',null,'center','field_enum',true,null,null,field_args,'#');
      
      /* some CSS needed here (to set column width) */
      //col_version.col.className='version';
      //col_version.th.className='version';
      
      /* adding column into the grid */
      t.grid_res.addColumn(col_version);
        
   }
   
   
   /* creating all the columns for each question */
   t._createQuestionsColumns=function(){
      
       for(var i=0;i<subject.parts.length;++i)
      {
         var part=subject.parts[i];
         var sub_cols=[];
         
         for (var j=0;j<part.questions.length;++j)
         {
            var question=part.questions[j];
            
            /* convert question field for grid widget */
            var grid_field=questionGridFieldType(question);
            var grid_args=questionGridFieldArgs(question);
                       
            /* create the new question Column */
            var col_question=new GridColumn('p'+part.id+'q'+question.id,'Question '+question.index,null,'center',grid_field,true,null,null,grid_args,'#');
            //col_question.col.className='questions';
            //col_question.th.className='questions';
            sub_cols.push(col_question);
         }
         
         /* push columns into the ColumContainer */
         t.grid_res.addColumnContainer(new GridColumnContainer(part.name,sub_cols,'#'));
      }
   }
   
   /* remove Questions Columns */
      t._removeQuestionsColumns=function(){
            
         var nb_cols=t.grid_res.getNbColumns();
         
         for(var i=0;i<nb_cols-1;++i)  
            t.grid_res.removeColumn(1);
  }
  
   /* Creating results columns */
   t._createResultsColumns=function(){
      
      for(var i=0;i<subject.parts.length;++i)
     {
        var part=subject.parts[i];
        var sub_cols=[];
        
        for (var j=0;j<part.questions.length;++j)
        {
           var question=part.questions[j];
           
           /* field decimal for displaying */
           var field_args={
	       can_be_null:true,
	       integer_digits:3,
	       decimal_digits:2,
	       };
            
                      
           /* create the new result Column */
           var col_result=new GridColumn('part'+part.index+'q'+question.index,'Question '+question.index,null,'center','field_decimal',false,null,null,field_args,'#')
           //col_result.col.className='question';
           //col_result.th.className='question';
           sub_cols.push(col_result);
        }
        
        /* push columns into the ColumContainer */
        t.grid_res.addColumnContainer(new GridColumnContainer(part.name,sub_cols,'#'));
     }
     
     //DEBUG (test)
     //var field_dbg=t.grid_res.getCellFieldById(1,'part0q2');
     //field_dbg.setData(7.1); 

     t._fixColumnsWidth();
   }
   
   
   /* creating the rows : one for each applicant */
   t._createRowsApplicants=function(){
      
        $.each(t.applicants,function(index,obj) {
          /* creating applicant row */
          t.grid_res.addRow(index,[{col_id:'col_applic',data_id:'#',data:obj.applicant_id}]);
         
        });
   }  
   
   /* creating footer toolbar */
   t._createFooterToolbar=function(){
   
      /* creating footer content elements */   
      var foot_content={
          button_wrapper :$("<div class='button_wrapper'></div>")[0],
          button_valid: $("<button class='action'>Validate</button>")[0]
      }
       
     /* Append the elements  */
     foot_content.button_wrapper.appendChild(foot_content.button_valid);
     t.elt.footer.appendChild(foot_content.button_wrapper);
     
     /* Click on validate button */
      $(foot_content.button_valid).click(function(){
         
      /* getting applicants answer */
      t._getAnswers();
      
      
      /* getting results from applicants answers */
      service.json("selection","applicant/get_results",{applicants_exam:t.applicants_exam,subject:t.subject},function(res){
         
         //TODO
         });
      
      
       /* Removing questions columns */
       t._removeQuestionsColumns();
       
       /* Creating results columns */
        t._createResultsColumns();

       });
   }
   

/* Set th cells width (by inserting some div inside) */
t._fixColumnsWidth=function(){
   
      //$(t.elt.grid).find('thead>tr>th').wrapInner($("<div>", {class: 'cell_wrapper'}));
      //
      ///* applicant selector */
      //$(t.elt.grid).find('thead>tr:first-child>th:first-child').addClass('applicant_cb');

  
   }

/* getting applicants answer */
t._getAnswers=function(){
         
          var applicants_res=[];
          var use_version=0;
         
         for (var row=0;row<t.applicants.length;++row) {
            
            /* init var */
            var applicant_id=t.applicants[row].applicant_id;
            var parts=[];
            var answers=[];
            var version_id=null;
            var last_part=-1;
   
            //get version id
            if (t.versions.length>1){
                  use_version=1;
                  var cell_field=t.grid_res.getCellFieldById(row,'col_version');
                  version_id=cell_field.getCurrentData();              
            }
            else
               version_id=t.versions[0];
         
            var nb_cols=t.grid_res.getNbColumns();
            for (var col=1+use_version;col<nb_cols;++col){
                         
               // getting part and question id 
               var col_id=t.grid_res.getColumn(col).id; // col_id='p{part_id}q{question_id}'               
               var part_id=col_id.slice(1,col_id.indexOf("q"));     
               var question_id=col_id.slice(col_id.indexOf("q")+1);
               
               /* does the question belong to a new part ? */
              if (part_id!=last_part && last_part!=-1) {
               /* pushing previous answers into last part */
               parts.push({exam_subject_part:last_part,score:null,answers:answers});
               answers=[];  
              }
                
               //getting answer value  
                var cell_field=t.grid_res.getCellFieldById(row,col_id);
                var answer=cell_field.getCurrentData();

                if (answer=="No data found for this column")
                  answer=null; 
               
               /* push answer object into answers array */
                answers.push({exam_subject_question:question_id,answer:answer,score:null});
              
              last_part=part_id;
            }
            // pushing answers of the last part
            parts.push({exam_subject_part:part_id,score:null,answers:answers});
            
            // pushing parts and other applicant data into an array 
            applicants_res.push({applicant:applicant_id,subject_version:version_id,score:null,parts:parts});
            parts=[]; 
         }
         
         // updating the final applicants exam object (i.e adding the 'exam_subject' data) 
         t.applicants_exam={exam_subject:t.subject.id,applicants_answers:applicants_res};
                      
      }
   
   /* Initialization */
   t._init();
}