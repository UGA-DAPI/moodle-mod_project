<?php

    /*
    *
    * This screen allows remote code repository setup and control.
    *
	*
	* @package mod-project
	* @category mod
	* @author Yohan Thomas - W3C2i (support@w3c2i.com)
	* @date 30/09/2013
	* @version 3.0
	* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
	*
	*/

    if (!has_capability('mod/project:manage', $context)){
        print_error(get_string('notateacher','project'));
        return;
    }

	echo $pagebuffer;

    echo $OUTPUT->box(get_string('notimplementedyet', 'project'), 'center', '50%');
?>