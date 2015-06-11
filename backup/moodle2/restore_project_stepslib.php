<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
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
 * Define all the restore steps that will be used by the restore_url_activity_task
 */

/**
 * Structure step to restore one project activity
 */
class restore_project_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $project = new restore_path_element('project', '/activity/project');
        $paths[] = $project;
        
        
        $paths[] = new restore_path_element('project_globalqualifiers', '/activity/project/globalqualifiers/globalqualifier');
        $paths[] = new restore_path_element('project_qualifiers', '/activity/project/qualifiers/qualifier');
        $paths[] = new restore_path_element('project_criterion', '/activity/project/criteria/criterion');
        $paths[] = new restore_path_element('project_requirement', '/activity/project/requirements/requirement');
        $paths[] = new restore_path_element('project_specification', '/activity/project/specifications/specification');
        $paths[] = new restore_path_element('project_milestone', '/activity/project/milestones/milestone');
        $paths[] = new restore_path_element('project_assessment', '/activity/project/assessments/assessment');
        $paths[] = new restore_path_element('project_spectoreq', '/activity/project/spectoreqs/spectoreq');
        $paths[] = new restore_path_element('project_deliverable', '/activity/project/deliverables/deliverable');
        $paths[] = new restore_path_element('project_validationsession', '/activity/project/validationsessions/validationsession');
        $paths[] = new restore_path_element('project_validationresult', '/activity/project/validationsessions/validationsession/validationresults/validationresult');
        
        //like in backup, we'll only restore those elements if they come from a regular backup, and not the duplication method
        if ($userinfo){
            $paths[] = new restore_path_element('project_task', '/activity/project/tasks/task');
            $paths[] = new restore_path_element('project_tasktodeliv', '/activity/project/tasktodelivs/tasktodeliv');
            $paths[] = new restore_path_element('project_tasktospec', '/activity/project/tasktospecs/tasktospec');
            $paths[] = new restore_path_element('project_taskdependency', '/activity/project/taskdeps/taskdep');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_project($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->projectstart = $this->apply_date_offset($data->projectstart);
        $data->assessmentstart = $this->apply_date_offset($data->assessmentstart);
        $data->projectend = $this->apply_date_offset($data->projectend);

        // insert the label record
        $newitemid = $DB->insert_record('project', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_project_requirement($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('project');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->lastuserid = $this->get_mappingid('user', $data->lastuserid);
        $data->modified = $this->apply_date_offset($data->modified);
        $data->created = $this->apply_date_offset($data->created);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('project_requirement', $data);
        $this->set_mapping('project_requirement', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_project_specification($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('project');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->lastuserid = $this->get_mappingid('user', $data->lastuserid);
        $data->modified = $this->apply_date_offset($data->modified);
        $data->created = $this->apply_date_offset($data->created);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('project_specification', $data);
        $this->set_mapping('project_specification', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_project_task($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('project');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->lastuserid = $this->get_mappingid('user', $data->lastuserid);
        $data->assignee = $this->get_mappingid('user', $data->assignee);
        $data->modified = $this->apply_date_offset($data->modified);
        $data->created = $this->apply_date_offset($data->created);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('project_task', $data);
        $this->set_mapping('project_task', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_project_milestone($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('project');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->lastuserid = $this->get_mappingid('user', $data->lastuserid);
        $data->modified = $this->apply_date_offset($data->modified);
        $data->created = $this->apply_date_offset($data->created);
        if ($data->deadlineenable){
            $data->deadline = $this->apply_date_offset($data->deadline);
        }

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('project_milestone', $data);
        $this->set_mapping('project_milestone', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_project_deliverable($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;
        $data->projectid = $this->get_new_parentid('project');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->lastuserid = $this->get_mappingid('user', $data->lastuserid);
        $data->modified = $this->apply_date_offset($data->modified);
        $data->created = $this->apply_date_offset($data->created);
        $data->milestoneid = $this->get_mappingid('project_milestone', $data->milestoneid);  
        // The data is actually inserted into the database later in inform_new_usage_id.
        //print_r($data);
        $newitemid = $DB->insert_record('project_deliverable', $data);
        $this->set_mapping('project_deliverable', $oldid, $newitemid, false); // Has no related files
        $this->add_related_files('mod_project', 'deliverable', 'localfile');
        //debugging(print_r($data));
        //debugging('item numéro '.$data->id.' fonctionne XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
    }

    protected function process_project_spectoreq($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('project');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->specid = $this->get_mappingid('project_specification', $data->specid);
        $data->reqid = $this->get_mappingid('project_requirement', $data->reqid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('project_spec_to_req', $data);
        $this->set_mapping('project_spec_to_req', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_project_tasktospec($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('project');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->specid = $this->get_mappingid('project_specification', $data->specid);
        $data->taskid = $this->get_mappingid('project_task', $data->taskid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('project_task_to_spec', $data);
        $this->set_mapping('project_task_to_spec', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_project_tasktodeliv($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('project');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->delivid = $this->get_mappingid('project_deliverable', $data->delivid);
        $data->taskid = $this->get_mappingid('project_task', $data->taskid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('project_task_to_deliv', $data);
        $this->set_mapping('project_task_to_deliv', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_project_taskdependency($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('project');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->master = $this->get_mappingid('project_task', $data->master);
        $data->slave = $this->get_mappingid('project_task', $data->slave);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('project_task_dependency', $data);
        $this->set_mapping('project_task_dependency', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_project_criterion($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('project');

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('project_criterion', $data);
        $this->set_mapping('project_criterion', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_project_assessment($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('project');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->criterion = $this->get_mappingid('group', $data->criterion);
        if ($data->itemclass == 'milestone'){
        	$data->itemid = $this->get_mappingid('project_milestone', $data->itemid);
        } elseif ($data->itemclass == 'task'){
        	$data->itemid = $this->get_mappingid('project_task', $data->itemid);
        } elseif ($data->itemclass == 'deliverable'){
        	$data->itemid = $this->get_mappingid('project_deliverable', $data->itemid);
        }


        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('project_task_dependency', $data);
        $this->set_mapping('project_task_dependency', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_project_validationsession($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('project');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->datecreated = $this->apply_date_offset($data->datecreated);
        $data->dateclosed = $this->apply_date_offset($data->dateclosed);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('project_valid_session', $data);
        $this->set_mapping('project_valid_session', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_project_validationresult($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('project');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->reqid = $this->get_mappingid('project_requirement', $data->reqid);
        $data->validationsessionid = $this->get_mappingid('project_valid_session', $data->validationsessionid);
        $data->lastchangeddate = $this->apply_date_offset($data->lastchangeddate);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('project_valid_session', $data);
        $this->set_mapping('project_valid_session', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_project_globalqualifiers($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = 0;
        
        // do not averride pre-existing global qualifiers
        if (!$DB->exist_record('project_qualifier', array('domain', $data->domain, 'code' => $data->code))){
	        // The data is actually inserted into the database later in inform_new_usage_id.
           $newitemid = $DB->insert_record('project_qualifier', $data);
	        $this->set_mapping('project_qualifier', $oldid, $newitemid, false); // Has no related files
	    }
    }

    protected function process_project_qualifiers($data) {
    	global $DB;
    	
        $data = (object)$data;
        $oldid = $data->id;

        $data->projectid = $this->get_new_parentid('project');
        
        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('project_qualifier', $data);
        $this->set_mapping('project_qualifier', $oldid, $newitemid, false); // Has no related files
    }

    protected function after_execute() {
        // Add project related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_project', 'intro', null);
        $this->add_related_files('mod_project', 'xslfilter', null);
        $this->add_related_files('mod_project', 'cssfilter', null);
        $this->add_related_files('mod_project', 'localfile', 'deliverable');
        
        // Remap all fatherid tree
        $this->remap_tree('requirement', 'fatherid', $this->task->get_activityid());
        $this->remap_tree('specification', 'fatherid', $this->task->get_activityid());
        $this->remap_tree('task', 'fatherid', $this->task->get_activityid());
        $this->remap_tree('deliverable', 'fatherid', $this->task->get_activityid());
    }

	/**
	* Post remaps tree dependencies in a single entity once all records renumbered. 
	*
	*/
	protected function remap_tree($entity, $treekey, $projectid){
		global $DB;
		
		/*if ($entities = $DB->get_records('project_'.$entity, array('projectid' => $projectid))){
			foreach ($entities as $rec){
                $newtreeid = $this->get_mappingid('project_'.$entity, $rec->$treekey);
                $DB->set_field('project_'.$entity, $treekey, $newtreeid, array('projectid', $rec->id));
			}
		}*/
        $rs = $DB->get_recordset('project_'.$entity, array('projectid' => $projectid),
                                 '', 'id',$treekey);
        foreach ($rs as $rec) {
            $rec->$treekey = (empty($rec->treekey)) ? 0 : $this->get_mappingid('project_'.$entity, $rec->$treekey);
            $DB->update_record('project_'.$entity, $rec);
        }
        $rs->close();
	}
}
