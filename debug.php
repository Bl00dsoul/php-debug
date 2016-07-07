<?php
/*
 * Copyright 01-07-2016 Bl00dsoul
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

function var_debug( $var ){
	$debug = new Debug();
	$name = $debug->var_name();
	$result = var_export( $var, true );

	if( !isset($name) || $name == false ){
		// it might be a straight value. i.e: "string" or 42, in wich case it does not have a name.
		$name = "\$Value";
	}
	error_log( $name." = ".$result.";" );
}

function error( $error_message = NULL, $level = NULL, $show_backtrace = false ){
	$debug = new Debug( $level );
	if( $debug->check_level() ){
		$debug->log_message( $error_message, $show_backtrace );
	}
}

class Debug {
	protected $level;
	protected $log_levels = array(
		"NONE" => 0,
		"TEST" => 1,
		"ERROR" => 2,
		"WARNING" => 3,
		"INFO" => 4,
		"DEBUG" => 5,
		"PROGRAM_FLOW" => 6 	
	);

	public function __construct( $level = NULL ) {
		if( isset($level) ){
			$this->level = $level;
		} else {
			$this->level = "UNKNOWN";
		}
	}
	
	public function set_debug_level( $level ){
		if( isset($level) && isset($this->log_levels[ $level ]) ){
			define( "DEBUG_LEVEL", $level );
			return true;
		}
		return false;
	}

	// check if the debug level of this debug object is higher or equal to the global debug level
	public function check_level(){
		if( !defined("DEBUG_LEVEL") ){
			set_debug_level( "INFO" );
		}
		if( $this->log_levels[DEBUG_LEVEL] != "NONE" ){	// never print anything when debugging is disabled
			if( $this->level == "UNKNOWN" ){	// if no level was set for this debug message, print it
				return true;
			}
			if( isset($this->log_levels[$this->level]) && $this->log_levels[$this->level] <= $this->log_levels[DEBUG_LEVEL] ){
				return true;
			}
		}
		return false;
	}
   
	// generates a backtrace listing the files and linenumbers of calls, useful when errors occur in deeply nested functions.
	private function backtrace( $bt_data ){
		$count = count($bt_data);

		$backtrace = "";
		$file_padding = "";
		$line_padding = "";
		
		// figure out what the longest file/line is. to align the output properly
		foreach($bt_data as $func){
			$file_padding = max( strlen($func['file']), $file_padding );
			$line_padding = max( strlen($func['line']), $line_padding );
		}

		$file_padding += 7;	
		$line_padding += 7;
		foreach($bt_data as $func){

			if( $func['file'] != __FILE__ ){				// ignore functions from this file
				$backtrace .= " [".$count."] => ";
				$backtrace .= sprintf( "%-".$file_padding."s", "File: ".$func['file'] );
				$backtrace .= sprintf( "%-".$line_padding."s", "Line: ".$func['line'] );
				$backtrace .= "Function: ".$func['function']."(";
				foreach($func['args'] as $arg){
					switch($arg){
						case (is_array($arg)): 
							$backtrace .= "Array, ";	
						 break;
						case (is_string($arg)): 
							$backtrace .= "\"$arg\", ";	
						 break;
						case (is_int($arg)):
							$backtrace .= "$arg, ";
						 break;
					}
				}
				if( count($func['args']) > 0 ){
					$backtrace = substr($backtrace, 0, -2);
				}
				$backtrace .= "); \n";
			}
			$count--;
		}
		if(isset($_SERVER['REQUEST_URI'])){
			$backtrace .= " [".$count."] => start: ". $bt_data[ (count($bt_data) - 1) ]['file'];
		} else {
			$pid = getmypid();
			exec( "ps ".$pid. "| awk '{ print substr($0, index($0,$5)) }'", $output, $return );
			$call = $output[1];
			$backtrace .= " [".$count."] => start: Called from commandline: ".$call." (".get_current_user().") ";
		}
		return( $backtrace );
	}

	// generates a standerdized formatted error to the errorlog
	public function log_message( $error_message = NULL, $show_backtrace = false, $depth = 1 ){

		$prefix = "[".$this->level."] ";
		if( !isset($error_message) ){
			$error_message = $prefix . "An error was encountered";
			$show_backtrace = true;
		} else {
			$error_message = $prefix . $error_message;
		}
	
		$bt_data = debug_backtrace();		// get backtrace data
		if( $show_backtrace ){
			$backtrace = self::backtrace($bt_data);
			$error_message .= "\n Backtrace: \n$backtrace \n";
		} else {
			$error_message .= "\n File: ".basename( $bt_data[$depth]['file'] )." Line: ".$bt_data[$depth]['line']." \n";
		}
	
		error_log($error_message);
	}

	// gets the variable name of the variable that was passed into the debug function
	public function var_name( $depth = 1 ) {

		$trace = debug_backtrace();
		$file = file( $trace[ $depth ]['file'] );		// read the file where the debug function was called
		$line = $file[ $trace[ $depth ]['line'] - 1 ];		// get the line
		preg_match( "#\\$+([^\)\s;]+)#", $line, $match );	// match the variable
		if( isset($match) && isset($match[0]) ){ 
			return $match[0];
		} else {
			return false;
		}
	}
}

?>
