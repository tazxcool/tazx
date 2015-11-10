<?php 
/**
 * This is a Tazx pagecontroller.
 *
 */
// Include the essential config-file which also creates the $tazx variable with its defaults.
include(__DIR__.'/config.php'); 
 
 
// Do it and store it all in variables in the Tazx container.
$tazx['title'] = "Hello World";
 
$tazx['header'] = <<<EOD
<img class='sitelogo' src='img/tazx.png' alt='Tazx Logo'/>
<span class='sitetitle'>Tazx webbtemplate</span>
<span class='siteslogan'>Återanvändbara moduler för webbutveckling med PHP</span>
EOD;
 
$tazx['main'] = <<<EOD
<h1>Hej Världen</h1>
<p>Detta är en exempelsida som visar hur Tazx ser ut och fungerar.</p>
EOD;
 
$tazx['footer'] = <<<EOD
<footer><span class='sitefooter'>Copyright (c) Mikael Roos (me@mikaelroos.se) | <a href='https://github.com/mosbth/Tazx-base'>Tazx på GitHub</a> | <a href='http://validator.w3.org/unicorn/check?ucn_uri=referer&amp;ucn_task=conformance'>Unicorn</a></span></footer>
EOD;
 
 
// Finally, leave it all to the rendering phase of Tazx.
include(TAZX_THEME_PATH);