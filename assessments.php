<?php

    /**
    *
    * This is a screen for assessing students
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

    /**
    * A small utility function for making scale menus
    *
    * @param object $project
    * @param int $id
    * @param string $selected
    * @param boolean $return if return is true, returns the HTML flow as a string, prints it otherwise
    */
    function make_grading_menu($project, $id, $selected = '', $return = false){
        if ($project->grade > 0){
            for($i = 0 ; $i <= $project->grade ; $i++)
                $scalegrades[$i] = $i; 
        } else {
            $scaleid = - ($project->grade);
            if ($scale = $DB->get_record('scale', array('id' => $scaleid))) {
                $scalegrades = make_menu_from_list($scale->scale);
            }
        }
        $str = html_writer::select($scalegrades, $id, $selected, '');
        if ($return) return $str;
        echo $str;
    }


    if (!has_capability('mod/project:gradeproject', $context)){
        print_error(get_string('notateacher','project'));
        return;
    }
    echo $OUTPUT->heading(get_string('assessment'));
    // checks if assessments can occur
    if (!groups_get_activity_groupmode($cm, $project->course)){
        // $groupStudents = get_course_students($project->course);
        $groupStudents = get_users_by_capability($context, 'mod/project:canbeevaluated', 'id,firstname,lastname,email,picture', 'lastname');
    } else {
        $groupmembers = groups_get_members($currentGroupId);
        foreach($groupmembers as $amember){
            if (!has_capability('mod/project:canbeevaluated', $context, $amember->id)) continue;
            $groupStudents[] = clone($amember);
        }
    }
    if (!isset($groupStudents) || count($groupStudents) == 0 || empty($groupStudents)){
        echo $OUTPUT->box(get_string('noonetoassess', 'project'), 'center', '70%');
        return;
    } else {
       $studentListArray = array();
       foreach($groupStudents as $aStudent){
          $userpic = new user_picture();
          $userpic->user = $aStudent->id;
          $userpic->courseid = $course->id;
          $userpic->image->src = !empty($aStudent->picture);
          $studentListArray[] = $aStudent->lastname . ' ' . $aStudent->firstname . ' ' . $OUTPUT->user_picture($userpic) ;
       }
       echo $OUTPUT->box_start('center', '80%');
       echo '<center><i>'.get_string('evaluatingforusers', 'project') .' : </i> '. implode(',', $studentListArray) . '</center><br/>';
    }
    if ($work == 'regrade'){
        $autograde = project_autograde($project, $currentGroupId);
        if ($project->grade > 0){
            unset($assessment);
            $assessment->id = 0;
            $assessment->projectid = $project->id;
            $assessment->groupid = $currentGroupId;
            $assessment->userid = $aStudent->id;
            $assessment->itemid = 0;
            $assessment->itemclass = 'auto';
            $assessment->criterion = 0;
            $assessment->grade = round($autograde * $project->grade);
            if ($oldrecord = $DB->get_record_select('project_assessment', "projectid = {$project->id} AND userid = {$aStudent->id} AND itemid = 0 AND itemclass='auto'")){
                $assessment->id = $oldrecord->id;
                $DB->update_record('project_assessment', $assessment);
                add_to_log($course->id, 'project', 'grade', "view.php?id=$cm->id&view=view_summary", $project->id, $cm->id, $aStudent->id);
            } else {
                $DB->insert_record('project_assessment', $assessment);
            }
        }
    }
    echo $OUTPUT->box_end();
    // do what needed
    if ($work == 'dosave'){
        // getting candidate keys for grading
        $parmKeys = array_keys($_POST);
        function filterTeachergradeKeys($var){
            return preg_match("/teachergrade_/", $var);
        }
        function filterFreegradeKeys($var){
            return preg_match("/free_/", $var);
        }
        $teacherGrades = array_filter($parmKeys, 'filterTeachergradeKeys');
        $freeGrades = array_filter($parmKeys, 'filterFreegradeKeys');
        foreach($groupStudents as $aStudent){
            // dispatch autograde for all students 
            if ($project->autogradingenabled){
                unset($assessment);
                $assessment->id = 0;
                $assessment->projectid = $project->id;
                $assessment->groupid = $currentGroupId;
                $assessment->userid = $aStudent->id;
                $assessment->itemid = 0;
                $assessment->itemclass = 'auto';
                $assessment->criterion = 0;
                $grade = optional_param('autograde', '', PARAM_INT);
                if (!empty($grade)) $assessment->grade = $grade;
                if ($oldrecord = $DB->get_record_select('project_assessment', "projectid = {$project->id} AND userid = {$aStudent->id} AND itemid = '{$assessment->itemid}' AND itemclass='{$assessment->itemclass}'")){
                    $assessment->id = $oldrecord->id;
                    $DB->update_record('project_assessment', $assessment);
                    add_to_log($course->id, 'project', 'grade', "view.php?id={$cm->id}&view=view_summary&group={$currentGroupId}", $project->id, $cm->id, $aStudent->id);
                } else {
                    $DB->insert_record('project_assessment', $assessment);
                }
            }
            // dispatch teachergrades for all students 
            foreach($teacherGrades as $aGradeKey){
                preg_match('/teachergrade_([^_]*?)_([^_]*?)(?:_(.*?))?$/', $aGradeKey, $matches);
                unset($assessment);
                $assessment->id = 0;
                $assessment->projectid = $project->id;
                $assessment->groupid = $currentGroupId;
                $assessment->userid = $aStudent->id;
                $assessment->itemid = $matches[2];
                $assessment->itemclass = $matches[1];
                $criterionClause = '';
                if (isset($matches[3])){
                     $assessment->criterion = $matches[3];
                     $criterionClause = "AND criterion='{$assessment->criterion}'";
                }
                $grade = optional_param($aGradeKey, '', PARAM_INT);
                if (!empty($grade)) $assessment->grade = $grade;
                if ($oldrecord = $DB->get_record_select('project_assessment', "projectid = {$project->id} AND userid = {$aStudent->id} AND itemid = {$assessment->itemid} AND itemclass='{$assessment->itemclass}' {$criterionClause}")){
                    $assessment->id = $oldrecord->id;
                    $DB->update_record('project_assessment', $assessment);
                } else {
                    $DB->insert_record('project_assessment', $assessment);
                }
            }
            // dispatch freegrades
            foreach($freeGrades as $aGradeKey){
                preg_match('/free_([^_]*?)$/', $aGradeKey, $matches);
                unset($assessment);
                $assessment->id = 0;
                $assessment->projectid = $project->id;
                $assessment->groupid = $currentGroupId;
                $assessment->userid = $aStudent->id;
                $assessment->itemclass = 'free';
                $assessment->itemid = 0;
                $assessment->criterion = $matches[1];
                $grade = optional_param($aGradeKey, '', PARAM_INT);
                if (!empty($grade)) $assessment->grade = $grade;
                if ($oldrecord = $DB->get_record_select('project_assessment', "projectid = {$project->id} AND userid = {$aStudent->id} AND itemclass = 'free' AND itemid = 0 AND criterion = {$assessment->criterion}")){
                    $assessment->id = $oldrecord->id;
                    $DB->update_record('project_assessment', $assessment);
                } else {
                    $DB->insert_record('project_assessment', $assessment);
                }
            }
            add_to_log($course->id, 'project', 'grade', "view.php?id=$cm->id&view=view_summary&group={$currentGroupId}", $project->id, $cm->id, $aStudent->id);
        }
    }
    elseif ($work == 'doerase'){
        foreach($groupStudents as $aStudent){
            $DB->delete_records('project_assessment', array('projectid' => $project->id, 'userid' => $aStudent->id));
            add_to_log($course->id, 'project', 'grade', "view.php?id=$cm->id&view=view_summary&group={$currentGroupId}", 'erase', $cm->id, $aStudent->id);
            add_to_log($course->id, 'project', 'grade', "view.php?id=$cm->id&view=view_summary&group={$currentGroupId}", 'erase', $cm->id);
        }
    }
    if ($project->teacherusescriteria){
        $freecriteria =  $DB->get_records_select('project_criterion', "projectid = {$project->id} AND isfree = 1");
        $criteria = $DB->get_records_select('project_criterion', "projectid = {$project->id} AND isfree = 0");
        if (!$criteria && !$freecriteria){
            echo $OUTPUT->box(format_text(get_string('cannotevaluatenocriteria','project'), FORMAT_HTML), 'center');
            return;
        }
    }
    $canGrade = false;
    ?>
    <center>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(cmd){
        document.forms['assessform'].work.value="do" + cmd;
        document.forms['assessform'].submit();
    }
    function cancel(){
        document.forms['assessform'].submit();
    }
    //]]>
    </script>
    <form name="assessform" method="post" action="view.php">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>"/>
    <input type="hidden" name="work" value=""/>
    <table width="80%">
    <?php
        $milestones = $DB->get_records_select('project_milestone', "projectid = {$project->id} AND groupid = {$currentGroupId}");
        $grades = $DB->get_records_select('project_assessment', "projectid = {$project->id} AND groupid = {$currentGroupId} GROUP BY itemid,criterion,itemclass");
        $gradesByClass = array();
        // if there are any grades yet, compile them by categories
        if($grades){
            $grades = array_values($grades);
            for($i = 0 ; $i < count($grades) ; $i++ ){
                $gradesByClass[$grades[$i]->itemclass][$grades[$i]->itemid][$grades[$i]->criterion] = $grades[$i]->grade;
            }
        }
        if($milestones && (!$project->teacherusescriteria || $criteria)){
            $canGrade = true;
            echo "<tr><td colspan=\"2\" align=\"center\">";
            echo $OUTPUT->heading(get_string('itemevaluators', 'project'));
            echo "</td></tr>";
            foreach($milestones as $aMilestone){
                echo "<tr valign=\"top\"><td align=\"left\"><b>";
                echo get_string('evaluatingfor','project')." M.{$aMilestone->ordering} {$aMilestone->abstract}</b>";
                echo "</td></tr><tr><td>";
                if (!$project->teacherusescriteria){
                    $teachergrade = @$gradesByClass['milestone'][$aMilestone->id][0];
                    echo get_string('teachergrade','project').' ';
                    make_grading_menu($project, "teachergrade_milestone_{$aMilestone->id}", $teachergrade);
                } else {
                    foreach($criteria as $aCriterion){
                        $criteriongrade = @$gradesByClass['milestone'][$aMilestone->id][$aCriterion->id];
                        echo $aCriterion->label.' : ';
                        make_grading_menu($project, "teachergrade_milestone_{$aMilestone->id}_{$aCriterion->id}", $criteriongrade);
                        echo ' * ' . $aCriterion->weight . '<br/>';
                    }
                }
                echo "</td></tr>";
            }
        }
        // additional free criteria for grading (including autograde)
        if ($project->autogradingenabled || $project->teacherusescriteria){
            echo "<tr><td colspan=\"2\">";
            echo $OUTPUT->heading(get_string('globalevaluators', 'project'));
            echo "</td></tr>";
            $canGrade = true;
        }
        if ($project->autogradingenabled){
            $autograde = @$gradesByClass['auto'][0][0];
            echo '<tr><td align="left">'.get_string('autograde','project').'</td><td align="left">';
            echo make_grading_menu($project, 'autograde', $autograde, true);
            echo " <a href=\"?work=regrade&amp;id={$cm->id}\">".get_string('calculate','project').'</a></td></tr>';
        }
        if ($project->teacherusescriteria){
            if (@$freecriteria){
                foreach($freecriteria as $aFreeCriterion){
                    $freegrade = @$gradesByClass['free'][0][$aFreeCriterion->id];
                    echo "<tr><td align=\"left\">{$aFreeCriterion->label}</td><td align=\"left\">";
                    make_grading_menu($project, "free_{$aFreeCriterion->id}", $freegrade);
                    echo " x {$aFreeCriterion->weight}</td></tr>";
                }
            }
        }
        if (!$canGrade){
            echo $OUTPUT->box(get_string('cannotevaluate', 'project'), 'center', '70%');
        }
    ?>
    </table>
    <br/>
    <br/>
    <input type="button" name="go_btn" value="<?php print_string('updategrades', 'project') ?>" onclick="senddata('save')" />
    <input type="button" name="erase_btn" value="<?php print_string('cleargrades', 'project') ?>" onclick="senddata('erase')" />
    </form>
    </center>