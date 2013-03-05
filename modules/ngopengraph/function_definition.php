<?php

$FunctionList = array();
$FunctionList['fetch_tags'] = array(
	'name'           => 'fetch_tags',
	'call_method'    => array(
		'class'  => 'ngOpenGraphFetchFunctions',
		'method' => 'fetchOGTags'
	),
	'parameter_type' => 'standard',
	'parameters'     => array(
		array(
			'name'     => 'url',
			'type'     => 'string',
			'required' => true,
			'default'  => null
		)
	)
);
