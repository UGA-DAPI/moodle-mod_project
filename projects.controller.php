<?php
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
/// Controller

	if ($work == 'close') {
		// $currentGroupId;
		// $project->id;
		$projectidtoclose = required_param('projectid', PARAM_INT);
		if($projectidtoclose>0){
			$query = "
			   UPDATE
				  {project}
			   SET
				  etat = 1
			   WHERE
				  id = {$projectidtoclose}
			";
			$DB->execute($query);
			$url = $CFG->wwwroot.'/mod/project/view.php?view=projects&id='.$cm->id;
			redirect($url);
		}
	}
	
	
