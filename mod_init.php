#!/opt/local/bin/php
<?php

//
// Description
// -----------
// This script will create the files required for a new module.
//
//
$package = 'ciniki';
$module = basename(getcwd());
$cur_code = 1;

$i = 1;
while( isset($argv[$i]) && $argv[$i][0] == '-' ) {
    if( $argv[$i] == '-p' ) {
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

if( !isset($argv[1]) ) {
    usage();
    exit;
}

$title = $argv[$i];

print "\n";

if( !file_exists('db') ) { mkdir('db'); }
if( !file_exists('hooks') ) { mkdir('hooks'); }
if( !file_exists('public') ) { mkdir('public'); }
if( !file_exists('private') ) { mkdir('private'); }
if( !file_exists('ui') ) { mkdir('ui'); }
if( !file_exists('web') ) { mkdir('web'); }
generate_readme();
generate_license();
generate_info();
generate_db_history();
generate_checkAccess();
generate_objects();
generate_maps();
generate_flags();
generate_uisettings();
//generate_ui();

print "done\n";

exit;

//
// Print the usage of the script
//
function usage() {
    print "mod_init.php [-p <package>] [-m <module>] [-c <error code>] <title> \n\n"
        . "    -p <package>  - The package if not ciniki\n"
        . "    -m <module>   - The package if not same as directory name\n"
        . "    -c <code>     - The error code to start errors at, otherwise looks for maximum in module\n"
        . "\n";
}

//
// README.md
//
function generate_readme() {
    $file = ""
        . "Ciniki - Small Business Management Platform\n"
        . "===========================================\n"
        . "\n"
        . "FIXME: Module Description\n"
        . "\n"
        . "License\n"
        . "-------\n"
        . "Ciniki is free software, and is released under the terms of the MIT License. See LICENSE.md.\n"
        . "";

    if( !file_exists('README.md') ) {
        file_put_contents('README.md', $file);
        print "Update the description in README.md\n";
    }
}

//
// LICENSE.md
//
function generate_license() {
    $file = ""
        . "The MIT License\n"
        . "\n"
        . "Ciniki - Small Business Management Platform (http://ciniki.org)\n"
        . "Copyright (c) 2011, ciniki.org\n"
        . "\n"
        . "Permission is hereby granted, free of charge, to any person obtaining a\n"
        . "copy of this software and associated documentation files (the \"Software\"),\n"
        . "to deal in the Software without restriction, including without limitation\n"
        . "the rights to use, copy, modify, merge, publish, distribute, sublicense,\n"
        . "and/or sell copies of the Software, and to permit persons to whom the\n"
        . "Software is furnished to do so, subject to the following conditions:\n"
        . "\n"
        . "The above copyright notice and this permission notice shall be included in\n"
        . "all copies or substantial portions of the Software.\n"
        . "\n"
        . "THE SOFTWARE IS PROVIDED \"AS IS\", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR\n"
        . "IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,\n"
        . "FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE\n"
        . "AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER\n"
        . "LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING\n"
        . "FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER\n"
        . "DEALINGS IN THE SOFTWARE.\n"
        . "";

    if( !file_exists('LICENSE.md') ) {
        file_put_contents('LICENSE.md', $file);
    }
}

//
// _info.ini
//
function generate_info() {
    global $title;

    $file = ""
        . "name = $title\n"
        . "public = no\n"
        . "";
    if( !file_exists('_info.ini') ) {
        file_put_contents('_info.ini', $file);
    }
}

//
// history.schema
//
function generate_db_history() {
    global $cur_code;
    global $package;
    global $module;
    
    $file = ""
        . "#\n"
        . "# Description\n"
        . "# -----------\n"
        . "# This table stores all changes to the $module module.\n"
        . "#\n"
        . "# Fields\n"
        . "# ------\n"
        . "# id:                   The id of the log entry.\n"
        . "#\n"
        . "# uuid:                 The uuid of the log entry.  This is used for replication purposes.\n"
        . "#\n"
        . "# business_id:          The ID of the business the change happened on.  Every change\n"
        . "#                       must be tied to a business for security reasons.\n"
        . "#\n"
        . "# user_id:              The user who made the change.\n"
        . "#\n"
        . "# session:              The id of the current session for the user.  A login starts a\n"
        . "#                       session, a logout or timeout ends a session.\n"
        . "#\n"
        . "#                       The session and transaction fields are also a system\n"
        . "#                       that group changes together.\n"
        . "#\n"
        . "# action:               The action performed.  This is used for rollback purposes.\n"
        . "#\n"
        . "#                           0 - unknown\n"
        . "#                           1 - add\n"
        . "#                           2 - update\n"
        . "#                           3 - delete\n"
        . "#                           4 - merge\n"
        . "#                           5 - merge delete\n"
        . "#                           6 - automerge\n"
        . "#                           7 - automerge delete\n"
        . "#\n"
        . "# table_name:           The table where the change was made.\n"
        . "#\n"
        . "# table_key:            This should always be the primary key for the table changed.\n"
        . "#\n"
        . "# table_field:          The changed field.\n"
        . "#\n"
        . "# new_value:            The new value of the field.\n"
        . "#\n"
        . "# log_date:             The UTC date and time the change happened.\n"
        . "#\n"
        . "create table {$package}_{$module}_history (\n"
        . "        id bigint not null auto_increment,\n"
        . "        uuid char(36) not null,\n"
        . "        business_id int not null,\n"
        . "        user_id int not null,\n"
        . "        session varchar(50) not null,\n"
        . "        action tinyint unsigned not null,\n"
        . "        table_name varchar(50) not null,\n"
        . "        table_key varchar(50) not null,\n"
        . "        table_field varchar(50) not null,\n"
        . "        new_value varchar(65000) not null,\n"
        . "        log_date datetime not null,\n"
        . "        primary key (id),\n"
        . "        index (user_id),\n"
        . "        index (business_id, table_name, table_key, table_field),\n"
        . "        index (log_date)\n"
        . ") ENGINE=InnoDB, COMMENT='v1.01';\n"
        . "";
    $filename = "db/{$package}_{$module}_history.schema";
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// checkAccess.php 
//
function generate_checkAccess() {
    global $cur_code;
    global $package;
    global $module;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// -----------\n"
        . "// This function will check if the user has access to the module.\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "// ciniki:\n"
        . "// business_id:                 The business ID to check the session user against.\n"
        . "// method:                      The requested method.\n"
        . "//\n"
        . "// Returns\n"
        . "// -------\n"
        . "// <rsp stat='ok' />\n"
        . "//\n"
        . "function {$package}_{$module}_checkAccess(&\$ciniki, \$business_id, \$method) {\n"
        . "    //\n"
        . "    // Check if the business is active and the module is enabled\n"
        . "    //\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');\n"
        . "    \$rc = ciniki_businesses_checkModuleAccess(\$ciniki, \$business_id, '$package', '$module');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \$modules = \$rc['modules'];\n"
        . "\n"
        . "    if( !isset(\$rc['ruleset']) ) {\n"
        . "        return array('stat'=>'fail', 'err'=>array('code'=>'{$package}.{$module}." . $cur_code++ . "', 'msg'=>'No permissions granted'));\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Sysadmins are allowed full access\n"
        . "    //\n"
        . "    if( (\$ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {\n"
        . "        return array('stat'=>'ok', 'modules'=>\$modules);\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Users who are an owner or employee of a business can see the business alerts\n"
        . "    //\n"
        . "    \$strsql = \"SELECT business_id, user_id \"\n"
        . "        . \"FROM ciniki_business_users \"\n"
        . "        . \"WHERE business_id = '\" . ciniki_core_dbQuote(\$ciniki, \$business_id) . \"' \"\n"
        . "        . \"AND user_id = '\" . ciniki_core_dbQuote(\$ciniki, \$ciniki['session']['user']['id']) . \"' \"\n"
        . "        . \"AND package = '$package' \"\n"
        . "        . \"AND status = 10 \"\n"
        . "        . \"AND (permission_group = 'owners' OR permission_group = 'employees' OR permission_group = 'resellers') \"\n"
        . "        . \"\";\n"
        . "    ciniki_core_loadMethod(\$ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');\n"
        . "    \$rc = ciniki_core_dbHashQuery(\$ciniki, \$strsql, 'ciniki.businesses', 'user');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return array('stat'=>'fail', 'err'=>array('code'=>'{$package}.{$module}." . $cur_code++ . "', 'msg'=>'Access denied.'));\n"
        . "    }\n"
        . "    //\n"
        . "    // If the user has permission, return ok\n"
        . "    //\n"
        . "    if( isset(\$rc['rows']) && isset(\$rc['rows'][0])\n"
        . "        && \$rc['rows'][0]['user_id'] > 0 && \$rc['rows'][0]['user_id'] == \$ciniki['session']['user']['id'] ) {\n"
        . "        return array('stat'=>'ok', 'modules'=>\$modules);\n"
        . "    }\n" 
        . "\n"
        . "    //\n"
        . "    // By default fail\n"
        . "    //\n"
        . "    return array('stat'=>'fail', 'err'=>array('code'=>'{$package}.{$module}." . $cur_code++ . "', 'msg'=>'Access denied'));\n"
        . "}\n"
        . "?>\n"
        . ""; 
    if( !file_exists('private/checkAccess.php') ) {
        file_put_contents('private/checkAccess.php', $file);
    }
}

//
// objects.php
//
function generate_objects() {
    global $package;
    global $module;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// -----------\n"
        . "// This function returns the list of objects for the module.\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "//\n"
        . "// Returns\n"
        . "// -------\n"
        . "//\n"
        . "function {$package}_{$module}_objects(&\$ciniki) {\n"
        . "    //\n"
        . "    // Build the objects\n"
        . "    //\n"
        . "    \$objects = array();\n"
        . "    //\n"
        . "    return array('stat'=>'ok', 'objects'=>\$objects);\n"
        . "}\n"
        . "?>\n"
        . "";
    if( !file_exists('private/objects.php') ) {
        file_put_contents('private/objects.php', $file);
    }
}

//
// maps.php
//
function generate_maps() {
    global $package;
    global $module;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// -----------\n"
        . "// This function returns the int to text mappings for the module.\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "//\n"
        . "// Returns\n"
        . "// -------\n"
        . "//\n"
        . "function {$package}_{$module}_maps(&\$ciniki) {\n"
        . "    //\n"
        . "    // Build the maps object\n"
        . "    //\n"
        . "    \$maps = array();\n"
        . "    \$maps['object'] = array('field'=>array(\n"
        . "        'int'=>'text',\n"
        . "    ));\n"
        . "    //\n"
        . "    return array('stat'=>'ok', 'maps'=>\$maps);\n"
        . "}\n"
        . "?>\n"
        . "";
    if( !file_exists('private/maps.php') ) {
        file_put_contents('private/maps.php', $file);
    }
}

//
// flags.php
//
function generate_flags() {
    global $package;
    global $module;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// -----------\n"
        . "// The module flags\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "//\n"
        . "// Returns\n"
        . "// -------\n"
        . "//\n"
        . "function {$package}_{$module}_flags(&\$ciniki) {\n"
        . "    //\n"
        . "    // The flags for the object\n"
        . "    //\n"
        . "    \$flags = array(\n"
        . "        // 0x01\n"
        . "//        array('flag'=>array('bit'=>'1', 'name'=>'')),\n"
        . "//        array('flag'=>array('bit'=>'2', 'name'=>'')),\n"
        . "//        array('flag'=>array('bit'=>'3', 'name'=>'')),\n"
        . "//        array('flag'=>array('bit'=>'4', 'name'=>'')),\n"
        . "        // 0x10\n"
        . "//        array('flag'=>array('bit'=>'5', 'name'=>'')),\n"
        . "//        array('flag'=>array('bit'=>'6', 'name'=>'')),\n"
        . "//        array('flag'=>array('bit'=>'7', 'name'=>'')),\n"
        . "//        array('flag'=>array('bit'=>'8', 'name'=>'')),\n"
        . "        // 0x0100\n"
        . "//        array('flag'=>array('bit'=>'9', 'name'=>'')),\n"
        . "//        array('flag'=>array('bit'=>'10', 'name'=>'')),\n"
        . "//        array('flag'=>array('bit'=>'11', 'name'=>'')),\n"
        . "//        array('flag'=>array('bit'=>'12', 'name'=>'')),\n"
        . "        // 0x1000\n"
        . "//        array('flag'=>array('bit'=>'13', 'name'=>'')),\n"
        . "//        array('flag'=>array('bit'=>'14', 'name'=>'')),\n"
        . "//        array('flag'=>array('bit'=>'15', 'name'=>'')),\n"
        . "//        array('flag'=>array('bit'=>'16', 'name'=>'')),\n"
        . "        );\n"
        . "    //\n"
        . "    return array('stat'=>'ok', 'flags'=>\$flags);\n"
        . "}\n"
        . "?>\n"
        . "";
    if( !file_exists('private/flags.php') ) {
        file_put_contents('private/flags.php', $file);
    }
}

//
// ui/main.js 
//
function generate_ui() {
    global $package;
    global $module;
    global $title;

    $file = ""
        . "//\n"
        . "// This is the main app for the $title module\n"
        . "//\n"
        . "function {$package}_{$module}_main() {\n"
        . "    //\n"
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
    if( !file_exists('ui/main.js') ) {
        file_put_contents('ui/main.js', $file);
    }
}

//
// Generate the hooks/uiSettings.php
//
function generate_uisettings() {
    global $package;
    global $module;
    global $title;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// -----------\n"
        . "// This function returns the settings for the module and the main menu items and settings menu items\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "// ciniki:\n"
        . "// business_id:\n"
        . "// args: The arguments for the hook\n"
        . "//\n"
        . "// Returns\n"
        . "// -------\n"
        . "//\n"
        . "function {$package}_{$module}_hooks_uiSettings(&\$ciniki, \$business_id, \$args) {\n"
        . "    //\n"
        . "    // Setup the default response\n"
        . "    //\n"
        . "    \$rsp = array('stat'=>'ok', 'menu_items'=>array(), 'settings_menu_items'=>array());\n"
        . "\n"
        . "    //\n"
        . "    // Check permissions for what menu items should be available\n"
        . "    //\n"
        . "    if( isset(\$ciniki['business']['modules']['{$package}.{$module}'])\n"
        . "        && (isset(\$args['permissions']['owners'])\n"
        . "            || isset(\$args['permissions']['employees'])\n"
        . "            || isset(\$args['permissions']['resellers'])\n"
        . "            || (\$ciniki['session']['user']['perms']&0x01) == 0x01\n"
        . "            )\n"
        . "        ) {\n"
        . "        \$menu_item = array(\n"
        . "            'priority'=>5000,\n"
        . "            'label'=>'{$title}',\n"
        . "            'edit'=>array('app'=>'{$package}.{$module}.main'),\n"
        . "            );\n"
        . "        \$rsp['menu_items'][] = \$menu_item;\n"
        . "    }\n"
        . "\n"
        . "    return \$rsp;\n"
        . "}\n"
        . "?>\n"
        . "";
    if( !file_exists('hooks/uiSettings.php') ) {
        file_put_contents('hooks/uiSettings.php', $file);
    }
}

?>
