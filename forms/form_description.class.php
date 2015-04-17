<?php

require_once($CFG->libdir.'/formslib.php');

class Description_Form extends moodleform {

	var $project;
	var $mode;
	var $editoroptions;

	function __construct($action, &$project, $mode){
		$this->project = $project;
		$this->mode = $mode;
		parent::__construct($action);
	}
    	
	function definition(){
		global $COURSE;

    	$mform = $this->_form;

    	$modcontext = context_module::instance($this->project->cmid);

		$maxfiles = 99;                // TODO: add some setting
		$maxbytes = $COURSE->maxbytes; // TODO: add some setting	
		$this->editoroptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $modcontext);
    	
    	$mform->addElement('hidden', 'id'); // cmid
    	$mform->addElement('hidden', 'headingid');
    	$mform->addElement('hidden', 'work');
    	$mform->setDefault('work', $this->mode);
    	
    	$mform->addElement('text', 'title', get_string('projecttitle', 'project'), array('size' => "100%"));

    	$mform->addElement('editor', 'abstract_editor', get_string('abstract', 'project'), null,  $this->editoroptions);		    	
		$mform->setType('abstract_editor', PARAM_RAW);

    	$mform->addElement('editor', 'rationale_editor', get_string('rationale', 'project'), null,  $this->editoroptions);		    	
		$mform->setType('rationale_editor', PARAM_RAW);

    	$mform->addElement('editor', 'environment_editor', get_string('environment', 'project'), null,  $this->editoroptions);		    	
		$mform->setType('environment_editor', PARAM_RAW);

    	$mform->addElement('text', 'organisation', get_string('organisation', 'project'), array('size' => "100%"));

    	$mform->addElement('text', 'department', get_string('department', 'project'), array('size' => "100%"));

		$this->add_action_buttons(true);
    }

    function set_data($defaults){

		$context = context_module::instance($this->project->cmid);

		$abstract_draftid_editor = file_get_submitted_draft_itemid('abstract_editor');
		$currenttext = file_prepare_draft_area($abstract_draftid_editor, $context->id, 'mod_project', 'abstract_editor', $defaults->id, array('subdirs' => true), $defaults->abstract);
		$defaults = file_prepare_standard_editor($defaults, 'abstract', $this->editoroptions, $context, 'mod_project', 'abstract', $defaults->id);
		$defaults->abstract = array('text' => $currenttext, 'format' => $defaults->format, 'itemid' => $abstract_draftid_editor);

		$rationale_draftid_editor = file_get_submitted_draft_itemid('rationale_editor');
		$currenttext = file_prepare_draft_area($rationale_draftid_editor, $context->id, 'mod_project', 'rationale_editor', $defaults->id, array('subdirs' => true), $defaults->rationale);
		$defaults = file_prepare_standard_editor($defaults, 'rationale', $this->editoroptions, $context, 'mod_project', 'rationale', $defaults->id);
		$defaults->rationale = array('text' => $currenttext, 'format' => $defaults->format, 'itemid' => $rationale_draftid_editor);

		$environment_draftid_editor = file_get_submitted_draft_itemid('environment_editor');
		$currenttext = file_prepare_draft_area($environment_draftid_editor, $context->id, 'mod_project', 'environment_editor', $defaults->id, array('subdirs' => true), $defaults->environment);
		$defaults = file_prepare_standard_editor($defaults, 'environment', $this->editoroptions, $context, 'mod_project', 'environment', $defaults->id);
		$defaults->environment = array('text' => $currenttext, 'format' => $defaults->format, 'itemid' => $environment_draftid_editor);

    	parent::set_data($defaults);
    }
}
