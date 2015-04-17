<?php

require_once($CFG->libdir.'/formslib.php');

class Task_Form extends moodleform {

	var $mode;
	var $project;
	var $current;
	var $descriptionoptions;

	function __construct($action, &$project, $mode, $taskid){
		global $DB;
		
		$this->mode = $mode;
		$this->project = $project;
		if ($taskid){
			$this->current = $DB->get_record('project_task', array('id' => $taskid));
		}

		parent::__construct($action);
	}
    	
	function definition(){
		global $COURSE, $DB, $USER, $OUTPUT;

    	$mform = $this->_form;
    	
    	$currentGroup = 0 + groups_get_course_group($COURSE);

    	$modcontext = context_module::instance($this->project->cmid);

		$maxfiles = 99;                // TODO: add some setting
		$maxbytes = $COURSE->maxbytes; // TODO: add some setting	
		$this->descriptionoptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $modcontext);
    	
    	$mform->addElement('hidden', 'id');
    	$mform->addElement('hidden', 'fatherid');
    	$mform->addElement('hidden', 'taskid');
    	$mform->addElement('hidden', 'work');
    	$mform->setDefault('work', $this->mode);
    	
    	$mform->addElement('text', 'abstract', get_string('tasktitle', 'project'), array('size' => "100%"));

    	$mform->addElement('editor', 'description_editor', get_string('description', 'project'), null, $this->descriptionoptions);		    	

        $milestones = $DB->get_records_select('project_milestone', "projectid = ? AND groupid = ? ", array($this->project->id, $currentGroup), 'ordering ASC', 'id, abstract, ordering');
        $milestonesoptions = array();
        $milestonesoptions[0] = get_string('nomilestone', 'project');
        if ($milestones){
            foreach($milestones as $aMilestone){
                $milestonesoptions[$aMilestone->id] = $aMilestone->abstract;
            }
        }
    	$mform->addElement('select', 'milestoneid', get_string('milestone', 'project'), $milestonesoptions);
		$mform->addHelpButton('milestone', 'task_to_miles', 'project');

        $ownerstr = $USER->lastname . " " . $USER->firstname . " ";
        $ownerstr .= $OUTPUT->user_picture($USER); 

		$mform->addElement('static', 'owner_st', get_string('owner', 'project'), $ownerstr);
		$mform->addElement('hidden', 'owner');
		$mform->setDefault('owner', $USER->id);

        $assignees = project_get_group_users($this->project->course, $this->project->cm, $currentGroup);
        if($assignees){
            $assignoptions = array();
            foreach($assignees as $anAssignee){
                $assignoptions[$anAssignee->id] = $anAssignee->lastname . ' ' . $anAssignee->firstname;
            }
	    	$mform->addElement('select', 'assignee', get_string('assignee', 'project'), $assignoptions);
			// $mform->addHelpButton('assignee', 'assignee', 'project');
        } else {
	    	$mform->addElement('static', 'assignee', get_string('assignee', 'project'), get_string('noassignees', 'project'));
        }

    	$mform->addElement('date_time_selector', 'taskstart', get_string('from'), array('optional' => true));
    	$mform->addElement('date_time_selector', 'taskend', get_string('to'), array('optional' => true));

        $worktypes = project_get_options('worktype', $this->project->id);
        $worktypeoptions = array();
        foreach($worktypes as $aWorktype){
            $worktypeoptions[$aWorktype->code] = '['. $aWorktype->code . '] ' . $aWorktype->label;
        }
    	$mform->addElement('select', 'worktype', get_string('worktype', 'project'), $worktypeoptions);
		$mform->addHelpButton('worktype', 'worktype', 'project');

        $statusses = project_get_options('taskstatus', $this->project->id);
        $statussesoptions = array();
        foreach($statusses as $aStatus){
            $statussesoptions[$aStatus->code] = '['. $aStatus->code . '] ' . $aStatus->label;
        }
    	$mform->addElement('select', 'status', get_string('status', 'project'), $statussesoptions);
		$mform->addHelpButton('status', 'status', 'project');

    	$mform->addElement('text', 'costrate', get_string('costrate', 'project'), array('size' => 6, 'onchange' => " task_update('quoted');task_update('spent') "));
    	$mform->addElement('text', 'planned', get_string('planned', 'project'), array('size' => 6, 'onchange' => " task_update('quoted') "));

    	$mform->addElement('static', 'quoted', get_string('quoted', 'project'), "<span id=\"quoted\">".@$this->current->quoted."</span> ".$this->project->costunit);
        $mform->addHelpButton('quoted', 'quoted', 'project'); 

		if (@$this->project->useriskcorrection){
	        $risks = project_get_options('risk', $this->project->id);
	        $risksesoptions = array();
	        foreach($risks as $aRisk){
	            $risksoptions[$aRisk->code] = '['. $aRisk->code . '] ' . $aRisk->label;
	        }
	    	$mform->addElement('select', 'risk', get_string('risk', 'project'), $risksoptions);
	    }

    	$mform->addElement('text', 'done', get_string('done', 'project'), array('size' => 6));
    	$mform->addElement('text', 'used', get_string('used', 'project'), array('size' => 6, 'onchange' => " task_update('spent') "));
    	$mform->addElement('static', 'spent', get_string('spent', 'project'), "<span id=\"spent\">".@$this->current->spent."</span> ".$this->project->costunit);
        // $mform->addHelpButton('spent', 'spent', 'project'); 

        $tasks = project_get_tree_options('project_task', $this->project->id, $currentGroup);
        $selection = $DB->get_records_select_menu('project_task_dependency', "slave = ? ", array(@$this->current->id), 'master,slave');
        $uptasksoptions = array();
        foreach($tasks as $aTask){
            $aTask->abstract = format_string($aTask->abstract);
            if ($aTask->id == $this->current->id) continue;
            $parentid = $DB->get_field('project_task', 'fatherid', array('id' => $this->current->id));
            if ($aTask->id == $parentid) continue;
            if (project_check_task_circularity($this->current->id, $aTask->id)) continue;
            $uptasksoptions[$aTask->id] = $aTask->ordering.' - '.shorten_text($aTask->abstract, 90);
        }
    	$select = &$mform->addElement('select', 'taskdependency', get_string('taskdependency', 'project'), $uptasksoptions, array('size' => 8));
    	$select->setMultiple(true);

		if ($this->project->projectusesspecs && $this->mode == 'update'){
	        $specifications = project_get_tree_options('project_specification', $this->project->id, $currentGroup);
	        $selection = $DB->get_records_select_menu('project_task_to_spec', "taskid = ? ", array($this->current->id), 'specid, taskid');
	        $specs = array();
	        if (!empty($specifications)){
	            foreach($specifications as $aSpecification){
	                $specs[$aSpecification->id] = $aSpecification->ordering .' - '.shorten_text(format_string($aSpecification->abstract), 90);
	            }
	        }
			$select = &$mform->addElement('select', 'tasktospec', get_string('tasktospec', 'project'), $specs, array('size' => 8));
			$select->setMultiple(true);
			$mform->addHelpButton('tasktospec', 'task_to_spec', 'project');
		}

		if ($this->project->projectusesdelivs && $this->mode == 'update'){
	        $deliverables = project_get_tree_options('project_deliverable', $this->project->id, $currentGroup);
	        $selection = $DB->get_records_select_menu('project_task_to_deliv', "taskid = ? ", array($this->current->id), 'delivid, taskid');
	        $delivs = array();
	        if (!empty($deliverables)){
	            foreach($deliverables as $aDeliverable){
	                $delivs[$aDeliverable->id] = $aDeliverable->ordering .' - '.shorten_text(format_string($aDeliverable->abstract), 90);
	            }
	        }
			$select = &$mform->addElement('select', 'tasktodeliv', get_string('tasktodeliv', 'project'), $delivs, array('size' => 8));
			$select->setMultiple(true);
			$mform->addHelpButton('tasktodeliv', 'task_to_deliv', 'project');
		}
		
		$this->add_action_buttons(true);
    }

    function set_data($defaults){

		$context = context_module::instance($this->project->cmid);

		$draftid_editor = file_get_submitted_draft_itemid('description_editor');
		$currenttext = file_prepare_draft_area($draftid_editor, $context->id, 'mod_project', 'description_editor', $defaults->id, array('subdirs' => true), $defaults->description);
		$defaults = file_prepare_standard_editor($defaults, 'description', $this->descriptionoptions, $context, 'mod_project', 'taskdescription', $defaults->id);
		$defaults->description = array('text' => $currenttext, 'format' => $defaults->descriptionformat, 'itemid' => $draftid_editor);

    	parent::set_data($defaults);
    }
}
