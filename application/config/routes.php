<?php
defined('BASEPATH') OR exit('No direct script access allowed');


$route['default_controller'] = 'dashboard';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['api/public'] = 'publicapi';
$route['api/public/(:any)'] = 'publicapi/$1';
$route['api/account'] = 'privateapi';
$route['api/account/(:any)'] = 'privateapi/$1';
$route['api/market'] = 'marketapi';
$route['api/market/(:any)'] = 'marketapi/$1';
$route['server/(:any)'] = 'serverapi/$1';
