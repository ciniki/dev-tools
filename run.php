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
	. "logs - list the last 5 log lines from $log and pick which one to run.\n"
	. "help - print this message\n"
	. "quit|exit - Exit script\n\n";

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
	else if( preg_match('/^(ciniki\.[a-z0-9A-Z]+\.[a-zA-Z0-9]+)\s*(.*)$/', $line, $matches) ) {
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
	$logs = `tail -5 $log`;
	$log_lines = explode("\n", $logs);
	$options = array();
	$num_options = 0;
	foreach($log_lines as $lline) {
		if( preg_match('/\?method=([^&]*)&api_key=([^&]*)&auth_token=([^&]*)(.*)&format=json/', $lline, $matches) ) {
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
	$login = `$php $api '$rest_args'`;
	$last_login = time();
	if( preg_match('/auth token=\"(.*)\"/', $login, $matches) ) {
		$GLOBALS['auth_token'] = $matches[1];
	} else {
		print "Login failed\n";
		$GLOBALS['auth_token'] = '';
	}
}

function run_api($method, $args) {

	$php = $GLOBALS['php'];	
	$api = $GLOBALS['api'];	
	// Check if login has expired
	if( $GLOBALS['auth_token'] = '' || $GLOBALS['last_login'] < (time() - 1800) ) {
		login();
	}

	if( $GLOBALS['auth_token'] == '' ) {
		return;
	}

	$rest_args = "method=$method&api_key=" . $GLOBALS['api_key'] . "&auth_token=" . $GLOBALS['auth_token'] . $args;

	$GLOBALS['last_apicmd'] = "$php $api '$rest_args'";
	print `$php $api '$rest_args'`;
}

?>
