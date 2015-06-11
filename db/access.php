<?php
//
// Capability definitions for the project module.
//
// The capabilities are loaded into the database table when the module is
// installed or updated. Whenever the capability definitions are updated,
// the module version number should be bumped up.
//
// The system has four possible values for a capability:
// CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
//
//
// CAPABILITY NAMING CONVENTION
//
// It is important that capability names are unique. The naming convention
// for capabilities that are specific to modules and blocks is as follows:
//   [mod/block]/<component_name>:<capabilityname>
//
// component_name should be the same as the directory name of the mod or block.
//
// Core moodle capabilities are defined thus:
//    moodle/<capabilityclass>:<capabilityname>
//
// Examples: mod/forum:viewpost
//           block/recent_activity:view
//           moodle/site:deleteuser
//
// The variable name for the capability definitions array follows the format
//   $<componenttype>_<component_name>_capabilities
//
// For the core capabilities, the variable is $moodle_capabilities.

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

$capabilities = array(

	//PROJET////////////////////////////////////////////////////

    //create & edit a project.
	'mod/project:addinstance' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_COURSE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
    //make a project blueprint. deprecated
	'mod/project:addtypeinstance' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
	//access the project
	'mod/project:view' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_COURSE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'teacher' => CAP_ALLOW,
			'student' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
	//archivate a project
	'mod/project:archive' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	//copy handlling. unused actually
	'mod/project:copy' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	//MILESTONES////////////////////////////////////////////////////

	// create, see & edit milestones. note that student don't have access to the edit menu so we should be fine
	'mod/project:changemilestone' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'student' => CAP_ALLOW,
			'teacher' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
    //validate a milestone
	'mod/project:validatemilestone' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'student' => CAP_PROHIBIT,
			'teacher' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
    //ask for a milestone validation
	'mod/project:askvalidatemilestone' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'student' => CAP_ALLOW,
			'teacher' => CAP_PROHIBIT,
			'editingteacher' => CAP_PROHIBIT,
			'manager' => CAP_PROHIBIT
			)
		),
	//DELIVERABLES AND RESSOURCES ////////////////////////////////////

    //create & edit deliverables
	'mod/project:editdeliverables' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
	//download a deliverable. teacher (aka company people) should not be able to do that for various reasons
	'mod/project:downloaddeliverable' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'student' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'teacher' => CAP_PREVENT,
			'manager' => CAP_ALLOW
			)
		),
    //create & edit ressources
	'mod/project:editressources' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
	
	//access to a "forum" inside the project.
	'mod/project:communicate' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'teacher' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'student' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
	// become noted. i wonder why editing teacher can be noted ...
	'mod/project:becomennoted' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'student' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	
	//note someone
	'mod/project:note' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),



	//////OTHERS, PROBABLY NOT IMPLEMENTED YET

	//cvs handling. unused
	'mod/project:cvs' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	//criterias handling. unused
	'mod/project:criteria' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	//import/export CSS XSL handling. unused
	'mod/project:imports' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	//tasks handling.
	'mod/project:changetasks' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'student' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
	//get task assigned to you.
	'mod/project:beassignedtasks' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'student' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
	

	//todo ? seem it's used to display the full name if you have the right ... i'll add this to everyone by default
	'mod/project:viewfullnames' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'teacher' => CAP_ALLOW,
			'student' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		)

	);


	?>
