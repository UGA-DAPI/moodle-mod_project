<?php

    /**
    *
    * Ajax receptor for updating collapse status.
    * when Moodle enables ajax, will also, when expanding, return all the underlying div structure
    *
	*
	* @package mod-project
	* @category mod
	* @author Yann Ducruy (yann[dot]ducruy[at]gmail[dot]com). Contact me if needed
	* @date 12/06/2015
	* @version 3.2
	* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
	*
	*/


    include "../../../config.php";
    require_once $CFG->dirroot."/mod/project/locallib.php";

    $id = required_param('id', PARAM_INT);   // module id
    $entity = required_param('entity', PARAM_ALPHA);   // module id
    $entryid = required_param('entryid', PARAM_INT);   // module id
    $state = required_param('state', PARAM_INT);   // module id

    // get some useful stuff...
    if (! $cm = get_coursemodule_from_id('project', $id)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
    if (! $project = $DB->get_record('project', array('id' => $cm->instance))) {
        print_error('invalidprojectid', 'project');
    }
    
    $group = 0 + groups_get_course_group($course, true);

    require_login($course->id, false, $cm);
    $context = context_module::instance($cm->id);
    if ($state){
        $collapse->userid = $USER->id;
        $collapse->projectid = $project->id;
        $collapse->entryid = $entryid;
        $collapse->entity = $entity;
        $collapse->collapsed = 1;
        $DB->insert_record('project_collapse', $collapse);

		// prepare for hidden branch / may not bne usefull
		/*
	    if ($CFG->enableajax && $CFG->enablecourseajax){
	    	$printfuncname = "project_print_{$entity}s";
	    	$propagated->collapsed = true;
	    	$printfuncname($project, $group, $entryid, $cm->id, $propagated);
	    }
	    */

    } else {
        $DB->delete_records('project_collapse', array('userid' => $USER->id, 'entryid' => $entryid, 'entity' => $entity));

		// prepare for showing branch
	    if ($CFG->enableajax && $CFG->enablecourseajax){
	    	$printfuncname = "project_print_{$entity}";
	    	$printfuncname($project, $group, $entryid, $cm->id);
	    }

    }

?>