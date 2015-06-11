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
    require_once $CFG->dirroot.'/mod/project/importlib.php';
    if (!has_capability('mod/project:imports', $context)){
        print_error(get_string('notateacher','project'));
        return;
    }
/// perform local use cases
    /******************************* exports as XML a full project description **************************/
    include_once($CFG->libdir."/uploadlib.php");

	echo $pagebuffer;

    if ($work == 'doexportall'){
        $xml = project_get_full_xml($project, $currentGroupId);
        echo $OUTPUT->heading(get_string('xmlexport', 'project'));
        $xml = str_replace('<', '&lt;', $xml);
        $xml = str_replace('>', '&gt;', $xml);
        echo $OUPTUT->box("<pre>$xml</pre>");
        echo $OUTPUT->continue_button("view.php?id={$cm->id}");    
        return;
    }
    /************************************ load an existing XSL sheet *********************************/
    if ($work == 'loadxsl'){
        $uploader = new upload_manager('xslfilter', false, false, $course->id, true, 0, true);
        $uploader->preprocess_files();
        $project->xslfilter = $uploader->get_new_filename();
        $DB->update_record('project', addslashes_recursive($project));
        if (!empty($project->xslfilter)){
            $uploader->save_files("{$course->id}/moddata/project/{$project->id}");
        }
    }
    /************************************ clears an existing XSL sheet *******************************/
    if ($work == 'clearxsl'){
        include_once "filesystemlib.php";
        $xslsheetname = $DB->get_field('project', 'xslfilter', array('id' => $project->id));    
        filesystem_delete_file("{$course->id}/moddata/project/{$project->id}/$xslsheetname");
        $DB->set_field('project', 'xslfilter', '', array('id' => $project->id));
        $project->xslfilter = '';
    }
    /************************************ load an existing CSS sheet *********************************/
    if ($work == 'loadcss'){
        $uploader = new upload_manager('cssfilter', false, false, $course->id, true, 0, true);
        $uploader->preprocess_files();
        $project->cssfilter = $uploader->get_new_filename();
        $DB->update_record('project', addslashes_recursive($project));
        if (!empty($project->cssfilter)){
            $uploader->save_files("{$course->id}/moddata/project/{$project->id}");
        }
    }
    /************************************ clears an existing CSS sheet *******************************/
    if ($work == 'clearcss'){
        include_once "filesystemlib.php";
        $csssheetname = $DB->get_field('project', 'cssfilter', array('id' => $project->id));    
        filesystem_delete_file("{$course->id}/moddata/project/{$project->id}/$csssheetname");
        $DB->set_field('project', 'cssfilter', '', array('id' => $project->id));
        $project->cssfilter = '';
    }

    if ($work == 'importdata'){
    	$entitytype = required_param('entitytype', PARAM_ALPHA);
        $uploader = new upload_manager('entityfile', true, false, $course->id, false, 0, false);
        $uploader->preprocess_files();
        $uploader->process_file_uploads($CFG->dataroot.'/tmp');
        $file = $uploader->get_new_filepath();
        $data = implode('', file($file));
        project_import_entity($project->id, $id, $data, $entitytype, $currentGroupId);
    }
/// write output view
    echo $OUTPUT->heading(get_string('importsexports', 'project'));
    echo $OUTPUT->heading(get_string('imports', 'project'), '3');
    echo $OUTPUT->box_start();
?>    
    <form name="importdata" method="post" enctype="multipart/form-data" style="display:block">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="view" value="teacher_load" />
    <input type="hidden" name="work" value="importdata" />
    <select name="entitytype" />
    	<option value="requs"><?php print_string('requirements', 'project') ?></option>
    	<option value="specs"><?php print_string('specifications', 'project') ?></option>
    	<option value="tasks"><?php print_string('tasks', 'project') ?></option>
    	<option value="deliv"><?php print_string('deliverables', 'project') ?></option>
	</select>
	<?php echo $OUTPUT->help_icon('importdata', 'project') ?>
    <input type="file" name="entityfile" />
    <input type="submit" name="go_btn" value="<?php print_string('import', 'project') ?>" />
    </form>
<?php  
    echo $OUTPUT->box_end();
    echo $OUTPUT->heading(get_string('exports', 'project'), '3');
    echo $OUTPUT->box_start();
?>
    <ul>
    <li><a href="?work=doexportall&amp;id=<?php p($cm->id) ?>"><?php print_string('exportallforcurrentgroup', 'project') ?></a></li>
    <?php
    if (has_capability('mod/project:imports', $context)){
    ?>
    <li><a href="Javascript:document.forms['export'].submit()"><?php print_string('loadcustomxslsheet', 'project') ?></a>
    <form name="export" method="post" enctype="multipart/form-data" style="display:inline">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="view" value="teacher_load" />
    <input type="hidden" name="work" value="loadxsl" />
    <?php
        if (@$project->xslfilter){
            echo '('.get_string('xslloaded', 'project').": {$project->xslfilter}) ";
        }
        else{
            echo '('.get_string('xslloaded', 'project').': '.get_string('default', 'project').') ';
        }
    ?>
    <input type="file" name="xslfilter" />
    </form>
    <a href="view.php?id=<?php p($cm->id)?>&amp;work=clearxsl"><?php print_string('clearcustomxslsheet', 'project') ?></a>
    </li>
    <?php
    }
    ?>
    <?php
    if (has_capability('mod/project:imports', $context)){
    ?>
    <li><a href="Javascript:document.forms['exportcss'].submit()"><?php print_string('loadcustomcsssheet', 'project') ?></a>
    <form name="exportcss" method="post" enctype="multipart/form-data" style="display:inline">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="view" value="teacher_load" />
    <input type="hidden" name="work" value="loadcss" />
    <?php
        if (@$project->cssfilter){
            echo '('.get_string('cssloaded', 'project').": {$project->cssfilter}) ";
        }
        else{
            echo '('.get_string('cssloaded', 'project').': '.get_string('default', 'project').') ';
        }
    ?>
    <input type="file" name="cssfilter" />
    </form>
    <a href="view.php?id=<?php p($cm->id)?>&amp;work=clearcss"><?php print_string('clearcustomcsssheet', 'project') ?></a>
    </li>
    <?php
    }
    ?>
    <li><a href="xmlview.php?id=<?php p($cm->id) ?>" target="_blank"><?php print_string('makedocument', 'project') ?></a></li>
    </ul>
    <?php
    echo $OUTPUT->box_end();
?>
