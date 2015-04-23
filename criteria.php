<?php

    /**
    *
    * This screen allows criteria management.
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

    if (!has_capability('mod/project:criteria', $context)){
        print_error(get_string('notateacher','project'));
        return;
    }

    if ($work == 'update'){
    	$criterion = new StdClass;
        $criterion->id = required_param('itemid', PARAM_INT);
        $criterion->projectid = $project->id;
        $criterion->criterion = required_param('criterion', PARAM_ALPHANUM);
        $criterion->label = required_param('label', PARAM_TEXT);
        $criterion->weight = required_param('weight', PARAM_INT);
        $criterion->isfree = optional_param('isfree', 0, PARAM_INT);
        if ($DB->get_record('project_criterion', array('id' => $criterion->id))){
            $DB->update_record('project_criterion', $criterion);
        } else {
            $DB->insert_record('project_criterion', $criterion);
        }
    } elseif ($work == 'doconfirmdelete') {
    	$criterion = new StdClass;
        $criterion->id = required_param('item', PARAM_INT);
        $criterion->isfree = optional_param('isfree', 0, PARAM_INT);
        if ($DB->get_record('project_criterion', array('id' => $criterion->id))){
            $DB->delete_records('project_criterion', array('id' => $criterion->id));
            $DB->delete_records('project_assessment', array('projectid' => $project->id, 'criterion' => $criterion->id));
        }
    }
    if ($work == 'dodelete'){
    	$criterion = new StdClass;
        $criterion->id = required_param('item', PARAM_INT);
        $criterion->isfree = optional_param('isfree', 0, PARAM_INT);
        echo $OUTPUT->heading(get_string('confirmdeletecriteria', 'project')); 
        echo $OUTPUT->box(get_string('criteriadeleteadvice', 'project'), 'center', '70%');
    ?>
    <script type="text/javascript">
    function senddata(cmd){
        document.confirmdeleteform.work.value="do" + cmd;
        document.confirmdeleteform.submit();
    }
    function cancel(){
        document.confirmdeleteform.submit();
    }
    </script>
    <center>
    <form name="confirmdeleteform" method="get" action="view.php">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="" />
    <input type="hidden" name="item" value="<?php p($criterion->id) ?>" />
    <input type="hidden" name="isfree" value="<?php p($criterion->isfree) ?>" />
    <input type="button" name="go_btn" value="<?php print_string('continue') ?>" onclick="senddata('confirmdelete')" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
    </form>
    </center>
    <?php

/// Criteria update form ***************************************************

    } else {
        $freeCriteria = $DB->get_records_select('project_criterion', "projectid = {$project->id} AND isfree = 1");
        $criteria = $DB->get_records_select('project_criterion', "projectid = {$project->id} AND isfree = 0");
    ?>
    <center>
    <table width="80%" cellspacing="10" style="padding : 10px">
        <tr>
            <td valign="top">
                <?php 
                echo $OUTPUT->heading(get_string('freecriteriaset', 'project') . ' ' . $OUTPUT->help_icon('freecriteriaset', 'project', false)) 
                ?>
                <script type="text/javascript">
                //<![CDATA[
                function senddatafree(){
                    if (document.forms['freecriteriaform'].criterion == ''){
                        alert('<?php print_string('emptycriterion','project') ?>');
                        return;
                    }
                    document.forms['freecriteriaform'].work.value = "update";
                    document.forms['freecriteriaform'].submit();
                }
                function changefree(itemid, criterion, label, weight){
                    document.forms['freecriteriaform'].itemid.value = itemid;
                    document.forms['freecriteriaform'].criterion.value = criterion;
                    document.forms['freecriteriaform'].label.value = label;
                    document.forms['freecriteriaform'].weight.value = weight;
                    document.forms['freecriteriaform'].work.value = "update";
                }
                //]]>
                </script>
                <form name="freecriteriaform" method="post" action="view.php">
                    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
                    <input type="hidden" name="work" value="" />
                    <?php
                    $table->head = array();
                    $table->width = "100%";
                    $table->align = array('left', 'left');
                    if($freeCriteria){
                        foreach($freeCriteria as $aCriterion){
                            $links = "<a href=\"view.php?id={$cm->id}&amp;work=dodelete&amp;isfree=1&amp;item={$aCriterion->id}\"><img src=\"{$CFG->pixpath}/t/delete.gif\" title=\"".get_string('deletecriteria', 'project')."\" border=\"0\" /></a>";
                            $links .= "<a href=\"javascript:changefree('{$aCriterion->id}','{$aCriterion->criterion}','{$aCriterion->label}','{$aCriterion->weight}')\"><img src=\"{$CFG->pixpath}/t/edit.gif\" title=\"".get_string('editcriteria', 'project')."\" border=\"0\" /></a>";
                            $table->data[] = array("<b>{$aCriterion->criterion}</b> {$aCriterion->label} ( x {$aCriterion->weight})", $links );
                        }
                    }
                    echo html_writer::table($table);
                    unset($table);
                    ?>
                    <table>
                        <tr>
                            <td align="right">
                                <?php print_string('criterion', 'project') ?>&nbsp;
                            </td>
                            <td> 
                                <input type="text" name="criterion" value="" /> 
                                <?php $OUTPUT->help_icon('criterion', 'project') ?>
                            </td>
                        </tr>
                        <tr>
                            <td align="right">
                                <?php print_string('label', 'project') ?>&nbsp;
                            </td>
                            <td> 
                                <input type="text" name="label" value="" /> 
                                <?php $OUTPUT->help_icon('label', 'project') ?>
                            </td>
                        </tr>
                        <tr>
                            <td align="right">
                                <?php print_string('weight', 'project') ?>&nbsp;
                            </td>
                            <td> 
                                <input type="text" name="weight" value="" /> 
                                <?php $OUTPUT->help_icon('weight', 'project') ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            </td>
                            <td align="right">
                                <input type="button" name="go_btn" value="<?php print_string('save', 'project') ?>" onclick="senddatafree('save')" />
                            </td>
                        </tr>
                    </table>
                    <input type="hidden" name="itemid" value="0" />
                    <input type="hidden" name="isfree" value="1" />
                </form>
            </td>
            <td valign="top">
                <?php 
                echo $OUTPUT->heading(get_string('itemcriteriaset', 'project') . ' ' . $OUTPUT->help_icon('itemcriteriaset', 'project', false)) 
                ?>
                <script type="text/javascript">
                //<![CDATA[
                function senddata(){
                    if (document.forms['criteriaform'].criterion == ''){
                        alert('<?php print_string('emptycriterion','project') ?>');
                        return;
                    }
                    document.forms['criteriaform'].work.value = "update";
                    document.forms['criteriaform'].submit();
                }
                function change(itemid, criterion, label, weight){
                    document.forms['criteriaform'].itemid.value = itemid;
                    document.forms['criteriaform'].criterion.value = criterion;
                    document.forms['criteriaform'].label.value = label;
                    document.forms['criteriaform'].weight.value = weight;
                    document.forms['criteriaform'].work.value = "update";
                }
                //]]>
                </script>
                <form name="criteriaform" method="post" action="view.php">
                    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
                    <input type="hidden" name="work" value="" />
                    <?php 
                    $table->head = array();
                    $table->width = "100%";
                    $table->align = array('left', 'left');
                    if($criteria){
                        foreach($criteria as $aCriterion){
                            $links = "<a href=\"view.php?id={$cm->id}&amp;work=dodelete&amp;isfree=0&amp;item={$aCriterion->id}\"><img src=\"{$CFG->pixpath}/t/delete.gif\" border=\"0\" /></a>";
                            $links .= "<a href=\"javascript:change('{$aCriterion->id}','{$aCriterion->criterion}','{$aCriterion->label}','{$aCriterion->weight}')\"><img src=\"{$CFG->pixpath}/t/edit.gif\" border=\"0\" /></a>";
                            $table->data[] = array("<b>{$aCriterion->criterion}</b> {$aCriterion->label} ( x {$aCriterion->weight})", $links);
                        }
                    }
                    echo html_writer::table($table);
                    unset($table);
                    ?>
                    <table>
                        <tr>
                            <td align="right">
                                <?php print_string('criterion', 'project') ?>&nbsp;
                            </td>
                            <td> 
                                <input type="text" name="criterion" value="" />
                            </td>
                        </tr>
                        <tr>
                            <td align="right">
                                <?php print_string('label', 'project') ?>&nbsp;
                            </td>
                            <td> 
                                <input type="text" name="label" value="" />
                            </td>
                        </tr>
                        <tr>
                            <td align="right">
                                <?php print_string('weight', 'project') ?>&nbsp;
                            </td>
                            <td> 
                                <input type="text" name="weight" value="" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                            </td>
                            <td align="right"> 
                                <input type="button" name="go_btn" value="<?php print_string('save', 'project') ?>" onclick="senddata('save')" />
                            </td>
                        </tr>
                    </table>
                    <input type="hidden" name="itemid" value="0" />
                    <input type="hidden" name="isfree" value="0" />
                 </form>
            </td>
        </tr>
    </table>
    </center>
<?php
}
?>
