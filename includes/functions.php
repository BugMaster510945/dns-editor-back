<?

function guidv4()
{
	$data = openssl_random_pseudo_bytes(16);
    assert(strlen($data) == 16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function getPOST()
{
	$data = file_get_contents('php://input');

	$data = json_decode($data, true);

	$_POST = null;
	if( json_last_error() == JSON_ERROR_NONE )
		$_POST = $data;

	if( is_null($_POST) )
		$_POST = array();
}

function sendJSON($data)
{
	Header('Content-Type: application/json; charset=utf-8');
	print json_encode($data);
}

function filter_var_array_errors($input, $filter_args, &$errors, $add_null, $prefix = '')
{
	$output = filter_var_array($input, $filter_args, $add_null);

	foreach($output as $key => $value)
	{
		if( $value === false )
		{
			if( $filter_args[$key]['filter'] == FILTER_VALIDATE_EMAIL )
				$errors[] = sprintf(_('Field %s: must be a valid email address'), $prefix.$key);
			if( $filter_args[$key]['filter'] == FILTER_VALIDATE_INT )
				$errors[] = sprintf(_('Field %s: must be an integer between %d and %d'), $prefix.$key, $filter_args[$key]['options']['min_range'], $filter_args[$key]['options']['max_range']);
			if( $filter_args[$key]['filter'] == FILTER_VALIDATE_REGEXP )
				$errors[] = sprintf(_('Field %s: must match regex "%s"'), $prefix.$key, $filter_args[$key]['options']['regexp']);
		}
	}
	return $output;
}

