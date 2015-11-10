<?php
/**
 * Config-file for Tazx. Change settings here to affect installation.
 *
 */
 
/**
 * Set the error reporting.
 *
 */
error_reporting(-1);              // Report all type of errors
ini_set('display_errors', 1);     // Display all errors 
ini_set('output_buffering', 0);   // Do not buffer outputs, write directly
 

/**
 * Start the session.
 *
 */
session_name(preg_replace('/[^a-z\d]/i', '', __DIR__));
session_start();
 
/**
 * Define Tazx paths.
 *
 */
define('TAZX_INSTALL_PATH', __DIR__ . '/../tazx');
define('TAZX_THEME_PATH', TAZX_INSTALL_PATH . '/theme/render.php');
 
 
/**
 * Include bootstrapping functions.
 *
 */
include(TAZX_INSTALL_PATH . '/src/bootstrap.php');
 
 
/**
 * Create the Tazx variable.
 *
 */
$tazx = array();
 
 
/**
 * Site wide settings.
 *
 */
$tazx['lang']         = 'sv';
$tazx['title_append'] = 'Me | oophp';

$tazx['header'] = <<<EOD
    <header>    
        <!--img class='sitelogo' src='img/oophp.png' alt='oophp Logo'/-->
        <span class='sitetitle'>Oophp-programmering</span>
        <span class='siteslogan'>Ingår i kursen Databaser och Objektorienterad PHP-programmering</span>
    </header>
EOD;

$tazx['footer'] = <<<EOD
    <footer><span class='sitefooter'>Copyright (c) Anders Haag | <a href='http://validator.w3.org/unicorn/check?ucn_uri=referer&amp;ucn_task=conformance'>Unicorn</a></span></footer>
EOD;

$tazx['byline'] = <<<EOD
<footer class="byline">
    <figure class="left"><img src="img/anders.gif" alt="Anders Haag">
        <figcaption>Anders Haag</figcaption>
    </figure>
    <p><strong>Anders Haag</strong> studerar webb&shy;programmering vid Blekinge Tekniska Högskola som en del i det systemvetenskapliga programmet på Luleå Tekniska Universitet. Förutom webbprogrammering studerar Anders även statistik vid Karlstads Universitet.</p>
</footer>
EOD;




/**
 * Theme related settings.
 *
 */
$tazx['stylesheets'] = array('css/style.css');
$tazx['favicon']    = 'favicon.ico';