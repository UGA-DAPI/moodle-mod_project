<?php

    /*
    *
    * This screen gives access to copy operations.
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

    if (!has_capability('mod/project:copy', $context)){
        print_error(get_string('notateacher','project'));
        return;
    }
    /// get groups, will be usefull at many locations

    $groups = groups_get_all_groups($course->id);
    if ($work == 'docopy'){
        function protectTextRecords(&$aRecord, $fieldList){
            $fields = explode(",", $fieldList);
            foreach($fields as $aField){
                if (isset($aRecord->$aField))
                    $aRecord->$aField = str_replace("'", "\\'", $aRecord->$aField);
            }
        }
        /**
        * @return an array for all copied ids translations so foreign keys can be fixed
        */    
        function unitCopy($project, $from, $to, $what, $detail = false){
            $copied = array();
            foreach(array_keys($what) as $aDatatable){
                // skip unchecked entites for copying
                if(!$what[$aDatatable]) continue;
                echo '<tr><td align="left">' . get_string('copying', 'project') . ' ' . get_string("{$aDatatable}s", 'project') . '...';
                $DB->delete_records("project_$aDatatable", array('projectid' => $project->id, 'groupid' => $to));
                if($records = $DB->get_records_select("project_$aDatatable", "projectid = $project->id AND groupid = $from")){
                    // copying each record into target recordset
                    if ($detail)
                        echo '<br/><span class="smalltechnicals">&nbsp&nbsp;&nbsp;copying '. count($records) . " from $aDatatable</span>";
                    foreach($records as $aRecord){
                        $id = $aRecord->id;
                        if ($detail)
                            echo '<br/><span class="smalltechnicals">&nbsp&nbsp;&nbsp;copying item : ['. $id . '] ' . @$aRecord->abstract . '</span>';
                        $aRecord->id = 0;
                        $aRecord->groupid = $to;
                        protectTextRecords($aRecord, 'title,abstract,rationale,description,environement,organisation,department');
                        // unassigns users from entites in copied entities (not relevant context)
                        if (isset($aRecord->assignee)){
                            $aRecord->assignee = 0;
                        }
                        if (isset($aRecord->owner)){
                            $aRecord->owner = 0;
                        }
                        // if milestones are not copied, no way to keep milestone assignation
                        if (isset($aRecord->milestoneid) && $what['milestone'] == 0){
                            $aRecord->milestoneid = 0;
                        }
                        $insertedid = $DB->insert_record("project_$aDatatable", $aRecord);
                        $copied[$aDatatable][$id] = $insertedid;
                    }
                }
                echo '</td><td align="right"><span class="technicals">' . get_string('done', 'project') . '</span></td></tr>';
            }
            return $copied;
        }
        /**
        * this function fixes in new records (given in recordSet as a comma separated list of indexes
        * some foreign key (fKey) that should shift from an unfixedValue to a fixed value in translations
        * table.
        * @return true if no errors.
        */
        function fixForeignKeys($project, $group, $table, $fKey, $translations, $recordSet){
         global $CFG;
         $result = 1;
         $recordList = implode(',', $recordSet);
         foreach(array_keys($translations) as $unfixedValue){
             $query = "
             UPDATE 
             {project_{$table}}
             SET
             $fKey = $translations[$unfixedValue]
             WHERE
             projectid = {$project->id} AND
             $fKey = $unfixedValue AND
             id IN ($recordList)
             ";
             $result = $result && $DB->execute($query);        
         }
         return $result;
     }
     $from = required_param('from', PARAM_INT);
     $to = required_param('to', PARAM_RAW);
     $detail = optional_param('detail', 0, PARAM_INT);
     $what['heading'] = optional_param('headings', 0, PARAM_INT);
     $what['requirement'] = optional_param('requs', 0, PARAM_INT);
     $what['spec_to_req'] = optional_param('specstoreq', 0, PARAM_INT);
     $what['specification'] = optional_param('specs', 0, PARAM_INT);
     $what['task_to_spec'] = optional_param('taskstospec', 0, PARAM_INT);
     $what['task_to_deliv'] = optional_param('tasktodeliv', 0, PARAM_INT);
     $what['task_dependency'] = optional_param('tasktotask', 0, PARAM_INT);
     $what['milestone'] = optional_param('miles', 0, PARAM_INT);
     $what['task'] = optional_param('tasks', 0, PARAM_INT);
     $what['deliverable'] = optional_param('deliv', 0, PARAM_INT);
     $targets = explode(',', $to);
     echo $OUTPUT->box_start('center', '70%');
     foreach($targets as $aTarget){
            // do copy data
        echo '<table width="100%">';
        $copied = unitCopy($project, $from, $aTarget, $what, $detail);
            // do fix some foreign keys
        echo '<tr><td align="left">' . get_string('fixingforeignkeys', 'project') . '...</td><td align="right">';
        if (array_key_exists('spec_to_req', $copied) && count(array_values(@$copied['spec_to_req']))){
            fixForeignKeys($project, $aTarget, 'spec_to_req', 'specid', $copied['specification'], array_values($copied['spec_to_req']));
            fixForeignKeys($project, $aTarget, 'spec_to_req', 'reqid', $copied['requirement'], array_values($copied['spec_to_req']));
        }
        if (array_key_exists('task_to_spec', $copied) && count(array_values(@$copied['task_to_spec']))){
            fixForeignKeys($project, $aTarget, 'task_to_spec', 'taskid', $copied['task'], array_values($copied['task_to_spec']));
            fixForeignKeys($project, $aTarget, 'task_to_spec', 'specid', $copied['specification'], array_values($copied['task_to_spec']));
        }
        if (array_key_exists('task_to_deliv', $copied) && count(array_values(@$copied['task_to_deliv']))){
            fixForeignKeys($project, $aTarget, 'task_to_deliv', 'taskid', $copied['task'], array_values($copied['task_to_deliv']));
            fixForeignKeys($project, $aTarget, 'task_to_deliv', 'delivid', $copied['deliverable'], array_values($copied['task_to_deliv']));
        }
        if (array_key_exists('task_dependency', $copied) && count(array_values(@$copied['task_dependency']))){
            fixForeignKeys($project, $aTarget, 'task_dependency', 'master', $copied['task'], array_values($copied['task_dependency']));
            fixForeignKeys($project, $aTarget, 'task_dependency', 'slave', $copied['task'], array_values($copied['task_dependency']));
        }
        if (array_key_exists('milestone', $copied) && array_key_exists('task', $copied) && count(array_values(@$copied['task'])) && count(array_values(@$copied['milestone']))){
            fixForeignKeys($project, $aTarget, 'task', 'milestoneid', $copied['milestone'], array_values($copied['task']));
        }
        if (array_key_exists('milestone', $copied) && array_key_exists('deliverable', $copied) && count(array_values(@$copied['deliverable'])) && count(array_values(@$copied['milestone']))){
            fixForeignKeys($project, $aTarget, 'deliverable', 'milestoneid', $copied['milestone'], array_values($copied['deliverable']));
        }
            // fixing fatherid values
        if(array_key_exists('specification', $copied))
            fixForeignKeys($project, $aTarget, 'specification', 'fatherid', $copied['specification'], array_values($copied['specification']));
        if(array_key_exists('requirement', $copied))
            fixForeignKeys($project, $aTarget, 'requirement', 'fatherid', $copied['requirement'], array_values($copied['requirement']));
        if(array_key_exists('task', $copied))
            fixForeignKeys($project, $aTarget, 'task', 'fatherid', $copied['task'], array_values($copied['task']));
        if(array_key_exists('deliverable', $copied))
            fixForeignKeys($project, $aTarget, 'deliverable', 'fatherid', $copied['deliverable'], array_values($copied['deliverable']));
            // must delete all grades in copied group
        $DB->delete_records('project_assessment', array('projectid' => $project->id, 'groupid' => $aTarget));
        echo '<span class="technicals">' . get_string('done', 'project') . '</td></tr>';
    }
    echo '</table>';
    echo $OUTPUT->box_end();
}

/// Setup project copy operations by defining source and destinations 

echo $pagebuffer;

if ($work == 'what'){
    $from = required_param('from', PARAM_INT);
    $to = required_param('to', PARAM_RAW);
        // check some inconsistancies here : 
    if (in_array($from, $to)){
        $errormessage = get_string('cannotselfcopy', 'project');
        $work = 'setup';
    } else {
        ?>
        <center>
            <?php 
            echo $OUTPUT->heading(get_string('copywhat', 'project')); 
            $toArray = array();
            foreach ($to as $aTarget) $toArray[] = $groups[$aTarget]->name;
            if ($from){
                echo $OUTPUT->box($groups[$from]->name . ' &gt;&gt; ' . implode(',',$toArray), 'center');
            } else {
                echo $OUTPUT->box(get_string('groupless', 'project') . ' &gt;&gt; ' . implode(',',$toArray), 'center');
            }
            ?>
            <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['copywhatform'].work.value='confirm';
        document.forms['copywhatform'].submit();
    }
    function cancel(){
        document.forms['copywhatform'].work.value='setup';
        document.forms['copywhatform'].submit();
    }
    function formControl(entity){
        switch(entity){
            case 'requs':{
                if (!document.forms['copywhatform'].requs.checked == true){
                    document.forms['copywhatform'].spectoreq.disabled = true;
                    aDiv = document.getElementById('spectoreq_span');
                    aDiv.className = 'dithered';
                } else {
                    document.forms['copywhatform'].spectoreq.disabled = false;
                    aDiv = document.getElementById('spectoreq_span');
                    aDiv.className = '';
                }
                break;
            }
            case 'specs':{
                if (!document.forms['copywhatform'].specs.checked == true){
                    document.forms['copywhatform'].spectoreq.disabled = true;
                    document.forms['copywhatform'].tasktospec.disabled = true;
                    aDiv = document.getElementById('tasktospec_span');
                    aDiv.className = 'dithered';
                    aDiv = document.getElementById('spectoreq_span');
                    aDiv.className = 'dithered';
                } else {
                    document.forms['copywhatform'].spectoreq.disabled = false;
                    document.forms['copywhatform'].tasktospec.disabled = false;
                    aDiv = document.getElementById('tasktospec_span');
                    aDiv.className = '';
                    aDiv = document.getElementById('spectoreq_span');
                    aDiv.className = '';
                }
                break;
            }
            case 'tasks':{
                if (!document.forms['copywhatform'].tasks.checked == true){
                    document.forms['copywhatform'].tasktospec.disabled = true;
                    document.forms['copywhatform'].tasktodeliv.disabled = true;
                    document.forms['copywhatform'].tasktotask.disabled = true;
                    aDiv = document.getElementById('tasktospec_span');
                    aDiv.className = 'dithered';
                    aDiv = document.getElementById('tasktotask_span');
                    aDiv.className = 'dithered';
                    aDiv = document.getElementById('tasktodeliv_span');
                    aDiv.className = 'dithered';
                } else {
                    document.forms['copywhatform'].tasktospec.disabled = false;
                    document.forms['copywhatform'].tasktodeliv.disabled = false;
                    document.forms['copywhatform'].tasktotask.disabled = false;
                    aDiv = document.getElementById('tasktospec_span');
                    aDiv.className = '';
                    aDiv = document.getElementById('tasktotask_span');
                    aDiv.className = '';
                    aDiv = document.getElementById('tasktodeliv_span');
                    aDiv.className = '';
                }
                break;
            }
            case 'deliv':{
                if (!document.forms['copywhatform'].deliv.checked == true){
                    document.forms['copywhatform'].tasktodeliv.disabled = true;
                    aDiv = document.getElementById('tasktodeliv_span');
                    aDiv.className = 'dithered';
                } else {
                    document.forms['copywhatform'].tasktodeliv.disabled = false;
                    aDiv = document.getElementById('tasktodeliv_span');
                    aDiv.className = '';
                }
                break;
            }
        }
    }
    //]]>
    </script>
    <form name="copywhatform" action="view.php" method="post">
        <input type="hidden" name="id" value="<?php p($cm->id) ?>"/>
        <input type="hidden" name="from" value="<?php p($from) ?>"/>
        <input type="hidden" name="to" value="<?php p(implode(',', $to)) ?>"/>
        <input type="hidden" name="work" value=""/>
        <table cellpadding="5">
            <tr valign="top">
                <td align="right"><b><?php print_string('what', 'project') ?></b></td>
                <td align="left">
                    <p><b><?php print_string('entities', 'project') ?></b></p>
                    <p><input type="checkbox" name="headings" value="1" checked="checked" /> <?php print_string('headings', 'project'); ?>
                        <?php
                        if (@$project->projectusesrequs) echo "<br/><input type=\"checkbox\" name=\"requs\" value=\"1\" checked=\"checked\" onclick=\"formControl('requs')\" /> " . get_string('requirements', 'project');
                        if (@$project->projectusesspecs) echo "<br/><input type=\"checkbox\" name=\"specs\" value=\"1\" checked=\"checked\" onclick=\"formControl('specs')\" /> " . get_string('specifications', 'project');
                        ?>
                        <br/><input type="checkbox" name="tasks" value="1" checked="checked" onclick="formControl('tasks')" /> <?php print_string('tasks', 'project'); ?>
                        <br/><input type="checkbox" name="miles" value="1" checked="checked"  onclick="formControl('miles')" /> <?php print_string('milestones', 'project'); ?>
                        <?php
                        if (@$project->projectusesspecs) echo "<br/><input type=\"checkbox\" name=\"deliv\" value=\"1\" checked=\"checked\"  onclick=\"formControl('deliv')\" /> " . get_string('deliverables', 'project');
                        ?>
                    </p>
                    <p><b><?php print_string('crossentitiesmappings', 'project') ?></b></p>
                    <?php
                    if (@$project->projectusesrequs && @$project->projectusesspecs) echo "<p><input type=\"checkbox\" name=\"spectoreq\" value=\"1\" checked=\"checked\" /> <span id=\"spectoreq_span\" class=\"\"> " . get_string('spec_to_req', 'project') . '</span>';
                    if (@$project->projectusesspecs) echo "<br/><input type=\"checkbox\" name=\"tasktospec\" value=\"1\" checked=\"checked\" /> <span id=\"tasktospec_span\" class=\"\"> ". get_string('task_to_spec', 'project') . '</span>';
                    ?>
                    <br/><input type="checkbox" name="tasktotask" value="1" checked="checked" /> <span id="tasktotask_span" class=""><?php print_string('task_to_task', 'project'); ?></span>
                    <?php
                    if (@$project->projectusesdelivs) echo "<br/><input type=\"checkbox\" name=\"tasktodeliv\" value=\"1\" checked=\"checked\" /> <span id=\"tasktodeliv_span\" class=\"\"> " . get_string('task_to_deliv', 'project') . '</span>';
                    ?>
                </p>
            </td>
        </tr>
    </table>
    <p><input type="button" name="go_btn" value="<?php print_string('continue'); ?>" onclick="senddata()" />
        <input type="button" name="cancel_btn" value="<?php print_string('cancel'); ?>" onclick="cancel()" /></p>
    </form>
</center>
<?php
}
}

/// Copy last confirmation form 

if ($work == 'confirm'){
    $from = required_param('from', PARAM_INT);
    $to = required_param('to', PARAM_RAW);
    $copyheadings = optional_param('headings', 0, PARAM_INT);
    $copyrequirements = optional_param('requs', 0, PARAM_INT);
    $copyspecifications = optional_param('specs', 0, PARAM_INT);
    $copymilestones = optional_param('miles', 0, PARAM_INT);
    $copytasks = optional_param('tasks', 0, PARAM_INT);
    $copydeliverables = optional_param('deliv', 0, PARAM_INT);
    $copyspectoreq = optional_param('spectoreq', 0, PARAM_INT);
    $copytasktospec = optional_param('tasktospec', 0, PARAM_INT);
    $copytasktotask = optional_param('tasktotask', 0, PARAM_INT);
    $copytasktodeliv = optional_param('tasktodeliv', 0, PARAM_INT);
    ?>
    <center>
        <?php 
        echo $OUTPUT->heading(get_string('copyconfirm', 'project')); 
        echo $OUTPUT->box(get_string('copyadvice', 'project'), 'center');
        ?>
        <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['confirmcopyform'].work.value='docopy';
        document.forms['confirmcopyform'].submit();
    }
    function cancel(){
        document.forms['confirmcopyform'].work.value='setup';
        document.forms['confirmcopyform'].submit();
    }
    //]]>
    </script>
    <form name="confirmcopyform" action="view.php" method="post">
        <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
        <input type="hidden" name="from" value="<?php p($from) ?>" />
        <input type="hidden" name="to" value="<?php p($to) ?>" />
        <input type="hidden" name="headings" value="<?php p($copyheadings) ?>" />
        <input type="hidden" name="requs" value="<?php p($copyrequirements) ?>" />
        <input type="hidden" name="specs" value="<?php p($copyspecifications) ?>" />
        <input type="hidden" name="tasks" value="<?php p($copytasks) ?>" />
        <input type="hidden" name="miles" value="<?php p($copymilestones) ?>" />
        <input type="hidden" name="deliv" value="<?php p($copydeliverables) ?>" />
        <input type="hidden" name="spectoreq" value="<?php p($copyspectoreq) ?>" />
        <input type="hidden" name="tasktospec" value="<?php p($copytasktospec) ?>" />
        <input type="hidden" name="tasktotask" value="<?php p($copytasktotask) ?>" />
        <input type="hidden" name="tasktodeliv" value="<?php p($copytasktodeliv) ?>" />
        <input type="hidden" name="work" value="" />
        <input type="checkbox" name="detail" value="1" /> <?php print_string('givedetail', 'project') ?>
        <input type="button" name="go_btn" value="<?php print_string('continue'); ?>" onclick="senddata()" />
        <input type="button" name="cancel_btn" value="<?php print_string('cancel'); ?>" onclick="cancel()" />
    </form>
</center>
<?php
}

/// Copy first setup form 

if ($work == '' || $work == 'setup'){
    ?>
    <center>
        <?php 
        echo $OUTPUT->heading(get_string('copysetup', 'project')); 
        if (isset($errormessage)){
            echo $OUPPUT->box("<span style=\"color:white\">$errormessage</span>", 'center', '70%', 'warning');
        }
        ?>
        <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['copysetupform'].work.value='what';
        document.forms['copysetupform'].submit();
    }
    //]]>
    </script>
    <form name="copysetupform" action="view.php" method="post">
        <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
        <input type="hidden" name="work" value="" />
        <table width="80%">
            <tr valign="top">
                <td align="right"><b><?php print_string('from', 'project') ?></b></td>
                <td align="left">
                    <?php 
                    $fromgroups = array();
                    if (!empty($groups)){
                        foreach(array_keys($groups) as $aGroupId){
                            $fromgroups[$groups[$aGroupId]->id] = $groups[$aGroupId]->name;
                        }
                    }
                    echo html_writer::select($fromgroups,  'from', 0 + get_current_group($course->id), get_string('groupless', 'project'));
                    ?>
                </td>
            </tr>
            <tr valign="top">
                <td align="right"><b><?php print_string('upto', 'project') ?></b></td>
                <td align="left">
                    <select name="to[]" multiple="multiple" size="6" style="width : 80%">
                        <?php
                        echo "<option value=\"0\">".get_string('groupless', 'project')."</option>";
                        if (!empty($groups)){
                            foreach($groups as $aGroup){
                                echo "<option value=\"{$aGroup->id}\">{$aGroup->name}</option>";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
        <input type="button" name="go_btn" value="<?php print_string('continue'); ?>" onclick="senddata()" />
    </form>
</center>
<?php
}
?>