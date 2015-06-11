<?php

/**
*
* Moodle API Library
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

/**
* Requires and includes
*/
if (file_exists($CFG->libdir.'/openlib.php')){
	require_once($CFG->libdir.'/openlib.php');
    //openmod lib by rick chaides
}
require_once($CFG->dirroot.'/calendar/lib.php');

/**
* Given an object containing all the necessary data,
* (defined by the form in mod.html) this function
* will create a new instance and return the id number
* of the new instance.
* @param object $project the form object from which create an instance 
* @return the new instance id
*/
function project_add_instance($project){
	global $DB,$USER;
	
	$project->timecreated = time();
	$project->timemodified = time();

	$context = context_module::instance($project->coursemodule);
	if($project->instance!=''){
		$projetid= $project->instance;
	}else{
		$projetid=0;
	}
	/*var_dump($context);
	var_dump($project);die();*/
	
	/*$draftitemid = $project->introimg;
    if ($draftitemid) {
        file_save_draft_area_files($draftitemid, $context->id, 'mod_project', 'introimg', 0, array('subdirs'=>true));
    }*/
    if ($project->projectconfidential==1) {
    	//role id has been hardcoded because the get_role_id was not finishing for whatever reasons
    	//role_change_permission(4, $context, 'moodle/course:viewhiddenactivities', CAP_PROHIBIT);
    	$roles = get_archetype_roles('teacher');
    	foreach ($roles as $value) {
    		role_change_permission($value->id, $context, 'moodle/course:viewhiddenactivities', CAP_PROHIBIT);
    		role_change_permission($value->id, $context, 'moodle/site:accessallgroups', CAP_PROHIBIT);
    		
    	}
    }
    if ($returnid = $DB->insert_record('project', $project)) {
    	$event = new StdClass;
    	$event->name        = get_string('projectstartevent','project', $project->name);
    	$event->description = $project->intro;
    	$event->courseid    = $project->course;
    	$event->groupid     = 0;
    	$event->userid      = 0;
    	$event->modulename  = 'project';
    	$event->instance    = $returnid;
    	$event->eventtype   = 'projectstart';
    	$event->timestart   = $project->projectstart;
    	$event->timeduration = 0;
    	calendar_event::create($event);
    	$event->name        = get_string('projectendevent','project', $project->name);
    	$event->eventtype   = 'projectend';
    	$event->timestart   = $project->projectend;
    	calendar_event::create($event);

    }	
    if (!empty($project->howtoworktype)) {
    	$worktypes = explode(';', $project->howtoworktype);
    	foreach ($worktypes as $worktype) {
    		$code = substr($worktype, 1,3);
    		$name = substr($worktype, 5);
    		//prepare the insert. blahblah is theorically unused but mut be not null so ...
    		$insert= array('projectid' => $returnid,'domain'=>'worktype','code'=>$code,'label'=>$name,'description'=>'blahblah');
    		$DB->insert_record('project_qualifier', $insert);
    	}
    }

	//gestion du champ introimg
    $introimgoptions = array('maxbytes' =>2000000, 'maxfiles'=> 1,'accepted_types' => array('.jpeg', '.jpg', '.png','return_types'=>FILE_INTERNAL));
    $project = file_postupdate_standard_filemanager($project, 'introimg', $introimgoptions, $context, 'mod_project', 'introimg', $returnid);

    return $returnid;
}

/**
* some consistency check over dates
* returns true if the dates are valid, false otherwise
* @param object $project a form object to be checked for dates
* @return true if dates are OK
*/
function project_check_dates($project) {
    // but enforce non-empty or non negative projet period.
	return ($project->projectstart < $project->projectend);           
}

/**
* Given an object containing all the necessary data, 
* (defined by the form in mod.html) this function 
* will update an existing instance with new data.
* @uses $CFG
* @param object $project the form object from which update an instance
*/
function project_update_instance($project){
	global $CFG, $DB;
	//var_dump($project);

	$project->timemodified = time();

	if (!project_check_dates($project)) {
		return get_string('invaliddates', 'project');
	}


	$project->id = $project->instance;
	//$draftitemid = $project->introimg;
    /* if ($draftitemid) {
    *		$context = context_module::instance($project->coursemodule);
    *      file_save_draft_area_files($draftitemid, $context->id, 'mod_project', 'introimg', 0, array('subdirs'=>true));
    }*/
    $context = context_module::instance($project->coursemodule);
    $introimgoptions = array('maxbytes' =>2000000, 'maxfiles'=> 1,'accepted_types' => array('.jpeg', '.jpg', '.png','return_types'=>FILE_INTERNAL));
    $project = file_postupdate_standard_filemanager($project, 'introimg', $introimgoptions, $context, 'mod_project', 'introimg', $project->id);

    if ($returnid = $DB->update_record('project', $project)) {
    	if (!empty($project->howtoworktype)) {
    		$worktypes = explode(';', $project->howtoworktype);
    		foreach ($worktypes as $worktype) {
    			$code = substr($worktype, 1,3);
    			$name = substr($worktype, 5);
    			//prepare the insert. blahblah is theorically unused but mut be not null so ...
    			$insert= array('projectid' => $returnid,'domain'=>'worktype','code'=>$code,'label'=>$name,'description'=>'blahblah');
    			$DB->insert_record('project_qualifier', $insert);
    		}
    	}
    	$dates = array(
    		'projectstart' => $project->projectstart,
    		'projectend' => $project->projectend,
    		'assessmentstart' => $project->assessmentstart
    		);
    	$moduleid = $DB->get_field('modules', 'id', array('name' => 'project'));
    	foreach ($dates as $type => $date) {
    		if ($event = $DB->get_record('event', array('modulename' => 'project', 'instance' => $project->id, 'eventtype' => $type))) {
    			$event->name        = get_string($type.'event','project', $project->name);
    			$event->description = $project->intro;
    			$event->eventtype   = $type;
    			$event->timestart   = $date;
    			update_event($event);
    		} 
    		else if ($date) {
    			$event = new StdClass;
    			$event->name        = get_string($type.'event','project', $project->name);
    			$event->description = $project->intro;
    			$event->courseid    = $project->course;
    			$event->groupid     = 0;
    			$event->userid      = 0;
    			$event->modulename  = 'project';
    			$event->instance    = $project->instance;
    			$event->eventtype   = $type;
    			$event->timestart   = $date;
    			$event->timeduration = 0;
    			$event->visible     = $DB->get_field('course_modules', 'visible', array('module' => $moduleid, 'instance' => $project->id)); 
    			calendar_event::create($event);
    		}
    	}
    }
    return $returnid;
}

/**
* Given an ID of an instance of this module,
* this function will permanently delete the instance
* and any data that depends on it.
* @param integer $id the instance id to delete
* @return true if successfully deleted
*/
function project_delete_instance($id){
	global $DB;
	
	if (! $project = $DB->get_record('project', array('id' => $id))) {
		return false;
	}

	$result = true;

	/* Delete any dependent records here */

	/* Delete subrecords here */
	$DB->delete_records('project_heading', array('projectid' => $project->id));
	$DB->delete_records('project_task', array('projectid' => $project->id));
	$DB->delete_records('project_messages', array('projectid' => $project->id));
	$DB->delete_records('project_specification', array('projectid' => $project->id));
	$DB->delete_records('project_requirement', array('projectid' => $project->id));
	$DB->delete_records('project_milestone', array('projectid' => $project->id));
	$DB->delete_records('project_deliverable', array('projectid' => $project->id));

    // echo "delete entities ok!!<br/>";

	$DB->delete_records('project_task_to_spec', array('projectid' => $project->id));
	$DB->delete_records('project_task_dependency', array('projectid' => $project->id));
	$DB->delete_records('project_task_to_deliv', array('projectid' => $project->id));
	$DB->delete_records('project_spec_to_req', array('projectid' => $project->id));

    // delete domain subrecords
	$DB->delete_records('project_qualifier', array('projectid' => $project->id));
	$DB->delete_records('project_assessment', array('projectid' => $project->id));
	$DB->delete_records('project_criterion', array('projectid' => $project->id));

	/* Delete any event associate with the project */
	$DB->delete_records('event', array('modulename' => 'project', 'instance' => $project->id));
	/* Delete the instance itself */
	if (! $DB->delete_records('project', array('id' => $project->id))) {
		$result = false;
	}

	echo "full delete : $result<br/>";
    // return $result;
	return true;
}

/**
* gives back an object for student detailed reports
* @param object $course the current course
* @param object $user the current user
* @param object $mod the current course module
* @param object $project the current project
*/
function project_user_complete($course, $user, $mod, $project){
	return NULL;
}

/**
* gives back an object for student abstract reports
* @uses $CFG
* @param object $course the current course
* @param object $user the current user
* @param object $mod the current course module
* @param object $project the current project
*/
function project_user_outline($course, $user, $mod, $project){
	global $CFG, $DB;

	if ($project = $DB->get_record('project', array('id' => $project->id))){
        // counting assigned tasks
		$assignedtasks = $DB->count_records('project_task', array('projectid' => $project->id, 'assignee' => $user->id));
		$select = "projectid = {$project->id} AND assignee = $user->id AND done < 100";
		$uncompletedtasks = $DB->count_records_select('project_task', $select);
		$ownedtasks = $DB->count_records('project_task', array('projectid' => $project->id, 'owner' => $user->id));
		$outline = new stdClass();
		$outline->info = get_string('haveownedtasks', 'project', $ownedtasks);
		$outline->info .= '<br/>'.get_string('haveassignedtasks', 'project', $assignedtasks);
		$outline->info .= '<br/>'.get_string('haveuncompletedtasks', 'project', $uncompletedtasks);

		$sql = "
		SELECT MAX(modified) as modified FROM {project_task} WHERE projectid = $project->id AND 
		(owner = $user->id OR assignee = $user->id) ";
		if ($lastrecord = $DB->get_record_sql($sql))
			$outline->time = $lastrecord->modified;
		else
			$outline->time = $project->timemodified;
		return $outline;
	}
	return NULL;
}

/**
 * Course resetting API
 * Called by course/reset.php
 * OLD OBSOLOETE WAY
 */
function project_reset_course_form($course) {
	echo get_string('resetproject', 'project'); 
	echo ':<br />';
	print_checkbox('reset_project_groups', 1, true, get_string('grouped','project'), '', '');  
	echo '<br />';
	print_checkbox('reset_project_group0', 1, true, get_string('groupless','project'), '', '');  
	echo '<br />';
	print_checkbox('reset_project_grades', 1, true, get_string('grades','project'), '', '');  
	echo '<br />';
	print_checkbox('reset_project_criteria', 1, true, get_string('criteria','project'), '', '');  
	echo '<br />';
	print_checkbox('reset_project_milestones', 1, true, get_string('milestones','project'), '', '');  
	echo '<br />';
	echo '</p>';
}

/**
 * Called by course/reset.php
 * @param $mform form passed by reference
 */
function project_reset_course_form_definition(&$mform) {
	global $COURSE, $DB;

	$mform->addElement('header', 'teachprojectheader', get_string('modulenameplural', 'project'));
	if(!$projects = $DB->get_records('project', array('course' => $COURSE->id))){
		return;
	}

	$mform->addElement('static', 'hint', get_string('resetproject','project'));
	$mform->addElement('checkbox', 'reset_project_grades', get_string('resetting_grades', 'project'));
	$mform->addElement('checkbox', 'reset_project_criteria', get_string('resetting_criteria', 'project'));
	$mform->addElement('checkbox', 'reset_project_groups', get_string('resetting_groupprojects', 'project'));
	$mform->addElement('checkbox', 'reset_project_group0', get_string('resetting_courseproject', 'project'));
}

/**
* This function is used by the remove_course_userdata function in moodlelib.
* If this function exists, remove_course_userdata will execute it.
* This function will remove all posts from the specified forum.
* @uses $CFG
* @param object $data the reset options
* @param boolean $showfeedback if true, ask the function to be verbose
*/
function project_reset_userdata($data) {
	global $CFG, $DB;

	$status = array();
	$componentstr = get_string('modulenameplural', 'magtest');
	$strreset = get_string('reset');
	if ($data->reset_project_grades or $data->reset_project_criteria or $data->reset_project_groups){
		$sql = "
		DELETE FROM
		{project_assessment} WHERE projectid IN ( SELECT c.id FROM {project} AS c WHERE c.course={$data->courseid} )";
		if ($DB->execute($sql)){
			$status[] = array('component' => $componentstr, 'item' => get_string('resetting_grades','project'), 'error' => false);
		}
	}

	if ($data->reset_project_criteria){
		$sql = " DELETE FROM {project_criterion} WHERE projectid IN ( SELECT c.id FROM {project} AS c WHERE c.course={$data->courseid} )";
		if($DB->execute($sql)){
			$status[] = array('component' => $componentstr, 'item' => get_string('resetting_criteria','project'), 'error' => false);
		}
	}

	if ($data->reset_project_groups){
		$subsql = " WHERE projectid IN ( SELECT c.id FROM {project} AS c WHERE c.course={$data->courseid} ) AND groupid != 0 ";

		$deletetables = array('spec_to_req', 
			'task_to_spec', 
			'task_to_deliv', 
			'task_dependency', 
			'requirement', 
			'specification', 
			'task', 
			'deliverable',
			'heading');

		if ($data->reset_project_milestones){
			$deletetables[] = 'milestone';
		}
		foreach($deletetables as $atable){
			$sql = "
			DELETE FROM
			{project_{$atable}}
			{$subsql}
			";
			$DB->execute($sql);
		}        

		$status[] = array('component' => $componentstr, 'item' => get_string('resetting_groupprojects','project'), 'error' => false);
	}

	if ($data->reset_project_group0){
		$subsql = "WHERE projectid IN ( SELECT c.id FROM {project} AS c WHERE c.course={$data->courseid} ) AND groupid = 0 ";

		$deletetables = array('spec_to_req', 
			'task_to_spec', 
			'task_to_deliv', 
			'task_dependency', 
			'requirement', 
			'specification', 
			'task', 
			'deliverable',
			'heading');

		if ($data->reset_project_milestones){
			$deletetables[] = 'milestone';
		}
		foreach($deletetables as $atable){
			$sql = "
			DELETE FROM
			{project_{$atable}}
			{$subsql}
			";
			$DB->execute($sql);
		}
		$status[] = array('component' => $componentstr, 'item' => get_string('resetting_courseproject','project'), 'error' => false);
	}
	return $status;
}


/**
* performs what needs to be done in asynchronous mode
*/
function project_cron(){
    // TODO : may cleanup some old group rubish ??

}

/**
* get the "grade" entries for this user and add the first and last names (of project owner, 
* better to get name of teacher...
* ...but not available in assessment record...)
* @param object $course the current course
* @param int $timestart the time from which to log
*/
function project_get_grade_logs($course, $timestart) {
	global $CFG, $USER, $DB;

	if (empty($USER->id)) {
		return false;
	}
    // TODO evaluate grading and assessment strategies
	return;
	$timethen = time() - $CFG->maxeditingtime;
	$query = "
	SELECT 
	l.time, 
	l.url, 
	u.firstname, 
	u.lastname, 
	a.projectid, 
	e.name
	FROM 
	{log} l,
	{project} e, 
	{project_assessments} a, 
	{user} u
	WHERE
	l.time > $timestart AND 
	l.time < $timethen AND 
	l.course = $course->id AND 
	l.module = 'project' AND 
	l.action = 'grade' AND 
	a.id = l.info AND 
	e.id = a.projectid AND 
	a.userid = $USER->id AND 
	u.id = e.userid AND 
	e.id = a.projectid
	";
	return $DB->get_records_sql($query);
}

/**
* get the log entries by a particular change in entities, 
* @uses $CFG
* @param object $course the current course
* @param int $timestart the time from which to log
* @param string $changekey the key of the event type to be considered
*/
function project_get_entitychange_logs($course, $timestart, $changekey) {
	global $CFG, $DB;

	$timethen = time() - $CFG->maxeditingtime;
	$query = "
	SELECT 
	l.time, 
	l.url, 
	u.firstname, 
	u.lastname, 
	l.info as projectid, 
	p.name
	FROM 
	{log} l,
	{project} p, 
	{user} u
	WHERE 
	l.time > $timestart AND 
	l.time < $timethen AND 
	l.course = $course->id AND 
	l.module = 'project' AND 
	l.action = '$changekey' AND 
	p.id = l.info AND 
	u.id = l.userid
	";
	return $DB->get_records_sql($query);
}

/**
* get the "submit" entries and add the first and last names...
* @uses $CFG
* @param object $course
* @param int $timestart
*/
function project_get_submit_logs($course, $timestart) {
	global $CFG, $DB;

	$timethen = time() - $CFG->maxeditingtime;
	$query = "
	SELECT 
	l.time, 
	l.url, 
	u.firstname, 
	u.lastname, 
	l.info as projectid, 
	e.name
	FROM 
	{log} l,
	{project} e, 
	{user} u
	WHERE 
	l.time > $timestart AND 
	l.time < $timethen AND 
	l.course = $course->id AND 
	l.module = 'project' AND 
	l.action = 'submit' AND 
	e.id = l.info AND 
	u.id = l.userid
	";
	return $DB->get_records_sql($query);
}

/**
* Given a list of logs, assumed to be those since the last login
* this function prints a short list of changes related to this module
* If isteacher is true then perhaps additional information is printed.
* This function is called from course/lib.php: print_recent_activity()
* @uses $CFG
* @param object $course
* @param boolean $isteacher
* @param int $timestart
*/
function project_print_recent_activity($course, $isteacher, $timestart){
	global $CFG;

    // have a look for what has changed in requ
	$changerequcontent = false;
    if (!$isteacher) { // teachers only need to see project
    	if ($logs = project_get_entitychange_logs($course, $timestart, 'changerequ')) {
            // got some, see if any belong to a visible module
    		foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
    			$tempmod = new StdClass;
    			$tempmod->course = $course->id;
    			$tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
    			if (instance_is_visible('project',$tempmod)) {
    				$changerequcontent = true;
    				break;
    			}
    		}
            // if we got some "live" ones then output them
    		if ($changerequcontent) {
    			print_headline(get_string('projectchangedrequ', 'project').":");
    			foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
    				$tempmod = new StdClass;
    				$tempmod->course = $course->id;
    				$tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
    				if (instance_is_visible('project',$tempmod)) {
                        if (!has_capability('mod/project:gradeproject', $context, $log->userid)) {  // don't break anonymous rule
                        $log->firstname = $course->student;
                        $log->lastname = '';
                    }
                    print_recent_activity_note($log->time, $log, $isteacher, $log->name,$CFG->wwwroot.'/mod/project/'.$log->url);
                }
            }
        }
    }
}

   // have a look for what has changed in specs
$changespeccontent = false;
    if (!$isteacher) { // teachers only need to see project
    	if ($logs = project_get_entitychange_logs($course, $timestart, 'changespec')) {
            // got some, see if any belong to a visible module
    		foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
    			$tempmod->course = $course->id;
    			$tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
    			if (instance_is_visible('project',$tempmod)) {
    				$changespeccontent = true;
    				break;
    			}
    		}
            // if we got some "live" ones then output them
    		if ($changespeccontent) {
    			print_headline(get_string('projectchangedspec', 'project').":");
    			foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
    				$tempmod->course = $course->id;
    				$tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
    				if (instance_is_visible('project',$tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {  // don't break anonymous rule
                        $log->firstname = $course->student;
                        $log->lastname = '';
                    }
                    print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                    	$CFG->wwwroot.'/mod/project/'.$log->url);
                }
            }
        }
    }
}

    // have a look for what has changed in tasks
$changetaskcontent = false;
    if (!$isteacher) { // teachers only need to see project
    	if ($logs = project_get_entitychange_logs($course, $timestart, 'changetask')) {
            // got some, see if any belong to a visible module
    		foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
    			$tempmod->course = $course->id;
    			$tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
    			if (instance_is_visible('project',$tempmod)) {
    				$changetaskcontent = true;
    				break;
    			}
    		}
            // if we got some "live" ones then output them
    		if ($changetaskcontent) {
    			print_headline(get_string('projectchangedtask', 'project').":");
    			foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
    				$tempmod->course = $course->id;
    				$tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
    				if (instance_is_visible('project',$tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {  // don't break anonymous rule
                        $log->firstname = $course->student;
                        $log->lastname = '';
                    }
                    print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                    	$CFG->wwwroot.'/mod/project/'.$log->url);
                }
            }
        }
    }
}

    // have a look for what has changed in milestones
$changemilescontent = false;
    if (!$isteacher) { // teachers only need to see project
    	if ($logs = project_get_entitychange_logs($course, $timestart, 'changemilestone')) {
            // got some, see if any belong to a visible module
    		foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
    			$tempmod->course = $course->id;
    			$tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
    			if (instance_is_visible('project',$tempmod)) {
    				$changemilescontent = true;
    				break;
    			}
    		}
            // if we got some "live" ones then output them
    		if ($changemilescontent) {
    			print_headline(get_string('projectchangedmilestone', 'project').":");
    			foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
    				$tempmod->course = $course->id;
    				$tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
    				if (instance_is_visible('project',$tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {  // don't break anonymous rule
                        $log->firstname = $course->student;
                        $log->lastname = '';
                    }
                    print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                    	$CFG->wwwroot.'/mod/project/'.$log->url);
                }
            }
        }
    }
}
    // have a look for what has changed in deliverables
$changedelivcontent = false;
    if (!$isteacher) { // teachers only need to see project
    	if ($logs = project_get_entitychange_logs($course, $timestart, 'changedeliverable')) {
            // got some, see if any belong to a visible module
    		foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
    			$tempmod->course = $course->id;
    			$tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
    			if (instance_is_visible('project',$tempmod)) {
    				$changedelivcontent = true;
    				break;
    			}
    		}
            // if we got some "live" ones then output them
    		if ($changedelivcontent) {
    			print_headline(get_string('projectchangeddeliverable', 'project').":");
    			foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
    				$tempmod->course = $course->id;
    				$tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
    				if (instance_is_visible('project',$tempmod)) {
                        if (!isteacher($course->id, $log->userid)) {  // don't break anonymous rule
                        $log->firstname = $course->student;
                        $log->lastname = '';
                    }
                    print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                    	$CFG->wwwroot.'/mod/project/'.$log->url);
                }
            }
        }
    }
}

    // have a look for new gradings for this user (grade)
$gradecontent = false;
if ($logs = project_get_grade_logs($course, $timestart)) {
        // got some, see if any belong to a visible module
	foreach ($logs as $log) {
            // Create a temp valid module structure (only need courseid, moduleid)
		$tempmod->course = $course->id;
		$tempmod->id = $log->projectid;
            //Obtain the visible property from the instance
		if (instance_is_visible('project',$tempmod)) {
			$gradecontent = true;
			break;
		}
	}
        // if we got some "live" ones then output them
	if ($gradecontent) {
		print_headline(get_string('projectfeedback', 'project').":");
		foreach ($logs as $log) {
                //Create a temp valid module structure (only need courseid, moduleid)
			$tempmod->course = $course->id;
			$tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
			if (instance_is_visible('project',$tempmod)) {
                    $log->firstname = $course->teacher;    // Keep anonymous
                    $log->lastname = '';
                    print_recent_activity_note($log->time, $log, $isteacher, $log->name,
                    	$CFG->wwwroot.'/mod/project/'.$log->url);
                }
            }
        }
    }

    // have a look for new project (only show to teachers) (submit)
    $submitcontent = false;
    if ($isteacher) {
    	if ($logs = project_get_submit_logs($course, $timestart)) {
            // got some, see if any belong to a visible module
    		foreach ($logs as $log) {
                // Create a temp valid module structure (only need courseid, moduleid)
    			$tempmod->course = $course->id;
    			$tempmod->id = $log->projectid;
                //Obtain the visible property from the instance
    			if (instance_is_visible('project',$tempmod)) {
    				$submitcontent = true;
    				break;
    			}
    		}
            // if we got some "live" ones then output them
    		if ($submitcontent) {
    			print_headline(get_string('projectproject', 'project').":");
    			foreach ($logs as $log) {
                    //Create a temp valid module structure (only need courseid, moduleid)
    				$tempmod->course = $course->id;
    				$tempmod->id = $log->projectid;
                    //Obtain the visible property from the instance
    				if (instance_is_visible('project',$tempmod)) {
    					print_recent_activity_note($log->time, $log, $isteacher, $log->name,
    						$CFG->wwwroot.'/mod/project/'.$log->url);
    				}
    			}
    		}
    	}
    }
    return $changerequcontent or $changespeccontent or $changetaskcontent or $changemilescontent or $changedelivcontent or $gradecontent or $submitcontent;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user. It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $newmoduleid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function project_grades($cmid) {
	global $CFG, $DB;

	if (!$module = $DB->get_record('course_modules', array('id' => $cmid))){
		return NULL;
	}    

	if (!$project = $DB->get_record('project', array('id' => $module->instance))){
		return NULL;
	}

    if ($project->grade == 0) { // No grading
    	return NULL;
    }

    $query = "
    SELECT
    a.*,
    c.weight
    FROM
    {project_assessment} as a
    LEFT JOIN
    {project_criterion} as c
    ON
    a.criterion = c.id
    WHERE
    a.projectid = {$project->id}
    ";
    // echo $query ;
    $grades = $DB->get_records_sql($query);
    if ($grades){
        if ($project->grade > 0 ){ // Grading numerically
        	$finalgrades = array();
        	foreach($grades as $aGrade){
        		$finalgrades[$aGrade->userid] = @$finalgrades[$aGrade->userid] + $aGrade->grade * $aGrade->weight;
        		$totalweights[$aGrade->userid] = @$totalweights[$aGrade->userid] + $aGrade->weight;
        	}
        	foreach(array_keys($finalgrades) as $aUserId){
        		if($totalweights[$aGrade->userid] != 0){
        			$final[$aUserId] = round($finalgrades[$aUserId] / $totalweights[$aGrade->userid]);
        		}
        		else{
        			$final[$aUserId] = 0;
        		}
        	}
        	$return->grades = @$final;
        	$return->maxgrade = $project->grade;
        } else { // Scales
        	$finalgrades = array();
        	$scaleid = - ($project->grade);
        	$maxgrade = '';
        	if ($scale = $DB->get_record('scale', array('id' => $scaleid))) {
        		$scalegrades = make_menu_from_list($scale->scale);
        		foreach ($grades as $aGrade) {
        			$finalgrades[$userid] = @$finalgrades[$userid] + $scalegrades[$aGgrade->grade] * $aGrade->weight;
        			$totalweights[$aGrade->userid] = @$totalweights[$aGrade->userid] + $aGrade->weight;
        		}
        		$maxgrade = $scale->name;

        		foreach(array_keys($finalgrades) as $aUserId){
        			if($totalweights[$aGrade->userid] != 0){
        				$final[$userId] = round($finalgrades[$aUserId] / $totalweights[$aGrade->userid]);
        			} else {
        				$final[$userId] = 0;
        			}
        		}
        	}
        	$return->grades = @$final;
        	$return->maxgrade = $maxgrade;
        }
        return $return;
    }
    return NULL;
}


/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of project. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $moduleid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function project_get_participants($moduleid) {
	global $DB;

	$usersreqs = $DB->get_records('project_requirement', array('projectid' => $moduleid), '', 'userid,userid');
	$usersspecs = $DB->get_records('project_specification', array('projectid' => $moduleid), '', 'userid,userid');
	$userstasks = $DB->get_records('project_task', array('projectid' => $moduleid), '', 'userid,userid');
	$userstasksassigned = $DB->get_records('project_task', array('projectid' => $moduleid), '', 'assignee,assignee');
	$userstasksowners = $DB->get_records('project_task', array('projectid' => $moduleid), '', 'owner,owner');
	$usersdelivs = $DB->get_records('project_deliverable', array('projectid' => $moduleid), '', 'userid,userid');
	$usersmiles = $DB->get_records('project_milestone', array('projectid' => $moduleid), '', 'userid,userid');

	$allusers = array();    
	if(!empty($usersreqs)){
		$allusers = array_keys($usersreqs);
	}
	if(!empty($usersspecs)){
		$allusers = array_merge($allusers, array_keys($usersspecs));
	}
	if(!empty($userstasks)){
		$allusers = array_merge($allusers, array_keys($userstasks));
	}
	if(!empty($userstasksassigned)){
		$allusers = array_merge($allusers, array_keys($userstasksassigned));
	}
	if(!empty($userstasksowned)){
		$allusers = array_merge($allusers, array_keys($userstasksowned));
	}
	if(!empty($userstasksdelivs)){
		$allusers = array_merge($allusers, array_keys($userstasksdelivs));
	}
	if(!empty($userstasksmiles)){
		$allusers = array_merge($allusers, array_keys($userstasksmiles));
	}
	$userlist = implode("','", $allusers);
	$participants = $DB->get_records_list('user', array('id' => "'$userlist'"));
	return $participants;
}

/**
 * This function returns if a scale is being used by one newmodule
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $newmoduleid ID of an instance of this module
 * @return mixed
 **/
function project_scale_used($cmid, $scaleid) {
	global $DB;

	$return = false;

    // note : scales are assigned using negative index in the grade field of project (see mod/assignement/lib.php) 
	$rec = $DB->get_record('project', array('id' => $cmid, 'grade' => -$scaleid));

	if (!empty($rec) && !empty($scaleid)) {
		$return = true;
	}
	return $return;
}

/**
 * Serves the project attachments. Implements needed access control ;-)
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function project_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
	global $CFG, $DB, $USER;

	if ($context->contextlevel != CONTEXT_MODULE) {
		return false;
	}

	require_course_login($course, true, $cm);

	$fileareas = array('deliverablearchive','deliverablelocalfile','introimg','requirementdescription', 'specificationdescription', 'milestonedescription', 'taskdescription', 'deliverabledescription', 'abstract', 'rationale', 'environment');
	$areastotables = array('deliverablearchive'=>'project_milestone','deliverablelocalfile'=>'project_deliverable','introimg'=>'project','requirementdescription' => 'project_requirement', 'specificationdescription' => 'project_specifciation', 'milestonedescription' => 'project_milestone', 'taskdescription' => 'project_task', 'deliverabledescription' => 'project_deliverable', 'abstract' => 'project_heading', 'rationale' => 'project_heading', 'environment' => 'project_heading');
	if (!in_array($filearea, $fileareas)) {
		return false;
	}
	
	$relatedtable = $areastotables[$filearea];

	$entryid = (int)array_shift($args);

	if (!$project = $DB->get_record('project', array('id' => $cm->instance))) {
		return false;
	}

	$fs = get_file_storage();
	$relativepath = implode('/', $args);
	$fullpath = "/$context->id/mod_project/$filearea/$entryid/$relativepath";
	if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
		return false;
	}
	
	$entry = $DB->get_record($relatedtable, array('id' => $entryid));
    // Make sure groups allow this user to see this file
	if($entry){
		if(isset($entry->groupid)){
			if ($entry->groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {   // Groups are being used
			   if (!groups_group_exists($entry->groupid)) { // Can't find group
					return false;                           // Be safe and don't send it to anyone
				}

				if (!groups_is_member($entry->groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
					// do not send posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS
					return false;
				}
			}
		}
	}
	if ((!isloggedin() || isguestuser()) && !$project->guestsallowed){
		return false;
	}

    // finally send the file
    send_stored_file($file, 0, 0, true); // download MUST be forced - security!
}
function project_supports($feature) {
	switch($feature) {
		case FEATURE_BACKUP_MOODLE2:          return true;
		default: return null;
	}
}
