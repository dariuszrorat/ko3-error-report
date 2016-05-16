<?php defined('SYSPATH') OR die('No direct access allowed.');
return array (

    'driver' => 'smtp',

    'options' =>  array(
        'hostname'   =>'hostname',
        'port'       =>'587',
        'username'   =>'your_name',
        'password'   =>'your_password',
        'encryption' => 'tls',
    ),
);