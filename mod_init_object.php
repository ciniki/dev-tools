#!/opt/local/bin/php
<?php

//
// Description
// -----------
// This script will create the files required for a new module.  This must be run in
// the modules public directory.
//
//
$options = array(
    'detailpanel'=>'no',
    'nextprev'=>'yes',
    'initializeui'=>'no',
    );
$package = 'ciniki';
$module = basename(getcwd());

//
// Get the max error code
//
$path = "./";
$results = `grep -H "'code'=>" $path/*/*.php`;
$lines = explode("\n", $results);
$max = 0;
foreach($lines as $lid => $line) {
    $line = preg_replace("#^" . $path . "/#", '', $line);
    if( preg_match("/^([^:]*):.*'code'=>'([^\.']*)\.([^\.']*)\.([^\.']*)'/", $line, $matches) ) {
        if( $matches[4] > $max ) {
            $max = $matches[4];
        }
    }
}
$cur_code = $max+1;

$i = 1;
while( isset($argv[$i]) && $argv[$i][0] == '-' ) {
    if( $argv[$i] == '-d' ) {
        $options['detailpanel'] = 'yes';
    }
    elseif( $argv[$i] == '-i' ) {
        $options['initializeui'] = 'yes';
    }
    elseif( $argv[$i] == '-nnp' ) {
        $options['nextprev'] = 'no';
    }
    elseif( $argv[$i] == '-p' ) {
        if( !isset($argv[$i+1]) ) {
            usage();
            exit;
        }
        $package = $argv[$i+1];
        $i++;
    }
    elseif( $argv[$i] == '-m' ) {
        if( !isset($argv[$i+1]) ) {
            usage();
            exit;
        }
        $module = $argv[$i+1];
        $i++;
    }
    elseif( $argv[$i] == '-c' ) {
        if( !isset($argv[$i+1]) ) {
            usage();
            exit;
        }
        $cur_code = $argv[$i+1];
        $i++;
    }
    $i++;
}
if( !isset($argv[$i]) || !isset($argv[$i+1]) ) {
    usage();
    exit;
}

$object = $argv[$i];
$object_id = $argv[$i+1];

print "\n";

//
// Load the objects
//
require('private/objects.php');
$fn = "{$package}_{$module}_objects";
$rc = $fn(array());
$objects = $rc['objects'];

if( !isset($objects[strtolower($object)]) ) {
    print "Missing object definition.\n";
    exit;
}

$object_def = $objects[strtolower($object)];

//
// Load the database table for the object
//
$table = file_get_contents("db/" . $object_def['table'] . ".schema");
$lines = explode("\n", $table);
foreach($lines as $line) {
    if( preg_match("/^\s+([^\s]+)\s+varchar\(([0-9]+)\)/", $line, $matches) ) {
        if( isset($object_def['fields'][$matches[1]]) ) {
            $object_def['fields'][$matches[1]]['dbtype'] = 'varchar';
            $object_def['fields'][$matches[1]]['dbsize'] = $matches[2];
        }
    }
    elseif( preg_match("/^\s+([^\s]+)\s+(int|tinyint|text|date|time|datetime)\s+/", $line, $matches) ) {
        if( isset($object_def['fields'][$matches[1]]) ) {
            $object_def['fields'][$matches[1]]['dbtype'] = $matches[2];
            if( !isset($object_def['fields'][$matches[1]]['type']) ) {
                if( $matches[2] == 'date' ) {
                    $object_def['fields'][$matches[1]]['type'] = 'date';
                } elseif( $matches[2] == 'time' ) {
                    $object_def['fields'][$matches[1]]['type'] = 'time';
                } elseif( $matches[2] == 'datetime' ) {
                    $object_def['fields'][$matches[1]]['type'] = 'utcdatetime';
                }
            }
        }
    }
}
foreach($object_def['fields'] as $fid => $field) {
    if( isset($field['type']) && !isset($field['argtype']) ) {
        if( $field['type'] == 'utcdatetime' ) {
            $object_def['fields'][$fid]['argtype'] = 'datetimetoutc';
        } else {
            $object_def['fields'][$fid]['argtype'] = $field['type'];
        }
    }
}

//
// Generate the files
//
generate_add();
generate_delete();
generate_get();
generate_history();
generate_list();
generate_search();
generate_update();
generate_ui();

print "done\n";

exit;

//
// Print the usage of the script
//
function usage() {
    print "mod_init_object.php [-d] [-p <package>] [-m <module>] [-c <error code>] <object> <object_id>\n\n"
        . "    -d            - Added a display panel to UI\n"
        . "    -nnp          - Do not use nextprev lists\n"
        . "    -i            - Initialize the ui\n"
        . "    -p <package>  - The package if not ciniki\n"
        . "    -m <module>   - The package if not same as directory name\n"
        . "    -c <code>     - The error code to start errors at, otherwise looks for maximum in module\n"
        . "\n";
}

//
// objectAdd.php
//
function generate_add() {
    global $options;
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
        $file .= "        '$field_id'=>array('required'=>'" . (isset($field['default'])?'no':'yes') . "', "
            . "'blank'=>'" . (isset($field['default'])?'yes':'no') . "', "
            . (isset($field['argtype']) && $field['argtype'] != '' ? "'type'=>'{$field['argtype']}', " : '')
            . "'name'=>'{$field['name']}'),\n"
            . "";
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
        . "\n";
    if( isset($object_def['fields']['permalink']) ) {
        $file .= "    //\n"
            . "    // Setup permalink\n"
            . "    //\n"
            . "    if( !isset(\$args['permalink']) || \$args['permalink'] == '' ) {\n"
            . "        ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'makePermalink');\n"
            . "        \$args['permalink'] = ciniki_core_makePermalink(\$ciniki, \$args['name']);\n"
            . "    }\n"
            . "\n"
            . "    //\n"
            . "    // Make sure the permalink is unique\n"
            . "    //\n"
            . "    \$strsql = \"SELECT id, name, permalink \"\n"
            . "        . \"FROM {$object_def['table']} \"\n"
            . "        . \"WHERE business_id = '\" . ciniki_core_dbQuote(\$ciniki, \$args['business_id']) . \"' \"\n"
            . "        . \"AND permalink = '\" . ciniki_core_dbQuote(\$ciniki, \$args['permalink']) . \"' \"\n"
            . "        . \"\";\n"
            . "    \$rc = ciniki_core_dbHashQuery(\$ciniki, \$strsql, '{$package}.{$module}', 'item');\n"
            . "    if( \$rc['stat'] != 'ok' ) {\n"
            . "        return \$rc;\n"
            . "    }\n"
            . "    if( \$rc['num_rows'] > 0 ) {\n"
            . "        return array('stat'=>'fail', 'err'=>array('code'=>'{$package}.{$module}." . $cur_code++ . "', 'msg'=>'You already have a " . strtolower($object_def['name']) . " with that name, please choose another.'));\n"
            . "    }\n"
            . "\n";
    }
    $file .= "    //\n"
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
        . "    //\n"
        . "    // Update the web index if enabled\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'hookExec');\n"
        . "    ciniki_core_hookExec(\$ciniki, \$args['business_id'], 'ciniki', 'web', 'indexObject', array('object'=>'{$package}.{$module}.{$object}', 'object_id'=>\${$object_id}));\n"
        . "\n"
        . "    return array('stat'=>'ok', 'id'=>\${$object_id});\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = "public/" . $object . 'Add.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectDelete.php
//
function generate_delete() {
    global $options;
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
        . "    ciniki_core_loadMethod(\$ciniki, '{$package}', '{$module}', 'private', 'checkAccess');\n"
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
        . "        return array('stat'=>'fail', 'err'=>array('code'=>'{$package}.{$module}." . $cur_code++ . "', 'msg'=>'{$object_def['name']} does not exist.'));\n"
        . "    }\n"
        . "    \${$object_def['o_name']} = \$rc['{$object_def['o_name']}'];\n"
        . "\n"
        . "    //\n"
        . "    // Check for any dependencies before deleting\n"
        . "    //\n"
        . "\n"
        . "    //\n"
        . "    // Check if any modules are currently using this object\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');\n"
        . "    \$rc = ciniki_core_objectCheckUsed(\$ciniki, \$args['business_id'], '{$package}.{$module}.{$object}', \$args['{$object_id}']);\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return array('stat'=>'fail', 'err'=>array('code'=>'{$package}.{$module}." . $cur_code++ . "', 'msg'=>'Unable to check if the " . strtolower($object_def['name']) . " is still being used.', 'err'=>\$rc['err']));\n"
        . "    }\n"
        . "    if( \$rc['used'] != 'no' ) {\n"
        . "        return array('stat'=>'fail', 'err'=>array('code'=>'{$package}.{$module}." . $cur_code++ . "', 'msg'=>'The " . strtolower($object_def['name']) . " is still in use. ' . \$rc['msg']));\n"
        . "    }\n"
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

    $filename = "public/" . $object . 'Delete.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectGet.php
//
function generate_get() {
    global $options;
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
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'users', 'private', 'dateFormat');\n"
        . "    \$date_format = ciniki_users_dateFormat(\$ciniki, 'php');\n"
        . "\n"
        . "    //\n"
        . "    // Return default for new {$object_def['name']}\n"
        . "    //\n"
        . "    if( \$args['{$object_id}'] == 0 ) {\n"
        . "        \${$object_def['o_name']} = array('id'=>0,\n"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= "            '$field_id'=>'" . (isset($field['default'])?$field['default']:'') . "',\n";
    }
    $file .= ""
        . "        );\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Get the details for an existing {$object_def['name']}\n"
        . "    //\n"
        . "    else {\n"
        . "        \$strsql = \"SELECT {$object_def['table']}.id"
        . "";
    $field_list = '';
    $utctotz = '';
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= ", \"\n"
            . "            . \"{$object_def['table']}.$field_id";
        $field_list .= ($field_list != '' ? ', ' : '') . "'$field_id'";
        if( isset($field['dbtype']) && $field['dbtype'] == 'date' ) {
            $utctotz .= ($utctotz != '' ? ",\n                    " : '') . "'$field_id'=>array('timezone'=>'UTC', 'format'=>\$date_format)";
        }
    }
    if( $utctotz != '' ) {
        $utctotz = "                'utctotz'=>array($utctotz),";
    }
    $file .= " \"\n"
        . "            . \"FROM {$object_def['table']} \"\n"
        . "            . \"WHERE {$object_def['table']}.business_id = '\" . ciniki_core_dbQuote(\$ciniki, \$args['business_id']) . \"' \"\n"
        . "            . \"AND {$object_def['table']}.id = '\" . ciniki_core_dbQuote(\$ciniki, \$args['{$object_id}']) . \"' \"\n"
        . "            . \"\";\n"
        . "        ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');\n"
        . "        \$rc = ciniki_core_dbHashQueryArrayTree(\$ciniki, \$strsql, '{$package}.{$module}', array(\n"
        . "            array('container'=>'{$object_def['o_container']}', 'fname'=>'id', \n"
        . "                'fields'=>array($field_list),\n"
        . $utctotz
        . "                ),\n"
        . "            ));\n"
        . "        if( \$rc['stat'] != 'ok' ) {\n"
        . "            return array('stat'=>'fail', 'err'=>array('code'=>'{$package}.{$module}." . $cur_code++ . "', 'msg'=>'{$object_def['name']} not found', 'err'=>\$rc['err']));\n"
        . "        }\n"
        . "        if( !isset(\$rc['{$object_def['o_container']}'][0]) ) {\n"
        . "            return array('stat'=>'fail', 'err'=>array('code'=>'{$package}.{$module}." . $cur_code++ . "', 'msg'=>'Unable to find {$object_def['name']}'));\n"
        . "        }\n"
        . "        \${$object_def['o_name']} = \$rc['{$object_def['o_container']}'][0];\n"
        . "    }\n"
        . "\n"
        . "    return array('stat'=>'ok', '{$object_def['o_name']}'=>\${$object_def['o_name']});\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = "public/" . $object . 'Get.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectHistory.php
//
function generate_history() {
    global $options;
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
        . "\n";
    //
    // Check for special fields and return reformated values
    //
    foreach($object_def['fields'] as $field_id => $field) {
        if( isset($field['type']) && $field['type'] == 'date' ) {
            $file .= "    if( \$args['field'] == '{$field_id}' ) {\n"
                . "        ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');\n"
                . "        return ciniki_core_dbGetModuleHistoryReformat(\$ciniki, '{$package}.{$module}', '{$package}_{$module}_history', \$args['business_id'], '{$object_def['table']}', \$args['{$object_id}'], \$args['field'], 'date');\n"
                . "    }\n"
                . "\n";
        }
        elseif( isset($field['type']) && $field['type'] == 'currency' ) {
            $file .= "    if( \$args['field'] == '{$field_id}' ) {\n"
                . "        ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');\n"
                . "        return ciniki_core_dbGetModuleHistoryReformat(\$ciniki, '{$package}.{$module}', '{$package}_{$module}_history', \$args['business_id'], '{$object_def['table']}', \$args['{$object_id}'], \$args['field'], 'currency');\n"
                . "    }\n"
                . "\n";
        }
    }
    $file .= ""
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');\n"
        . "    return ciniki_core_dbGetModuleHistory(\$ciniki, '{$package}.{$module}', '{$package}_{$module}_history', \$args['business_id'], '{$object_def['table']}', \$args['{$object_id}'], \$args['field']);\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = "public/" . $object . 'History.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectList.php
//
function generate_list() {
    global $options;
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
        . "    \$rc = {$package}_{$module}_checkAccess(\$ciniki, \$args['business_id'], '{$package}.{$module}.{$object}List');\n"
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
        //
        // Skip images and text or long varchars
        //
        if( (isset($field['ref']) && $field['ref'] == 'ciniki.images.image')
            || (isset($field['dbtype']) && ($field['dbtype'] == 'text' || ($field['dbtype'] == 'varchar' && $field['dbsize'] > 999))) 
            ) {
            continue;
        }
        $file .= ", \"\n"
            . "        . \"{$object_def['table']}.$field_id";
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
        if( (isset($field['ref']) && $field['ref'] == 'ciniki.images.image')
            || (isset($field['dbtype']) && ($field['dbtype'] == 'text' || ($field['dbtype'] == 'varchar' && $field['dbsize'] > 999))) 
            ) {
            continue;
        }
        $file .= ", '$field_id'";
    }
    $file .= ""
        . ")),\n"
        . "        ));\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    if( isset(\$rc['{$object_def['o_container']}']) ) {\n"
        . "        \${$object_def['o_container']} = \$rc['{$object_def['o_container']}'];\n"
        . "";
    if( $options['nextprev'] == 'yes' ) {
        $file .= "        \${$object_def['o_name']}_ids = array();\n"
            . "        foreach(\${$object_def['o_container']} as \$iid => \${$object_def['o_name']}) {\n"
            . "            \${$object_def['o_name']}_ids[] = \${$object_def['o_name']}['id'];\n"
            . "        }\n"
            . "";
    } 
    $file .= "    } else {\n"
        . "        \${$object_def['o_container']} = array();\n"
        . ($options['nextprev'] == 'yes' ? "        \${$object_def['o_name']}_ids = array();\n" : "")
        . "    }\n"
        . "\n"
        . "    return array('stat'=>'ok', '{$object_def['o_container']}'=>\${$object_def['o_container']}"
            . ($options['nextprev'] == 'yes' ? ", 'nplist'=>\${$object_def['o_name']}_ids" : "")
            . ");\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = "public/" . $object . 'List.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectSearch.php
//
function generate_search() {
    global $options;
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
        . "// This method searchs for a {$object_def['name']}s for a business.\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "// api_key:\n"
        . "// auth_token:\n"
        . "// business_id:        The ID of the business to get {$object_def['name']} for.\n"
        . "// start_needle:       The search string to search for.\n"
        . "// limit:              The maximum number of entries to return.\n"
        . "//\n"
        . "// Returns\n"
        . "// -------\n"
        . "//\n"
        . "function {$package}_{$module}_{$object}Search(\$ciniki) {\n"
        . "    //\n"
        . "    // Find all the required and optional arguments\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'prepareArgs');\n"
        . "    \$rc = ciniki_core_prepareArgs(\$ciniki, 'no', array(\n"
        . "        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),\n"
        . "        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),\n"
        . "        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),\n"
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
        . "    \$rc = {$package}_{$module}_checkAccess(\$ciniki, \$args['business_id'], '{$package}.{$module}.{$object}Search');\n"
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
        //
        // Skip images and text or long varchars
        //
        if( (isset($field['ref']) && $field['ref'] == 'ciniki.images.image')
            || (isset($field['dbtype']) && ($field['dbtype'] == 'text' || ($field['dbtype'] == 'varchar' && $field['dbsize'] > 999))) 
            ) {
            continue;
        }
        $file .= ", \"\n"
            . "        . \"{$object_def['table']}.$field_id";
    }
    $file .= " \"\n"
        . "        . \"FROM {$object_def['table']} \"\n"
        . "        . \"WHERE {$object_def['table']}.business_id = '\" . ciniki_core_dbQuote(\$ciniki, \$args['business_id']) . \"' \"\n"
        . "        . \"AND (\"\n"
        . "            . \"name LIKE '\" . ciniki_core_dbQuote(\$ciniki, \$args['start_needle']) . \"%' \"\n"
        . "            . \"OR name LIKE '% \" . ciniki_core_dbQuote(\$ciniki, \$args['start_needle']) . \"%' \"\n"
        . "        . \") \"\n"
        . "        . \"\";\n"
        . "    if( isset(\$args['limit']) && is_numeric(\$args['limit']) && \$args['limit'] > 0 ) {\n"
        . "        \$strsql .= \"LIMIT \" . ciniki_core_dbQuote(\$ciniki, \$args['limit']) . \" \";\n"
        . "    } else {\n"
        . "        \$strsql .= \"LIMIT 25 \";\n"
        . "    }\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');\n"
        . "    \$rc = ciniki_core_dbHashQueryArrayTree(\$ciniki, \$strsql, '{$package}.{$module}', array(\n"
        . "        array('container'=>'" . strtolower($object_def['o_container']) . "', 'fname'=>'id', \n"
        . "            'fields'=>array('id'"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        if( (isset($field['ref']) && $field['ref'] == 'ciniki.images.image')
            || (isset($field['dbtype']) && ($field['dbtype'] == 'text' || ($field['dbtype'] == 'varchar' && $field['dbsize'] > 999))) 
            ) {
            continue;
        }
        $file .= ", '$field_id'";
    }
    $file .= ""
        . ")),\n"
        . "        ));\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    if( isset(\$rc['{$object_def['o_container']}']) ) {\n"
        . "        \${$object_def['o_container']} = \$rc['{$object_def['o_container']}'];\n"
        . "";
    if( $options['nextprev'] == 'yes' ) {
        $file .= "        \${$object_def['o_name']}_ids = array();\n"
            . "        foreach(\${$object_def['o_container']} as \$iid => \${$object_def['o_name']}) {\n"
            . "            \${$object_def['o_name']}_ids[] = \${$object_def['o_name']}['id'];\n"
            . "        }\n"
            . "";
    } 
    $file .= "    } else {\n"
        . "        \${$object_def['o_container']} = array();\n"
        . ($options['nextprev'] == 'yes' ? "        \${$object_def['o_name']}_ids = array();\n" : "")
        . "    }\n"
        . "\n"
        . "    return array('stat'=>'ok', '{$object_def['o_container']}'=>\${$object_def['o_container']}"
            . ($options['nextprev'] == 'yes' ? ", 'nplist'=>\${$object_def['o_name']}_ids" : "")
            . ");\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = "public/" . $object . 'Search.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectUpdate.php
//
function generate_update() {
    global $options;
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
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "//\n"
        . "// Returns\n"
        . "// -------\n"
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
        $file .= "        '$field_id'=>array('required'=>'no', "
            . "'blank'=>'" . (isset($field['default'])?'yes':'no') . "', "
            . (isset($field['argtype']) && $field['argtype'] != '' ? "'type'=>'{$field['argtype']}', " : '')
            . "'name'=>'{$field['name']}'),\n";
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
        . "\n";
    if( isset($object_def['fields']['permalink']) && isset($object_def['fields']['name']) ) {
        $file .= ""
            . "    if( isset(\$args['name']) ) {\n"
            . "        ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'makePermalink');\n"
            . "        \$args['permalink'] = ciniki_core_makePermalink(\$ciniki, \$args['name']);\n"
            . "        //\n"
            . "        // Make sure the permalink is unique\n"
            . "        //\n"
            . "        \$strsql = \"SELECT id, name, permalink \"\n"
            . "            . \"FROM {$object_def['table']} \"\n"
            . "            . \"WHERE business_id = '\" . ciniki_core_dbQuote(\$ciniki, \$args['business_id']) . \"' \"\n"
            . "            . \"AND permalink = '\" . ciniki_core_dbQuote(\$ciniki, \$args['permalink']) . \"' \"\n"
            . "            . \"AND id <> '\" . ciniki_core_dbQuote(\$ciniki, \$args['{$object_id}']) . \"' \"\n"
            . "            . \"\";\n"
            . "        \$rc = ciniki_core_dbHashQuery(\$ciniki, \$strsql, '{$package}.{$module}', 'item');\n"
            . "        if( \$rc['stat'] != 'ok' ) {\n"
            . "            return \$rc;\n"
            . "        }\n"
            . "        if( \$rc['num_rows'] > 0 ) {\n"
            . "            return array('stat'=>'fail', 'err'=>array('code'=>'{$package}.{$module}." . $cur_code++ . "', 'msg'=>'You already have an " . strtolower($object_def['name']) . " with this name, please choose another.'));\n"
            . "        }\n"
            . "    }\n"
            . "\n";
    }
    if( isset($object_def['fields']['permalink']) && isset($object_def['fields']['title']) ) {
        $file .= ""
            . "    if( isset(\$args['title']) ) {\n"
            . "         ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'makePermalink');\n"
            . "        \$args['permalink'] = ciniki_core_makePermalink(\$ciniki, \$args['title']);\n"
            . "        //\n"
            . "        // Make sure the permalink is unique\n"
            . "        //\n"
            . "        $strsql = \"SELECT id, title, permalink \"\n"
            . "            . \"FROM {$object_def['table']} \"\n"
            . "             . \"WHERE business_id = '\" . ciniki_core_dbQuote(\$ciniki, \$args['business_id']) . \"' \"\n"
            . "             . \"AND permalink = '\" . ciniki_core_dbQuote(\$ciniki, \$args['permalink']) . \"' \"\n"
            . "            . \"AND id <> '\" . ciniki_core_dbQuote(\$ciniki, \$args['{$object_id}']) . \"' \"\n"
            . "             . \"\";\n"
            . "        \$rc = ciniki_core_dbHashQuery(\$ciniki, \$strsql, '{$package}.{$module}', 'item');\n"
            . "        if( \$rc['stat'] != 'ok' ) {\n"
            . "            return \$rc;\n"
            . "          }\n"
            . "        if( \$rc['num_rows'] > 0 ) {\n"
            . "             return array('stat'=>'fail', 'err'=>array('code'=>'{$package}.{$module}." . $cur_code++ . "', 'msg'=>'You already have an " . strtolower($object_def['name']) . " with this title, please choose another.'));\n"
            . "        }\n"
            . "    }\n"
            . "\n";
    }
    $file .= "    //\n"
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
        . "    //\n"
        . "    // Update the web index if enabled\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'hookExec');\n"
        . "    ciniki_core_hookExec(\$ciniki, \$args['business_id'], 'ciniki', 'web', 'indexObject', array('object'=>'{$package}.{$module}.{$object}', 'object_id'=>\$args['{$object_id}']));\n"
        . "\n"
        . "    return array('stat'=>'ok');\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = "public/" . $object . 'Update.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// ui/object.php
//
function generate_ui() {
    global $options;
    global $package;
    global $module;
    global $object;
    global $object_id;
    global $object_def;
    global $cur_code;

    $object_title = $object;
    $object = strtolower($object);
    $o_name = $object_def['o_name'];
    $o_container = $object_def['o_container'];
    $oid = $object_id[0] . 'id';

    $file = "";

    //
    // Check if ui should be initialized
    //
    $p_name = $o_container;
    if( $options['initializeui'] == 'yes' ) {
        $file .= ""
            . "//\n"
            . "// This is the main app for the {$module} module\n"
            . "//\n"
            . "function {$package}_{$module}_main() {\n"
            . "";
        $p_name = 'menu';
    }
    //
    // Setup the list panel
    //
    $file .= "    //\n"
        . "    // The panel to list the ${object_title}\n"
        . "    //\n"
        . "    this.{$p_name} = new M.panel('{$object_title}', '{$package}_{$module}_main', '{$p_name}', 'mc', 'medium', 'sectioned', '{$package}.{$module}.main.{$p_name}');\n"
        . "    this.{$p_name}.data = {};\n"
        . ($options['nextprev'] == 'yes' ? "    this.{$p_name}.nplist = [];\n" : "")
        . "    this.{$p_name}.sections = {\n"
        . "        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,\n"
        . "            'cellClasses':[''],\n"
        . "            'hint':'Search {$object_title}',\n"
        . "            'noData':'No {$object_title} found',\n"
        . "            },\n"
        . "        '{$o_container}':{'label':'{$object_def['name']}', 'type':'simplegrid', 'num_cols':1,\n"
        . "            'noData':'No {$object_title}',\n"
        . "            'addTxt':'Add {$object_def['name']}',\n"
        . "            'addFn':'M.{$package}_{$module}_main." . ($options['detailpanel'] == 'no' ? $o_name : "edit") . ".open(\'M.{$package}_{$module}_main.{$p_name}.open();\',0,null);'\n"
        . "            },\n"
        . "    }\n"
        . "    this.{$p_name}.liveSearchCb = function(s, i, v) {\n"
        . "        if( s == 'search' && v != '' ) {\n"
        . "            M.api.getJSONBgCb('{$package}.{$module}.{$object_title}Search', {'business_id':M.curBusinessID, 'start_needle':v, 'limit':'25'}, function(rsp) {\n"
        . "                M.{$package}_{$module}_main.{$p_name}.liveSearchShow('search',null,M.gE(M.{$package}_{$module}_main.{$p_name}.panelUID + '_' + s), rsp.{$o_container});\n"
        . "                });\n"
        . "        }\n"
        . "    }\n"
        . "    this.{$p_name}.liveSearchResultValue = function(s, f, i, j, d) {\n"
        . "        return d.name;\n"
        . "    }\n"
        . "    this.{$p_name}.liveSearchResultRowFn = function(s, f, i, j, d) {\n"
        . "        return 'M.{$package}_{$module}_main.{$o_name}.open(\'M.{$package}_{$module}_main.{$p_name}.open();\',\'' + d.id + '\');';\n"
        . "    }\n"
        . "    this.{$p_name}.cellValue = function(s, i, j, d) {\n"
        . "        if( s == '{$o_container}' ) {\n"
        . "            switch(j) {\n"
        . "                case 0: return d.name;\n"
        . "            }\n"
        . "        }\n"
        . "    }\n"
        . "    this.{$p_name}.rowFn = function(s, i, d) {\n"
        . "        if( s == '{$o_container}' ) {\n"
        . "            return 'M.{$package}_{$module}_main.{$o_name}.open(\'M.{$package}_{$module}_main.{$p_name}.open();\',\'' + d.id + '\',"
            . ($options['nextprev'] == 'yes' ? "M.{$package}_{$module}_main.{$o_name}.nplist" : "" ) . ");';\n"
        . "        }\n"
        . "    }\n"
        . "    this.{$p_name}.open = function(cb) {\n"
        . "        M.api.getJSONCb('{$package}.{$module}.{$object_title}List', {'business_id':M.curBusinessID}, function(rsp) {\n"
        . "            if( rsp.stat != 'ok' ) {\n"
        . "                M.api.err(rsp);\n"
        . "                return false;\n"
        . "            }\n"
        . "            var p = M.{$package}_{$module}_main.{$p_name};\n"
        . "            p.data = rsp;\n"
        . ($options['nextprev'] == 'yes' ? "            p.nplist = (rsp.nplist != null ? rsp.nplist : null);\n" : "")
        . "            p.refresh();\n"
        . "            p.show(cb);\n"
        . "        });\n"
        . "    }\n"
        . "    this.{$p_name}.addClose('Back');\n"
        . "\n";


    //
    // Setup the detail panel if requested
    //
    $p_name = $o_name;
    if( $options['detailpanel'] == 'yes' ) {
        $file .= "    //\n"
            . "    // The panel to display {$object_def['name']}\n"
            . "    //\n"
            . "    this.{$p_name} = new M.panel('{$object_def['name']}', '{$package}_{$module}_main', '{$p_name}', 'mc', 'medium mediumaside', 'sectioned', '{$package}.{$module}.main.{$p_name}');\n"
            . "    this.{$p_name}.data = null;\n"
            . "    this.{$p_name}.{$object_id} = 0;\n"
            . "    this.{$p_name}.sections = {\n"
            . "    }\n"
            . "    this.{$p_name}.open = function(cb, {$oid}" . ($options['nextprev'] == 'yes' ? ", list" : "") . ") {\n"
            . "        if( {$oid} != null ) { this.{$object_id} = {$oid}; }\n"
            . ($options['nextprev'] == 'yes' ? "        if( list != null ) { this.nplist = list; }\n" : "")
            . "        M.api.getJSONCb('{$package}.{$module}.{$object_title}Get', {'business_id':M.curBusinessID, '{$object_id}':this.{$object_id}}, function(rsp) {\n"
            . "            if( rsp.stat != 'ok' ) {\n"
            . "                M.api.err(rsp);\n"
            . "                return false;\n"
            . "            }\n"
            . "            var p = M.{$package}_{$module}_main.{$p_name};\n"
            . "            p.data = rsp.{$o_name};\n"
            . "            p.refresh();\n"
            . "            p.show(cb);\n"
            . "        });\n"
            . "    }\n"
            . "    this.{$p_name}.addButton('edit', 'Edit', 'M.{$package}_{$module}_main.edit.open(\'M.{$package}_{$module}_main.{$p_name}.open();\',M.{$package}_{$module}_main.{$p_name}.{$object_id});');\n"
            . "    this.{$p_name}.addClose('Back');\n"
            . "\n";
        
        //
        // Setup the p_name to be edit for the edit panel next
        //
        $p_name = 'edit';
    }


    //
    // Setup the edit panel
    //
    $file .= "    //\n"
        . "    // The panel to edit {$object_def['name']}\n"
        . "    //\n"
        . "    this.{$p_name} = new M.panel('{$object_def['name']}', '{$package}_{$module}_main', '{$p_name}', 'mc', 'medium mediumaside', 'sectioned', '{$package}.{$module}.main.{$p_name}');\n"
        . "    this.{$p_name}.data = null;\n"
        . "    this.{$p_name}.{$object_id} = 0;\n"
        . ($options['nextprev'] == 'yes' ? "    this.{$p_name}.nplist = [];\n" : "")
        . "    this.{$p_name}.sections = {\n"
        . "";
    //
    // Setup the images
    //
    foreach($object_def['fields'] as $field_id => $field) {
        if( !isset($field['ref']) || $field['ref'] != 'ciniki.images.image' ) {
            continue;
        }
        $file .= "        '_{$field_id}':{'label':'{$field['name']}', 'type':'imageform', 'aside':'yes', 'fields':{\n"
            . "            '{$field_id}':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',\n"
            . "                'addDropImage':function(iid) {\n"
            . "                    M.{$package}_{$module}_main.{$p_name}.setFieldValue('{$field_id}', iid);\n"
            . "                    return true;\n"
            . "                    },\n"
            . "                'addDropImageRefresh':'',\n"
            . "                'addDropImage':function(fid) {\n"
            . "                    M.{$package}_{$module}_main.{$p_name}.setFieldValue(fid,0);\n"
            . "                    return true;\n"
            . "                 },\n"
            . "             },\n"
            . "        }},\n"
            . "";
    }
        $file .= "        'general':{'label':'', 'fields':{\n";
    //
    // Setup the other fields
    //
    foreach($object_def['fields'] as $field_id => $field) {
        if( $field_id == 'permalink'
            || (isset($field['ref']) && $field['ref'] == 'ciniki.images.image')
            || (isset($field['dbtype']) && ($field['dbtype'] == 'text' || ($field['dbtype'] == 'varchar' && $field['dbsize'] > 500))) 
            ) {
            continue;
        }
        $required = '';
        if( !isset($field['default']) ) {
            $required = "'required':'yes', ";
        }
        if( isset($field['type']) && $field['type'] == 'date' ) {
            $file .= "            '$field_id':{'label':'{$field['name']}', {$required}'type':'date'},\n";
        } elseif( isset($field['type']) && ($field['type'] == 'datetimetoutc' || $field['type'] == 'utcdatetime') ) {
            $file .= "            '$field_id':{'label':'{$field['name']}', {$required}'type':'date'},\n";
        } elseif( isset($field['type']) && $field['type'] == 'currency' ) {
            $file .= "            '$field_id':{'label':'{$field['name']}', {$required}'type':'text', 'size':'small'},\n";
        } else {
            $file .= "            '$field_id':{'label':'{$field['name']}', {$required}'type':'text'},\n";
        }
    }
    $file .= ""
        . "            }},\n";
    //
    // Setup the text blobs
    //
    foreach($object_def['fields'] as $field_id => $field) {
        print_r($field);
        if( isset($field['dbtype']) ) {
            if( $field['dbtype'] == 'text' ) {
                $file .= "        '_{$field_id}':{'label':'{$field['name']}', 'fields':{\n"
                    . "            '{$field_id}':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},\n"
                    . "            }},\n"
                    . "";
            } elseif( $field_id == 'synopsis' || ($field['dbtype'] == 'varchar' && $field['dbsize'] > 500 && $field['dbsize'] < 2000) ) {
                $file .= "        '_{$field_id}':{'label':'{$field['name']}', 'fields':{\n"
                    . "            '{$field_id}':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},\n"
                    . "            }},\n"
                    . "";
            } elseif( ($field['dbtype'] == 'varchar' && $field['dbsize'] > 500) || $field['dbtype'] == 'text' ) {
                $file .= "        '_{$field_id}':{'label':'{$field['name']}', 'fields':{\n"
                    . "            '{$field_id}':{'label':'', 'hidelabel':'yes', 'type':'textarea'},\n"
                    . "            }},\n"
                    . "";
            }
        }
    }
    $file .= ""
        . "        '_buttons':{'label':'', 'buttons':{\n"
        . "            'save':{'label':'Save', 'fn':'M.{$package}_{$module}_main.{$p_name}.save();'},\n"
        . "            'delete':{'label':'Delete', \n"
        . "                'visible':function() {return M.{$package}_{$module}_main.{$p_name}.{$object_id} > 0 ? 'yes' : 'no'; },\n"
        . "                'fn':'M.{$package}_{$module}_main.{$p_name}.remove();'},\n"
        . "            }},\n"
        . "        };\n"
        . "    this.{$p_name}.fieldValue = function(s, i, d) { return this.data[i]; }\n"
        . "    this.{$p_name}.fieldHistoryArgs = function(s, i) {\n"
        . "        return {'method':'{$package}.{$module}.{$object_title}History', 'args':{'business_id':M.curBusinessID, '{$object_id}':this.{$object_id}, 'field':i}};\n"
        . "    }\n"
        . "    this.{$p_name}.open = function(cb, {$oid}" . ($options['nextprev'] == 'yes' ? ", list" : "") . ") {\n"
        . "        if( {$oid} != null ) { this.{$object_id} = {$oid}; }\n"
        . ($options['nextprev'] == 'yes' ? "        if( list != null ) { this.nplist = list; }\n" : "")
        . "        M.api.getJSONCb('{$package}.{$module}.{$object_title}Get', {'business_id':M.curBusinessID, '{$object_id}':this.{$object_id}}, function(rsp) {\n"
        . "            if( rsp.stat != 'ok' ) {\n"
        . "                M.api.err(rsp);\n"
        . "                return false;\n"
        . "            }\n"
        . "            var p = M.{$package}_{$module}_main.{$p_name};\n"
        . "            p.data = rsp.{$o_name};\n"
        . "            p.refresh();\n"
        . "            p.show(cb);\n"
        . "        });\n"
        . "    }\n"
        . "    this.{$p_name}.save = function(cb) {\n"
        . "        if( cb == null ) { cb = 'M.{$package}_{$module}_main.{$p_name}.close();'; }\n"
        . "        if( !this.checkForm() ) { return false; }\n"
        . "        if( this.{$object_id} > 0 ) {\n"
        . "            var c = this.serializeForm('no');\n"
        . "            if( c != '' ) {\n"
        . "                M.api.postJSONCb('{$package}.{$module}.{$object_title}Update', {'business_id':M.curBusinessID, '{$object_id}':this.{$object_id}}, c, function(rsp) {\n"
        . "                    if( rsp.stat != 'ok' ) {\n"
        . "                        M.api.err(rsp);\n"
        . "                        return false;\n"
        . "                    }\n"
        . "                    eval(cb);\n"
        . "                });\n"
        . "            } else {\n"
        . "                eval(cb);\n"
        . "            }\n"
        . "        } else {\n"
        . "            var c = this.serializeForm('yes');\n"
        . "            M.api.postJSONCb('{$package}.{$module}.{$object_title}Add', {'business_id':M.curBusinessID}, c, function(rsp) {\n"
        . "                if( rsp.stat != 'ok' ) {\n"
        . "                    M.api.err(rsp);\n"
        . "                    return false;\n"
        . "                }\n"
        . "                M.{$package}_{$module}_main.{$p_name}.{$object_id} = rsp.id;\n"
        . "                eval(cb);\n"
        . "            });\n"
        . "        }\n"
        . "    }\n"
        . "    this.{$p_name}.remove = function() {\n"
        . "        if( confirm('Are you sure you want to remove {$object_title}?') ) {\n"
        . "            M.api.getJSONCb('{$package}.{$module}.{$object_title}Delete', {'business_id':M.curBusinessID, '{$object_id}':this.{$object_id}}, function(rsp) {\n"
        . "                if( rsp.stat != 'ok' ) {\n"
        . "                    M.api.err(rsp);\n"
        . "                    return false;\n"
        . "                }\n"
        . "                M.{$package}_{$module}_main.{$p_name}.close();\n"
        . "            });\n"
        . "        }\n"
        . "    }\n"
        . "";
    if( $options['nextprev'] == 'yes' ) {
        $file .= "    this.{$p_name}.nextButtonFn = function() {\n"
            . "        if( this.nplist != null && this.nplist.indexOf('' + this.{$object_id}) < (this.nplist.length - 1) ) {\n"
            . "            return 'M.{$package}_{$module}_main.{$p_name}.save(\'M.{$package}_{$module}_main.{$p_name}.open(null,' + this.nplist[this.nplist.indexOf('' + this.{$object_id}) + 1] + ');\');';\n"
            . "        }\n"
            . "        return null;\n"
            . "    }\n"
            . "    this.{$p_name}.prevButtonFn = function() {\n"
            . "        if( this.nplist != null && this.nplist.indexOf('' + this.{$object_id}) > 0 ) {\n"
            . "            return 'M.{$package}_{$module}_main.{$p_name}.save(\'M.{$package}_{$module}_main.{$object_id}.open(null,' + this.nplist[this.nplist.indexOf('' + this.{$object_id}) - 1] + ');\');';\n"
            . "        }\n"
            . "        return null;\n"
            . "    }\n"
            . "";
    }
    $file .= "    this.{$p_name}.addButton('save', 'Save', 'M.{$package}_{$module}_main.{$p_name}.save();');\n"
        . "    this.{$p_name}.addClose('Cancel');\n"
        . "";
    if( $options['nextprev'] == 'yes' ) {
        $file .= "    this.{$p_name}.addButton('next', 'Next');\n"
            . "    this.{$p_name}.addLeftButton('prev', 'Prev');\n"
            . "";
    }
    $file .= "\n";

    if( $options['initializeui'] == 'yes' ) {
        $file .= "    //\n"
            . "    // Start the app\n"
            . "    // cb - The callback to run when the user leaves the main panel in the app.\n"
            . "    // ap - The application prefix.\n"
            . "    // ag - The app arguments.\n"
            . "    //\n"
            . "    this.start = function(cb, ap, ag) {\n"
            . "        args = {};\n"
            . "        if( ag != null ) {\n"
            . "            args = eval(ag);\n"
            . "        }\n"
            . "        \n"
            . "        //\n"
            . "        // Create the app container\n"
            . "        //\n"
            . "        var ac = M.createContainer(ap, '{$package}_{$module}_main', 'yes');\n"
            . "        if( ac == null ) {\n"
            . "            alert('App Error');\n"
            . "            return false;\n"
            . "        }\n"
            . "        \n"
            . "        this.menu.open(cb);\n"
            . "    }\n"
            . "}";
    }

    $filename = "ui/" . ($options['initializeui'] == 'no' ? $object : 'main') . '.js';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

?>
