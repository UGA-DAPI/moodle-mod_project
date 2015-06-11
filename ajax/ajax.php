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

/**
 * Process ajax requests
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

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

include "../../../config.php";
require_once $CFG->dirroot."/mod/project/locallib.php";

$id = required_param('id', PARAM_INT);
$idType = required_param('idtype', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$sesskey = optional_param('sesskey', false, PARAM_TEXT);
/*
$itemorder = optional_param('itemorder', false, PARAM_SEQUENCE);

$cm = get_coursemodule_from_id('feedback', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$feedback = $DB->get_record('feedback', array('id'=>$cm->instance), '*', MUST_EXIST);

require_sesskey();

$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/feedback:edititems', $context);

$return = false;
*/
/*
require_sesskey();
$cm = get_coursemodule_from_id('project', $id)
$context = context_module::instance($id);*/
global $DB;
switch ($action) {
    case 'getdatatype':
		//if(has_capability('mod/project:addinstance', $context)){
			/*$itemlist = explode(',', trim($itemorder, ','));
			if (count($itemlist) > 0) {
				$return = feedback_ajax_saveitemorder($itemlist, $feedback);
			}*/
			$query = "
			  SELECT name,intro,projectconfidential FROM {project} WHERE id =".(int)$idType;
			$return =  array_pop($DB->get_records_sql($query));
		//}
        break;
}

echo json_encode($return);
die;
