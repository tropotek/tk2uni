<?php
/*
 * Application default config values
 * This file should not need to be edited
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */

$config = \App\Config::getInstance();

/**************************************
 * Default app config values
 **************************************/



/*
 * Template folders for pages
 */
$config['system.template.path'] = '/html';

$config['system.theme.public']  = $config['system.template.path']  . '/public';
$config['system.theme.admin']   = $config['system.template.path']  . '/admin';

$config['template.admin']       = $config['system.theme.admin']    . '/admin.html';
$config['template.client']      = $config['system.theme.admin']    . '/admin.html';
$config['template.staff']       = $config['system.theme.admin']    . '/admin.html';
$config['template.student']     = $config['system.theme.admin']    . '/admin.html';
$config['template.public']      = $config['system.theme.public']   . '/public.html';

$config['template.login']       = $config['system.theme.admin']    . '/login.html';

//$config['url.auth.home']        = '/';
//$config['url.auth.login']       = '/';

/*
 * Set the error page template, this has minimum system requirements
 * for parsing and is usually a separate file.
 */
$config['template.error']   = $config['system.template.path'] . '/theme-cube/error.html';




