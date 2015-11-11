#!/usr/bin/php
<?php

$config = parse_ini_file(dirname(__FILE__) . '/run.ini');

$php = $config['php'];
$api = $config['api'];
$api_key = $config['api_key'];
$username = $config['username'];
$password = $config['password'];
$prompt = $config['prompt'];
$log = $config['log'];

$last_login = 0;
$last_cmd = '';
$last_options = array();
$line = '';
$autoexit = 'no';
$auth_token = '';

if( isset($argv[1]) && $argv[1] != '' ) {
	$autoexit = 'yes';
	for($i=1;$i<count($argv);$i++) {
		if( $i > 1 ) {
			$line .= ' ' . $argv[$i];
		} else {
			$line .= $argv[$i];
		}
	}
}

$usage = "The following commands are valid: \n\n"
	. "logs - list the last 15 log lines from $log and pick which one to run.\n"
	. "last - print the last api call\n"
	. "login <user> <pass> - login with a different username/password\n"
	. "lerr - update and get the highest error number\n"
	. "derr - duplicate error codes\n"
	. "help - print this message\n"
	. "quit|exit - Exit script\n"
	. "<enter> - re-run the last command\n"
	. "\n";

if( $line == '' ) {
	check_logs();
	print $prompt;
}

// Keep track of the last line entered, so it can be repeated by hitting enter
$last_line = "";
$last_apicmd = "";
if( $line == '' ) {
	$line = trim(fgets(STDIN));
}
while( $line !== FALSE ) {
	// $line = trim($line);

	// Check if arrow has been pressed
	if( $line != '' && ord($line[0]) == 27 ) {
		$line = '';
	}

	if( $line == '' ) {
		$line = $last_line;
	} else {
		$last_line = $line;
	}
	if( $line == 'quit' || $line == 'exit' ) {
		break;
	}
	else if( $line == 'help' ) {
		print $usage;
	}
	else if( $line == 'derr' ) {
		run_api('ciniki.systemdocs.update', '&package=ciniki');
		run_api('ciniki.systemdocs.toolsDupErrors', '&package=ciniki');
	}
	else if( $line == 'lerr' ) {
		run_api('ciniki.systemdocs.update', '&package=ciniki');
		run_api('ciniki.systemdocs.errors', '&package=ciniki&limit=1');
	}
	else if( preg_match('/^login (.*) (.*)$/', $line, $matches) ) {
		$username = $matches[1];
		$password = $matches[2];
//		run_api('ciniki.systemdocs.update', '&package=ciniki');
//		run_api('ciniki.systemdocs.errors', '&package=ciniki&limit=1');
	}
	else if( $line == 'last' ) {
		print $last_apicmd . "\n";
	}
	else if( preg_match('/logs/', $line, $matches) ) {
		check_logs();
	}
	else if( $last_cmd == 'logs' && preg_match('/^([0-9]+)$/', $line, $matches) ) {
//		print "======================== running " . $last_options[($matches[1]-1)]['method'] . "\n";
		print "============================================================================\n";
		run_api($last_options[($matches[1]-1)]['method'], $last_options[($matches[1]-1)]['args']);
	}
	else if( preg_match('/^([a-zA-Z0-9]+\.[a-z0-9A-Z]+\.[a-zA-Z0-9]+)\s*(.*)$/', $line, $matches) ) {
		$args = '';
		if( isset($matches[2]) ) {
			$args = $matches[2];
		}
		run_api($matches[1], $args);
	}

	if( $autoexit == 'yes' ) {
		break;
	}

	print $prompt;
	$line = trim(fgets(STDIN));
}

function check_logs() {
	$log = $GLOBALS['log'];
	$logs = `tail -25 $log`;
	$log_lines = explode("\n", $logs);
	$options = array();
	$num_options = 0;
	foreach($log_lines as $lline) {
		if( preg_match('/\?method=([^&]*)&api_key=([^&]*)&auth_token=([^&]*)(.*)/', $lline, $matches) ) {
			$options[$num_options] = array('log'=>$lline, 'method'=>$matches[1], 'api_key'=>$matches[2], 'auth_token'=>$matches[3], 
				'args'=>$matches[4]);
			$num_options++;
			print "$num_options. $matches[1], $matches[4]\n";
		}
	}
	if( $num_options > 0 ) {
		$GLOBALS['last_cmd'] = 'logs';
		$GLOBALS['last_options'] = $options;
		// $line = trim(fgets(STDIN));
	} else {
		print "No valid logs found\n";
	}
}

function login() {
	$php = $GLOBALS['php'];
	$api = $GLOBALS['api'];
	$rest_args = "method=ciniki.users.auth&api_key=" . $GLOBALS['api_key'] ."&auth_token=&username=" . $GLOBALS['username'] . "&password=" . $GLOBALS['password'];
//    print "$php $api '$rest_args'\n";
	$login = `$php $api '$rest_args'`;
	$last_login = time();
//	print_r($login);
	if( preg_match('/auth token=\"([a-zA-Z0-9]*)\"/', $login, $matches) ) {
		$GLOBALS['auth_token'] = $matches[1];
//		print_r($GLOBALS['auth_token']);
	} else {
		print "Login failed\n";
		$GLOBALS['auth_token'] = '';
	}
}

function run_api($method, $args) {

	$php = $GLOBALS['php'];	
	$api = $GLOBALS['api'];	
	$config = $GLOBALS['config'];	

	// Check if login has expired
	if( $GLOBALS['auth_token'] = '' || $GLOBALS['last_login'] < (time() - 1800) ) {
		login();
	}

	if( $GLOBALS['auth_token'] == '' ) {
		return;
	}

    $args = preg_replace('/ HTTP.*/', '', $args);

	$rest_args = "method=$method&api_key=" . $GLOBALS['api_key'] . "&auth_token=" . $GLOBALS['auth_token'] . $args;

//	print_r("$php $api '$rest_args'\n");
	$GLOBALS['last_apicmd'] = "$php $api '$rest_args'";
	$rc = `$php $api '$rest_args'`;
    $json = json_decode($rc, true);
    if( $json == NULL ) {
        print_r($rc);
        print "\n";
    } else {
        if( isset($config['output']) && strstr($config['output'], 'print_r') ) {
            print_r($json);
            print "\n";
        }
        if( !isset($config['output']) || strstr($config['output'], 'json') ) {
            print json_encode($json, JSON_PRETTY_PRINT);
            print "\n";
        }
    }
}


?>
