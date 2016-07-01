## php-debug
  
Simple, single file, debug functions for php.  
  
var_debug:  
```
<?php
  include("debug.php");

  $test = 42;
  var_debug($test);     // log: '$test = 42;'
?>
```
  
error:  
```
<?php
  include("debug.php");

  define( "DEBUG_LEVEL", "INFO" );
  error( "error message", "WARNING" );  // will be logged
  error( "error message2", "DEBUG" );   // won't be logged
  
  //with backtrace
  error( "error message3", "TEST", true ); 
?>
```
  
debug levels:
```
NONE, disables debugging  
TEST  
ERROR  
WARNING  
INFO  
DEBUG  
PROGRAM_FLOW  
```
