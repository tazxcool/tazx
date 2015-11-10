<?php 
/**
 * This is a Tazx pagecontroller.
 *
 */
// Include the essential config-file which also creates the $tazx variable with its defaults.
include(__DIR__.'/config.php'); 
 
 
// Do it and store it all in variables in the Tazx container.
$tazx['title'] = "404";
$tazx['header'] = "";
$tazx['main'] = "This is a Tazx 404. Document is not here.";
$tazx['footer'] = "";
 
// Send the 404 header 
header("HTTP/1.0 404 Not Found");
 
 
// Finally, leave it all to the rendering phase of Tazx.
include(TAZX_THEME_PATH);