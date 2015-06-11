<?php

    /*
    *
    * This screen allows remote code repository setup and control.
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

    if (!has_capability('mod/project:cvs', $context)){
        print_error(get_string('notateacher','project'));
        return;
    }

	echo $pagebuffer;

    echo $OUTPUT->box(get_string('notimplementedyet', 'project'), 'center', '50%');
?>