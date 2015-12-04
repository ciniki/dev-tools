#!/opt/local/bin/php
<?php

//
// Description
// -----------
// This script will create the files required for a new module.  This must be run in
// the modules public directory.
//
//
if( !isset($argv[1]) || !isset($argv[2]) || !isset($argv[3]) || !isset($argv[4]) || !isset($argv[5]) ) {
    usage();
    exit;
}

$package = $argv[1];
$module = $argv[2];
$object = $argv[3];
$object_id = $argv[4];
$cur_code = $argv[5];

print "\n";

//
// Load the objects
//
require('../private/objects.php');
$fn = "{$package}_{$module}_objects";
$rc = $fn(array());
$objects = $rc['objects'];

if( !isset($objects[strtolower($object)]) ) {
    print "Missing object definition.\n";
    exit;
}

$object_def = $objects[strtolower($object)];

generate_add();
generate_delete();
generate_get();
generate_history();
generate_list();
generate_update();

print "done\n";

exit;

//
// Print the usage of the script
//
function usage() {
    print "mod_init.php <package> <module> <object> <object_id> <error_code>\n\n";
}

//
// objectAdd.php
//
function generate_add() {
    global $package;
    global $module;
    global $object;
    global $object_id;
    global $object_def;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// -----------\n"
        . "// This method will add a new " . strtolower($object_def['name']) . " for the business.\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "// api_key:\n"
        . "// auth_token:\n"
        . "// business_id:        The ID of the business to add the {$object_def['name']} to.\n"
        . "//\n"
        . "// Returns\n"
        . "// -------\n"
        . "// <rsp stat=\"ok\" id=\"42\">\n"
        . "//\n"
        . "function {$package}_{$module}_{$object}Add(&\$ciniki) {\n"
        . "    //\n"
        . "    // Find all the required and optional arguments\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'prepareArgs');\n"
        . "    \$rc = ciniki_core_prepareArgs(\$ciniki, 'no', array(\n"
        . "        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),\n"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= "        '$field_id'=>array('required'=>'" . (isset($field['default'])?'no':'yes') . "', 'blank'=>'no', 'name'=>'{$field['name']}'),\n";
    }
	$file .= ""
        . "        ));\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \$args = \$rc['args'];\n"
        . "\n"
        . "    //\n"
        . "    // Check access to business_id as owner\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, '{$package}', '{$module}', 'private', 'checkAccess');\n"
        . "    \$rc = {$package}_{$module}_checkAccess(\$ciniki, \$args['business_id'], '{$package}.{$module}.{$object}Add');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Start transaction\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');\n"
        . "    \$rc = ciniki_core_dbTransactionStart(\$ciniki, '{$package}.{$module}');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Add the " . strtolower($object_def['name']) . " to the database\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'objectAdd');\n"
        . "    \$rc = ciniki_core_objectAdd(\$ciniki, \$args['business_id'], '{$package}.{$module}." . strtolower($object) . "', \$args, 0x04);\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        ciniki_core_dbTransactionRollback(\$ciniki, '{$package}.{$module}');\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \${$object_id} = \$rc['id'];\n"
        . "\n"
        . "    //\n"
        . "    // Commit the transaction\n"
        . "    //\n"
        . "    \$rc = ciniki_core_dbTransactionCommit(\$ciniki, '{$package}.{$module}');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Update the last_change date in the business modules\n"
        . "    // Ignore the result, as we don't want to stop user updates if this fails.\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');\n"
        . "    ciniki_businesses_updateModuleChangeDate(\$ciniki, \$args['business_id'], '{$package}', '{$module}');\n"
        . "\n"
        . "    return array('stat'=>'ok', 'id'=>\${$object_id});\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = $object . 'Add.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectDelete.php
//
function generate_delete() {
    global $package;
    global $module;
    global $object;
    global $object_id;
    global $object_def;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// -----------\n"
        . "// This method will delete an " . strtolower($object_def['name']) . ".\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "// api_key:\n"
        . "// auth_token:\n"
        . "// business_id:            The ID of the business the " . strtolower($object_def['name']) . " is attached to.\n"
        . "// {$object_id}:            The ID of the " . strtolower($object_def['name']) . " to be removed.\n"
        . "//\n"
        . "// Returns\n"
        . "// -------\n"
        . "// <rsp stat=\"ok\">\n"
        . "//\n"
        . "function {$package}_{$module}_{$object}Delete(&\$ciniki) {\n"
        . "    //\n"
        . "    // Find all the required and optional arguments\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'prepareArgs');\n"
        . "    \$rc = ciniki_core_prepareArgs(\$ciniki, 'no', array(\n"
        . "        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),\n"
        . "        '{$object_id}'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'{$object_def['name']}'),\n"
        . "        ));\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \$args = \$rc['args'];\n"
        . "\n"
        . "    //\n"
        . "    // Check access to business_id as owner\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', '{$module}', 'private', 'checkAccess');\n"
        . "    \$rc = {$package}_{$module}_checkAccess(\$ciniki, \$args['business_id'], 'ciniki.{$module}.{$object}Delete');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Get the current settings for the " . strtolower($object_def['name']) . "\n"
        . "    //\n"
        . "    \$strsql = \"SELECT id, uuid \"\n"
        . "        . \"FROM {$object_def['table']} \"\n"
        . "        . \"WHERE business_id = '\" . ciniki_core_dbQuote(\$ciniki, \$args['business_id']) . \"' \"\n"
        . "        . \"AND id = '\" . ciniki_core_dbQuote(\$ciniki, \$args['{$object_id}']) . \"' \"\n"
        . "        . \"\";\n"
        . "    \$rc = ciniki_core_dbHashQuery(\$ciniki, \$strsql, 'ciniki.{$module}', '{$object_def['o_name']}');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    if( !isset(\$rc['{$object_def['o_name']}']) ) {\n"
        . "        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2701', 'msg'=>'Airlock does not exist.'));\n"
        . "    }\n"
        . "    \${$object_def['o_name']} = \$rc['{$object_def['o_name']}'];\n"
        . "\n"
        . "    //\n"
        . "    // Start transaction\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbDelete');\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'objectDelete');\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');\n"
        . "    \$rc = ciniki_core_dbTransactionStart(\$ciniki, '{$package}.{$module}');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Remove the {$object_def['o_name']}\n"
        . "    //\n"
        . "    \$rc = ciniki_core_objectDelete(\$ciniki, \$args['business_id'], '{$package}.{$module}." . strtolower($object) . "',\n"
        . "        \$args['{$object_id}'], \${$object_def['o_name']}['uuid'], 0x04);\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        ciniki_core_dbTransactionRollback(\$ciniki, '{$package}.{$module}');\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Commit the transaction\n"
        . "    //\n"
        . "    \$rc = ciniki_core_dbTransactionCommit(\$ciniki, '{$package}.{$module}');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Update the last_change date in the business modules\n"
        . "    // Ignore the result, as we don't want to stop user updates if this fails.\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');\n"
        . "    ciniki_businesses_updateModuleChangeDate(\$ciniki, \$args['business_id'], '{$package}', '{$module}');\n"
        . "\n"
        . "    return array('stat'=>'ok');\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = $object . 'Delete.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectGet.php
//
function generate_get() {
    global $package;
    global $module;
    global $object;
    global $object_id;
    global $object_def;
    global $cur_code;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// ===========\n"
        . "// This method will return all the information about an " . strtolower($object_def['name']) . ".\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "// api_key:\n"
        . "// auth_token:\n"
        . "// business_id:         The ID of the business the " . strtolower($object_def['name']) . " is attached to.\n"
        . "// {$object_id}:          The ID of the " . strtolower($object_def['name']) . " to get the details for.\n"
        . "//\n"
        . "// Returns\n"
        . "// -------\n"
        . "//\n"
        . "function {$package}_{$module}_{$object}Get(\$ciniki) {\n"
        . "    //\n"
        . "    // Find all the required and optional arguments\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'prepareArgs');\n"
        . "    \$rc = ciniki_core_prepareArgs(\$ciniki, 'no', array(\n"
        . "        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),\n"
        . "        '{$object_id}'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'{$object_def['name']}'),\n"
        . "        ));\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \$args = \$rc['args'];\n"
        . "\n"
        . "    //\n"
        . "    // Make sure this module is activated, and\n"
        . "    // check permission to run this function for this business\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, '{$package}', '{$module}', 'private', 'checkAccess');\n"
        . "    \$rc = {$package}_{$module}_checkAccess(\$ciniki, \$args['business_id'], '{$package}.{$module}.{$object}Get');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Load business settings\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');\n"
        . "    \$rc = ciniki_businesses_intlSettings(\$ciniki, \$args['business_id']);\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \$intl_timezone = \$rc['settings']['intl-default-timezone'];\n"
        . "    \$intl_currency_fmt = numfmt_create(\$rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);\n"
        . "    \$intl_currency = \$rc['settings']['intl-default-currency'];\n"
        . "\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');\n"
        . "    \$datetime_format = ciniki_users_datetimeFormat(\$ciniki, 'php');\n"
        . "\n"
        . "    //\n"
        . "    // Return default for new {$object_def['name']}\n"
        . "    //\n"
        . "    if( \$args['{$object_id}'] == 0 ) {\n"
        . "        \${$object_def['o_name']} = array('id'=>0,\n"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= "        '$field_id'=>'" . (isset($field['default'])?$field['default']:'') . "',\n";
    }
	$file .= ""
        . "            );\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Get the details for an existing {$object_def['name']}\n"
        . "    //\n"
        . "    else {\n"
        . "        \$strsql = \"SELECT {$object_def['table']}.id"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= ", \"\n"
            . "        . \"{$object_def['table']}.$field_id, \"\n";
    }
	$file .= " \"\n"
        . "            . \"FROM {$object_def['table']} \"\n"
        . "            . \"WHERE {$object_def['table']}.business_id = '\" . ciniki_core_dbQuote(\$ciniki, \$args['business_id']) . \"' \"\n"
        . "            . \"AND {$object_def['table']}.id = '\" . ciniki_core_dbQuote(\$ciniki, \$args['{$object_id}']) . \"' \"\n"
        . "            . \"\";\n"
        . "        ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');\n"
        . "        \$rc = ciniki_core_dbHashQuery(\$ciniki, \$strsql, '{$package}.{$module}', '{$object_def['o_name']}');\n"
        . "        if( \$rc['stat'] != 'ok' ) {\n"
        . "            return array('stat'=>'fail', 'err'=>array('pkg'=>'{$package}', 'code'=>'" . $cur_code++ . "', 'msg'=>'{$object_def['name']} not found', 'err'=>\$rc['err']));\n"
        . "        }\n"
        . "        if( !isset(\$rc['{$object_def['o_name']}']) ) {\n"
        . "            return array('stat'=>'fail', 'err'=>array('pkg'=>'{$package}', 'code'=>'" . $cur_code++ . "', 'msg'=>'Unable to find {$object_def['name']}'));\n"
        . "        }\n"
        . "        \${$object_def['o_name']} = \$rc['{$object_def['o_name']}'];\n"
        . "    }\n"
        . "\n"
        . "    return array('stat'=>'ok', '{$object_def['o_name']}'=>\${$object_def['o_name']});\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = $object . 'Get.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectHistory.php
//
function generate_history() {
    global $package;
    global $module;
    global $object;
    global $object_id;
    global $object_def;

    $file = ""
		. "<?php\n"
		. "//\n"
		. "// Description\n"
		. "// -----------\n"
		. "// This method will return the list of actions that were applied to an element of an " . strtolower($object_def['name']) . ".\n"
		. "// This method is typically used by the UI to display a list of changes that have occured\n"
		. "// on an element through time. This information can be used to revert elements to a previous value.\n"
		. "//\n"
		. "// Arguments\n"
		. "// ---------\n"
		. "// api_key:\n"
		. "// auth_token:\n"
		. "// business_id:         The ID of the business to get the details for.\n"
		. "// {$object_id}:          The ID of the " . strtolower($object_def['name']) . " to get the history for.\n"
		. "// field:                   The field to get the history for.\n"
		. "//\n"
		. "// Returns\n"
		. "// -------\n"
		. "// <history>\n"
		. "// <action user_id=\"2\" date=\"May 12, 2012 10:54 PM\" value=\"{$object_def['name']} Name\" age=\"2 months\" user_display_name=\"Andrew\" />\n"
		. "// ...\n"
		. "// </history>\n"
		. "//\n"
		. "function {$package}_{$module}_{$object}History(\$ciniki) {\n"
		. "    //\n"
		. "    // Find all the required and optional arguments\n"
		. "    //\n"
		. "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'prepareArgs');\n"
		. "    \$rc = ciniki_core_prepareArgs(\$ciniki, 'no', array(\n"
		. "        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),\n"
		. "        '{$object_id}'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'{$object_def['name']}'),\n"
		. "        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'),\n"
		. "        ));\n"
		. "    if( \$rc['stat'] != 'ok' ) {\n"
		. "        return \$rc;\n"
		. "    }\n"
		. "    \$args = \$rc['args'];\n"
		. "\n"
		. "    //\n"
		. "    // Check access to business_id as owner, or sys admin\n"
		. "    //\n"
		. "    ciniki_core_loadMethod(\$ciniki, '{$package}', '{$module}', 'private', 'checkAccess');\n"
		. "    \$rc = {$package}_{$module}_checkAccess(\$ciniki, \$args['business_id'], '{$package}.{$module}.{$object}History');\n"
		. "    if( \$rc['stat'] != 'ok' ) {\n"
		. "        return \$rc;\n"
		. "    }\n"
		. "\n"
		. "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');\n"
		. "    return ciniki_core_dbGetModuleHistory(\$ciniki, '{$package}.{$module}', '{$package}_{$module}_history', \$args['business_id'], '{$object_def['table']}', \$args['{$object_id}'], \$args['field']);\n"
		. "}\n"
		. "?>\n"
        . "";

    $filename = $object . 'History.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectList.php
//
function generate_list() {
    global $package;
    global $module;
    global $object;
    global $object_id;
    global $object_def;

    $file = ""
		. "<?php\n"
		. "//\n"
		. "// Description\n"
		. "// -----------\n"
		. "// This method will return the list of {$object_def['name']}s for a business.\n"
		. "//\n"
		. "// Arguments\n"
		. "// ---------\n"
		. "// api_key:\n"
		. "// auth_token:\n"
		. "// business_id:        The ID of the business to get {$object_def['name']} for.\n"
		. "//\n"
		. "// Returns\n"
		. "// -------\n"
		. "//\n"
		. "function {$package}_{$module}_{$object}List(\$ciniki) {\n"
		. "    //\n"
		. "    // Find all the required and optional arguments\n"
		. "    //\n"
		. "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'prepareArgs');\n"
		. "    \$rc = ciniki_core_prepareArgs(\$ciniki, 'no', array(\n"
		. "        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),\n"
		. "        ));\n"
		. "    if( \$rc['stat'] != 'ok' ) {\n"
		. "        return \$rc;\n"
		. "    }\n"
		. "    \$args = \$rc['args'];\n"
		. "\n"
		. "    //\n"
		. "    // Check access to business_id as owner, or sys admin.\n"
		. "    //\n"
		. "    ciniki_core_loadMethod(\$ciniki, '{$package}', '{$module}', 'private', 'checkAccess');\n"
		. "    \$rc = {$package}_{$module}_checkAccess(\$ciniki, \$args['business_id'], '{$package}.{$module}.{$object}');\n"
		. "    if( \$rc['stat'] != 'ok' ) {\n"
		. "        return \$rc;\n"
		. "    }\n"
        . "\n"
		. "    //\n"
		. "    // Get the list of {$object_def['o_container']}\n"
		. "    //\n"
		. "    \$strsql = \"SELECT {$object_def['table']}.id"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= ", \"\n"
            . "        . \"{$object_def['table']}.$field_id, \"\n";
    }
	$file .= " \"\n"
        . "        . \"FROM {$object_def['table']} \"\n"
		. "        . \"WHERE {$object_def['table']}.business_id = '\" . ciniki_core_dbQuote(\$ciniki, \$args['business_id']) . \"' \"\n"
		. "        . \"\";\n"
		. "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');\n"
		. "    \$rc = ciniki_core_dbHashQueryArrayTree(\$ciniki, \$strsql, '{$package}.{$module}', array(\n"
		. "        array('container'=>'" . strtolower($object_def['o_container']) . "', 'fname'=>'id', \n"
		. "            'fields'=>array('id'"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= ", '$field_id'";
    }
	$file .= ""
        . ")),\n"
		. "        ));\n"
		. "    if( \$rc['stat'] != 'ok' ) {\n"
		. "        return \$rc;\n"
		. "    }\n"
		. "    if( !isset(\$rc['{$object_def['o_container']}']) ) {\n"
		. "        \${$object_def['o_container']} = \$rc['{$object_def['o_container']}'];\n"
		. "    } else {\n"
		. "        \${$object_def['o_container']} = array();\n"
		. "    }\n"
		. "\n"
		. "    return array('stat'=>'ok', '{$object_def['o_container']}'=>\${$object_def['o_container']});\n"
		. "}\n"
		. "?>\n"
        . "";

    $filename = $object . 'List.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectUpdate.php
//
function generate_update() {
    global $package;
    global $module;
    global $object;
    global $object_id;
    global $object_def;

    $file = ""
		. "<?php\n"
		. "//\n"
		. "// Description\n"
		. "// ===========\n"
		. "//\n"
		. "// Arguments\n"
		. "// ---------\n"
		. "//\n"
		. "// Returns\n"
		. "// -------\n"
		. "// <rsp stat='ok' />\n"
		. "//\n"
		. "function {$package}_{$module}_{$object}Update(&\$ciniki) {\n"
		. "    //\n"
		. "    // Find all the required and optional arguments\n"
		. "    //\n"
		. "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'prepareArgs');\n"
		. "    \$rc = ciniki_core_prepareArgs(\$ciniki, 'no', array(\n"
		. "        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),\n"
		. "        '{$object_id}'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'{$object_def['name']}'),\n"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= "        '$field_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'{$field['name']}'),\n";
    }
	$file .= ""
		. "        ));\n"
		. "    if( \$rc['stat'] != 'ok' ) {\n"
		. "        return \$rc;\n"
		. "    }\n"
		. "    \$args = \$rc['args'];\n"
		. "\n"
		. "    //\n"
		. "    // Make sure this module is activated, and\n"
		. "    // check permission to run this function for this business\n"
		. "    //\n"
		. "    ciniki_core_loadMethod(\$ciniki, '{$package}', '{$module}', 'private', 'checkAccess');\n"
		. "    \$rc = {$package}_{$module}_checkAccess(\$ciniki, \$args['business_id'], '{$package}.{$module}.{$object}Update');\n"
		. "    if( \$rc['stat'] != 'ok' ) {\n"
		. "        return \$rc;\n"
		. "    }\n"
		. "\n"
		. "    //\n"
		. "    // Start transaction\n"
		. "    //\n"
		. "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');\n"
		. "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');\n"
		. "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');\n"
		. "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');\n"
		. "    \$rc = ciniki_core_dbTransactionStart(\$ciniki, '{$package}.{$module}');\n"
		. "    if( \$rc['stat'] != 'ok' ) {\n"
		. "        return \$rc;\n"
		. "    }\n"
		. "\n"
		. "    //\n"
		. "    // Update the {$object_def['name']} in the database\n"
		. "    //\n"
		. "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'objectUpdate');\n"
		. "    \$rc = ciniki_core_objectUpdate(\$ciniki, \$args['business_id'], '{$package}.{$module}." . strtolower($object) . "', \$args['{$object_id}'], \$args, 0x04);\n"
		. "    if( \$rc['stat'] != 'ok' ) {\n"
		. "        ciniki_core_dbTransactionRollback(\$ciniki, '{$package}.{$module}');\n"
		. "        return \$rc;\n"
		. "    }\n"
		. "\n"
		. "    //\n"
		. "    // Commit the transaction\n"
		. "    //\n"
		. "    \$rc = ciniki_core_dbTransactionCommit(\$ciniki, '{$package}.{$module}');\n"
		. "    if( \$rc['stat'] != 'ok' ) {\n"
		. "        return \$rc;\n"
		. "    }\n"
		. "\n"
		. "    //\n"
		. "    // Update the last_change date in the business modules\n"
		. "    // Ignore the result, as we don't want to stop user updates if this fails.\n"
		. "    //\n"
		. "    ciniki_core_loadMethod(\$ciniki, '{$package}', 'businesses', 'private', 'updateModuleChangeDate');\n"
		. "    ciniki_businesses_updateModuleChangeDate(\$ciniki, \$args['business_id'], '{$package}', '{$module}');\n"
		. "\n"
		. "    return array('stat'=>'ok');\n"
		. "}\n"
		. "?>\n"
        . "";

    $filename = $object . 'Update.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

?>
