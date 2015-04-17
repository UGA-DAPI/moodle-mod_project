<?php
/*
*
* @package mod-project
* @category mod
* @author Yohan Thomas - W3C2i (support@w3c2i.com)
* @date 30/09/2013
* @version 3.0
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*
*/
if (!defined('MOODLE_INTERNAL')) die("You cannot access this script directly");

function project_import_entity($projectid, $cmid, $data, $type, $groupid){
	global $USER, $CFG, $DB, $OUTPUT;
	// normalise to unix
	$data = str_replace("\r\n", "\n", $data);
	$data = explode("\n", $data);

	$errors = 0;
	$errors_no_parent = 0;
	$errors_insert = 0;
	$errors_bad_counts = 0;

	switch($type){
		case 'requs':
			$tablename = 'project_requirement';
			$view = 'requirements';
		break;
		case 'specs':
			$tablename = 'project_specification';
			$view = 'specifications';
		break;
		case 'tasks':
			$tablename = 'project_task';
			$view = 'tasks';
		break;
		case 'deliv':
			$tablename = 'project_deliverable';
			$view = 'deliverables';
		break;
		default:
		 print_error('errorunknownimporttype', 'project');
	}
	if (!empty($data)){
		$columns = $data[0];
		$columnnames = explode(';', $columns);
		if (!in_array('id', $columnnames)){
		 	print_error('errorbadformatmissingid', 'project');
		}
		if (!in_array('id', $columnnames)){
		 	print_error('errorbadformatmissingparent', 'project');
		}
		// removing title column
		$titleline = true;
		$i = 2;
		echo "<pre>";
		$errors_bad_counts = 0;
		foreach($data as $line){

			if ($titleline == true){
				$titleline = false;
				continue;
			}

			$recordarr = explode(';', $line);
			if (count($recordarr) != count($columnnames)) {
				$errors_bad_counts++;
				mtrace("\nBad count at line : $i");
				$i++;
				continue;
			} else {
				$checkedrecords[] = $line;
			}
			$i++;
		}
		echo '</pre>';
	} else {
		print_error('errornorecords', 'project');
	}

	if (!empty($checkedrecords)){
		// test insertability on first record before deleting everything
		$recobject = (object)array_combine($columnnames, explode(';', $checkedrecords[0]));
		unset($recobject->id);
		unset($recobject->parent);

		$recobject->userid = $USER->id;
		$recobject->created = time();
		$recobject->modified = time();
		$recobject->lastuserid = $USER->id;
		$recobject->groupid = $groupid;
		$recobject->format = 0;
		$recobject->abstract = '';

		if ($DB->insert_record($tablename, $recobject)){
			$DB->delete_records($tablename, array('projectid' => $projectid));
			// purge crossmappings
			switch($type){
				case 'requs':
					$DB->delete_records('project_spec_to_req', array('projectid' => $projectid));
				break;
				case 'specs':
					$DB->delete_records('project_spec_to_req', array('projectid' => $projectid));
					$DB->delete_records('project_task_to_spec', array('projectid' => $projectid));
				break;
				case 'tasks':
					$DB->delete_records('project_task_to_spec', array('projectid' => $projectid));
					$DB->delete_records('project_task_to_deliv', array('projectid' => $projectid));
					$DB->delete_records('project_task_dependency', array('projectid' => $projectid));
				break;
				case 'deliv':
					$DB->delete_records('project_task_to_deliv', array('projectid' => $projectid));
				break;
			}
			$ID_MAP = array();
			$PARENT_ORDERING = array();
			$ordering = 1;
			foreach($checkedrecords as $record){
				$recobject = (object)array_combine($columnnames, explode(';', $record));
				$oldid = $recobject->id;
				$parent = $recobject->parent;
				unset($recobject->id);
				unset($recobject->parent);
				if (!isset($TREE_ORDERING[$parent])){
					$TREE_ORDERING[$parent] = 1;
				} else {
					$TREE_ORDERING[$parent]++;
				}
				$recobject->ordering = $TREE_ORDERING[$parent];
				if ($parent != 0){
					if (empty($ID_MAP[$parent])){
						$errors++;
						$errors_no_parent++;
						continue;
					}
					$recobject->fatherid = $ID_MAP[$parent];
				} else {
					$recobject->fatherid = 0;
				}

				$recobject->projectid = $projectid;
				$recobject->format = MOODLE_HTML;
				$recobject->created = time();
				$recobject->modified = time();
				$recobject->userid = $USER->id;
				$recobject->lastuserid = $USER->id;
				if(empty($recobject->abstract)){
					$recobject->abstract = shorten_text($recobject->description, 100);
				}

				// prepare record
				switch($type){
					case 'requs':
					break;
					case 'specs':
					break;
					case 'tasks':
					break;
					case 'deliv':
					break;
				}

				if (!($ID_MAP["$oldid"] = $DB->insert_record($tablename, $recobject))){
					$errors++;
					$errors_insert++;
				}
			}
		} else {
			echo $OUPUT->notification("Could not insert records. Maybe file column names are not compatible. ". mysql_error());
		}
	}
	if($errors){
		echo "Errors : $errors<br/>";
		echo "Errors in tree : $errors_no_parent<br/>";
		echo "Insertion Errors : $errors_insert<br/>";
		echo "Insertion Errors : $errors_bad_counts<br/>";
	}
	echo $OUTPUT->continue_button($CFG->wwwroot."/mod/project/view.php?view=$view&id=$cmid");
	echo $OUTPUT->footer();
	exit();
}

?>