if (typeof require != 'undefined'){
   require ("exam_objects.js");
   require("applicant_objects.js");
   theme.css("grid.css");
   theme.css("results_grid.css"); // redefine some css property
}


/* results_grid : class to manage the exam subject results
 * @param {object} subject  : exam subject
 * @param {array} applicants : array of applicants objects
 * @param {String} grid_height : the height of the inside grid
 * @param {boolean} set_correct_answer : Set the correct answer of an exam subject question, and correct automatically applicants' answers
*/

function results_grid(subject,applicants,grid_height,set_correct_answer) {
   
   
   var t=this;
   
    /* html elements */   
   t.elt = {
      container : $("<div class='container_results'></div>")[0],
      grid : $("<div class='grid_results'></div>")[0],
      footer : $("<div class='footer_results'></div>")[0],
   };
      
   t.subject=subject;
   t.applicants = applicants;
   t.set_correct_answer=set_correct_answer;
   
   t.grid_res=new grid (t.elt.grid); // creating grid   
   t.index_applicant=-1; // index of selected applicant
   t.applicants_exam=null;
   t.rows_ready=false;

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
    if (t.subject.versions.length>1) {
      t._createVersionColumn();
    }
      
      
      /* Setting the screen according to the mode defined by set_correct_answer */
      if (t.set_correct_answer) 
         t._answerScreen();
      else
         t._scoreScreen(true);
      
       /* add the applicants rows (one for each applicant) */
      t._createRowsApplicants();
      
      /* set grid height */
      $(t.elt.grid).height(grid_height);
      
      /* Clear previously set grid width css property (from grid.css) */
      $(t.elt.grid).find("table.grid").css("width","");
      
      layout.changed(t.elt.container);
   }
   
   /* creating applicant ID column */
   t._createApplicantColumn=function(){
      var col_applicant=new GridColumn('col_applic','Applicant ID',null,'center','field_text',false,null,null,{},{});
      
      /* adding column into the grid */
      t.grid_res.addColumn(col_applicant);
      
   }
   
   /* creating version column */
   t._createVersionColumn=function(){
      var field_args={
         "possible_values":[],
          "can_be_null":false
          };            
          
         for(var j=0;j<t.subject.versions.length;++j)
           field_args.possible_values.push(new Array(t.subject.versions[j],String.fromCharCode(j+65)));// push key (id) ,value to display
        
      var col_version=new GridColumn('col_version','Version',null,'center','field_enum',true,null,null,field_args,'#');      
      
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
            sub_cols.push(col_question);
         }
         
         /* push columns into the ColumContainer */
         t.grid_res.addColumnContainer(new GridColumnContainer(part.name,sub_cols,'#'));
      }
   }
   
   /* remove Questions Columns */
      t._removeColumns=function(){
            
         var nb_cols=t.grid_res.getNbColumns();
         
         for(var i=0;i<nb_cols-1;++i)  
            t.grid_res.removeColumn(1);
  }
  
     /* toggle Questions Columns editability
     */
      t._toggleEditableColumns=function(){
            
         var nb_cols=t.grid_res.getNbColumns();
         
         for(var i=1;i<nb_cols;++i)  
            t.grid_res.getColumn(i).toggleEditable();
  }
  
  
   /* Creating results columns
    * @param {boolean} editable
   */
   t._createResultsColumns=function(editable){
      
   /* field decimal for displaying */
     var field_args={
         can_be_null:true,
         integer_digits:3,
         decimal_digits:2,
         };
         
      /* Total Exam Column */
      var total_exam=new GridColumn('total_exam','Total',null,'center','field_decimal',editable,null,null,field_args,'#');
      t.grid_res.addColumn(total_exam);
      
      // for each parts 
      for(var i=0;i<subject.parts.length;++i)
     {
        var part=subject.parts[i];
        var sub_cols=[];
        
        // for each question
        for (var j=0;j<part.questions.length;++j)
        {
           var question=part.questions[j];
            
           /* create the new result Column */
           var col_result=new GridColumn('p'+part.id+'q'+question.id,'Question '+question.index,null,'center','field_decimal',editable,null,null,field_args,'#');
      
           sub_cols.push(col_result);

        }
        
        /* total part column */
        /* create the new result Column */
           var total_part=new GridColumn('total_p'+part.id,'Total',null,'center','field_decimal',editable,null,null,field_args,'#');
           sub_cols.push(total_part);
        
        /* push columns into the ColumContainer */
        t.grid_res.addColumnContainer(new GridColumnContainer(part.name,sub_cols,'#'));
        
     }
     
   }
   
   
   /* creating the rows : one for each applicant */
   t._createRowsApplicants=function(){
      
        $.each(t.applicants,function(index,obj) {
          /* creating applicant row */
          t.grid_res.addRow(index,[{col_id:'col_applic',data_id:'#',data:obj.applicant_id}]);
        });
   }  
   
   

/* Get applicants answer */
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
            if (t.subject.versions.length>1){
                  use_version=1;
                  var cell_field=t.grid_res.getCellFieldById(row,'col_version');
                  version_id=cell_field.getCurrentData();              
            }
            else
               version_id=t.subject.versions[0];
         
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

/* Fill rows with applicants results scores */
t._fillResultsRows=function()
   {
      if (!t.applicants_exam) {
        return;
      }
      
      /* for each row (one applicant) */
      for (var row_id=0;row_id<t.applicants_exam.applicants_answers.length;++row_id) {
         var applicant_answers=t.applicants_exam.applicants_answers[row_id];
         
         //total exam
         var cell_field=t.grid_res.getCellFieldById(row_id,'total_exam');
         cell_field.setData(applicant_answers.score);
         
         // for each part
         for(var i=0;i<applicant_answers.parts.length;++i)
         {
            var part=applicant_answers.parts[i];

            // for each answer
            for (var j=0;j<part.answers.length;++j)
            {
              
               var answer=part.answers[j];
               var cell_field=t.grid_res.getCellFieldById(row_id,'p'+part.exam_subject_part+'q'+answer.exam_subject_question);
               cell_field.setData(answer.score);
            }
            
            // total part
            var cell_field=t.grid_res.getCellFieldById(row_id,'total_p'+part.exam_subject_part);
            cell_field.setData(part.score);
            
         }
      }
   }
   
   
   /* Creating screen for entering answers */
   t._answerScreen=function()
   {
      
      t.clearScreen();
      
      /* creating all the columns (one for each question) */
      t._createQuestionsColumns();
      
      
      /* footer of answerScreen */
      
      /* creating footer content elements */   
      var foot_content={
          button_wrapper :$("<div class='button_wrapper'></div>")[0],
          button_valid: $("<button class='action'>Validate</button>")[0],
          button_set_scores: $("<button class='action' style='background:black'>Manual Mode</button>")[0]   
      }
       
     /* Append the elements  */
     foot_content.button_wrapper.appendChild(foot_content.button_valid);
     foot_content.button_wrapper.appendChild(foot_content.button_set_scores);
     
     t.elt.footer.appendChild(foot_content.button_wrapper);
     
     /* Click on validate button */
      $(foot_content.button_valid).click(function(){
         
      /* getting applicants answer */
      t._getAnswers();
      
      
      /* getting results from applicants answers */
      service.json("selection","applicant/get_results",{applicants_exam:t.applicants_exam,subject:t.subject},function(res){
         
         
         /* updating t.applicants_exam object with computed scores from server side */
         for(var i=0;i<res.length;++i)
         {
            var applicant_exam=res[i];
            t.applicants_exam.applicants_answers[i].score=applicant_exam.score;
            for(var j=0;j<applicant_exam.parts.length;++j)
            {
               var part=applicant_exam.parts[j];
               t.applicants_exam.applicants_answers[i].parts[j].score=part.score;
                for(var k=0;k<part.answers.length;++k)
                {
                  var answer=part.answers[k];
                  t.applicants_exam.applicants_answers[i].parts[j].answers[k].score=answer.score;
                }
            }
         }
         
      /* Displaying results */
      t._scoreScreen(false);   
         
         });
       });
      
       /* Click on 'set scores' button */
      $(foot_content.button_set_scores).click(function(){
         
         t._scoreScreen(true);
         
      });
      
   }

   /* creating screen for displaying or entering scores
   * @param {boolean} editable
   */
   t._scoreScreen=function(editable){
      
      t.clearScreen();
      
      t._createResultsColumns(editable);
      
      /* Wait for all rows ready before filling with results */
      
         t.grid_res.onallrowsready(function(){
                 /* Filling with results */
                   t._fillResultsRows();
             });


      /* footer of scoreScreen */
        
      var foot_content={
          button_wrapper :$("<div class='button_wrapper'></div>")[0],
          button_edit_save: $("<button class='action'></button>")[0],
          button_back: $("<button class='action' style='background:black'>Back</button>")[0]   
      }
       
     /* Append the elements  */
    
    /* editable/save button */
     foot_content.button_wrapper.appendChild(foot_content.button_edit_save);
     if (editable)
      $(foot_content.button_edit_save).html("Save").css( "background", "red" );
     else
      $(foot_content.button_edit_save).html("Edit").css( "background", "" );
     
     /* click on edit/save button */
     $(foot_content.button_edit_save).click(function(){
      
         if ($(this).html()=="Save") {
            // sending scores to server
            //TODO (service to send scores )
            
            
            
            /* Go back to Edit mode */
           $(this).html("Edit").css( "background", "" );
         }
         else
            /* Go back to Save mode */
           $(this).html("Save").css( "background", "red" );
         

         /* toggle columns editable mode */
         t._toggleEditableColumns()
     }
      );
     
     /* back to answers screen button */
     if (!editable) {
       foot_content.button_wrapper.appendChild(foot_content.button_back);
       /* back button */
       $(foot_content.button_back).click(function(){
         
         t._answerScreen();
         
      });
       
     }
     
     
     t.elt.footer.appendChild(foot_content.button_wrapper);
     
     
      
   }
   
   /* clearing grid screen */
   t.clearScreen=function()
   {
      /* removing columns */
      t._removeColumns();
      
      /* remove footer button(s) */
      while (t.elt.footer.firstChild) 
        t.elt.footer.removeChild(t.elt.footer.firstChild);
      
   }
   
   
   /* Initialization */
   t._init();
}