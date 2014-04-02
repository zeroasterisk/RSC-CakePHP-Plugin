<?php
// https://github.com/zeroasterisk/RSC-CakePHP-Plugin
//
$config = array(
	'RSC' => array(
		'server' => 'US', //UK
		'username' => 'xxxxx',
		'api_key' => 'xxxxxxx',
		'region' => 'ORD', //ORD, DFW, LON
		'url_type' => 'publicURL',
		'tenant_name' => '',
		// RSC Files
		'files' => array(
			'defaults' => array(
				// CORS header (WIP)
				'extra_headers' => array('Access-Control-Allow-Origin' => '*'),
			)
		)
		// RSC DNS
	)
);
