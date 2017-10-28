#!/opt/local/bin/php
<?php

//
// Description
// -----------
// This script will find all the error codes in the current module directory
//
//
$path = getcwd();
if( !file_exists($path . '/db') && !file_exists($path . '/public') ) {
    $path = dirname($path);
}
if( !file_exists($path . '/db') && !file_exists($path . '/public') ) {
    print "Must be used inside a module.\n";
    usage();
    exit;
}

//
// Search for the errors
//
$results = `grep -H "'code'=>" $path/*/*.php`;
$lines = explode("\n", $results);
$errors = array();  // Current array of errors
$old = array();     // Old version of errors, from copied code
foreach($lines as $lid => $line) {
    $line = preg_replace("#^" . $path . "/#", '', $line);
    if( preg_match("/^([^:]*):.*'code'=>'([^\.']*)\.([^\.']*)\.([^\.']*)'/", $line, $matches) ) {
        $errors[] = array('file'=>$matches[1], 'package'=>$matches[2], 'module'=>$matches[3], 'number'=>$matches[4]);
    }
    if( preg_match("/^([^:]*):.*'pkg'.*'code'/", $line, $matches) ) {
        $old[] = array('file'=>$matches[1], 'line'=>$line);
    }
}
uasort($errors, function($a, $b) {
    if( $a['number'] == $b['number'] ) {
        return 0;
    }
    return ($a['number'] < $b['number']) ? -1 : 1;
});

$prev = 0;
$numbers = array();
$gaps = array();
$dups = array();
$next = 0;
$pkg = '';
$mod = '';
$max = '';
foreach($errors as $err) {
    $pkg = $err['package'];
    $mod = $err['module'];
    if( in_array($err['number'], $numbers) ) {
        $dups[] = $err['number'];
    } else {
        $numbers[] = $err['number'];
    }
    if( ($err['number'] - $prev) > 1 ) {
        for($i = ($prev+1); $i < $err['number']; $i++) {
            if( $next == 0 ) {
                $next = $i;
            }
            $gaps[] = $i;
        }
    }
    $max = $err['package'] . '.' . $err['module'] . '.' . $err['number']; 
    $prev = $err['number'];
}

if( $next == 0 ) {
    $next = $prev + 1;
}

if( isset($argv[1]) && $argv[1] == '-r' ) {
    print "return array('stat'=>'fail', 'err'=>array('code'=>'$pkg.$mod.$next', 'msg'=>'', 'err'=>\$rc['err']));";
} else {
    print "return array('stat'=>'fail', 'err'=>array('code'=>'$pkg.$mod.$next', 'msg'=>''));";
}
exit;

//
// Print the usage of the script
//
function usage() {
    print "mod_next_err.php\n\n";
}

?>
