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
defined('MOODLE_INTERNAL') || die;

/*$ADMIN->add('root', new admin_category('project', get_string('hub', 'project')));
$ADMIN->add('project', new admin_externalpage('settings', get_string('settings', 'project'),
        $CFG->wwwroot."/local/hub/admin/settings.php",
        'moodle/site:config'));*/




//In the actual version of the plugin, those settings are useless. i'll comment this for now, but you can use this later 

/*

if ($ADMIN->fulltree) {
	$rolelist = get_all_roles();
	//print_r($rolelist);
	foreach ($rolelist as $roleobject) {
		$role[] = $roleobject->shortname;
	}

    $settings->add(new admin_setting_configselect('project/teacher_role',
        get_string('editingteacherreplacement', 'project'), get_string('editingteacherreplacementexplain', 'project'), 'editingteacher',
        $role));

    $settings->add(new admin_setting_configselect('project/tutor_role',
        get_string('teacherreplacement', 'project'), get_string('teacherreplacementexplain', 'project'), 'teacher',
        $role));

}*/