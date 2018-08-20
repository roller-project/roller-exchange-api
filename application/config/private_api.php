<?php
include_once __DIR__."/rest_zone.php";
$config['rest_enable_keys'] = false;
$config['rest_auth'] = 'basic';
$config['auth_source'] = 'library';
$config['auth_library_class'] = 'author_model';
$config['auth_library_function'] = '_checklogin';