<?php

/**
* This file keeps track of upgrades to 
* the project module
*
* Sometimes, changes between versions involve
* alterations to database structures and other
* major things that may break installations.
*
* The commands in here will all be database-neutral,
* using the functions defined in lib/ddllib.php
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
* The upgrade function in this file will attempt
* to perform all the necessary actions to upgrade
* your older installtion to the current version.
*
* If there's something it cannot do itself, it
* will tell you what you need to do.
*/
function xmldb_project_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;
	
	$dbman = $DB->get_manager();
    if ($oldversion < 2013100900) {

        // Define field projectconfidential to be added to project.
        $table = new xmldb_table('project');
		//ajout dans table project => des projets
		
		//champ projectconfidential
        $field = new xmldb_field('projectconfidential', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'typeprojet');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		//champ introimg
        $field = new xmldb_field('introimg', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'projectconfidential');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		//champ commanditaire
        $field = new xmldb_field('commanditaire', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null, 'introimg');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		//champ projectgrpid
        $field = new xmldb_field('projectgrpid', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'commanditaire');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		//champ etat
        $field = new xmldb_field('etat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'projectgrpid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
        // Define field statut to be added to project_milestone.
        $table = new xmldb_table('project_milestone');
		//ajout dans table étapes
		
		//champ statut
        $field = new xmldb_field('statut', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'deadlineenable');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		//champ numversion
        $field = new xmldb_field('numversion', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'statut');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		 // Define field typeelm to be added to project_deliverable.
        $table = new xmldb_table('project_deliverable');
		//ajout dans table deliverable
		
		//champ type d'element (ressource ou livrable)
        $field = new xmldb_field('typeelm', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'url');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		//champ commentaire
        $field = new xmldb_field('commentaire', XMLDB_TYPE_TEXT, null, null, null, null, null, 'descriptionformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		//champ commentaireformat
        $field = new xmldb_field('commentaireformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, 'commentaire');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
        // Define table project_messages to be created.
        $table = new xmldb_table('project_messages');
        // Adding fields to table project_messages.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('parent', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('ordering', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('projectid', XMLDB_TYPE_INTEGER, '6', null, null, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('modified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('lastuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('abstract', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        // Adding keys to table project_messages.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // Conditionally launch create table for project_messages.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
		
		//champ messageformat
        $field = new xmldb_field('messageformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, 'message');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
        // project savepoint reached.
        upgrade_mod_savepoint(true, 2013100900, 'project');
    }

    $result = true;

	/// Moodle 1.9 break

    return $result;
}

?>