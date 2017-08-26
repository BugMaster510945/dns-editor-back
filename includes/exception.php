<?php

class appException extends Exception
{
	protected $details;

	public function __construct()
#string $message, int $code, string $message="", Throwable $previous = null, array $details = null)
	{
		$code = 500;
		$message = "";
		$previous = null;
		$details = array();

		foreach(func_get_args() as $param)
		{
			if( is_int($param) )
				$code = $param;
			if( is_string($param) )
				$message = $param;
			if( is_array($param) )
				$details = $param;
			if( is_object($param) && ($param instanceof Exception) )
				$previous = $param;
		}
		parent::__construct($message, $code, $previous);
		$this->details = $details;
	}

	public function getHTTPMessageFromCode()
	{
		switch( $this->code )
		{
			case 400:
				return 'Bad Request';
			case 401:
				return 'Unauthorized';
			case 404:
				return 'Not Found';
			case 403:
				return 'Forbidden';
			case 504:
				return 'Gateway Time-out';
			default:
				return 'Internal Server Error';
		}
	}

	public function getMessageFromCode()
	{
		switch( $this->code )
		{
			case 400:
				return _('Bad Request');
			case 401:
				return _('Unauthorized');
			case 404:
				return _('Not Found');
			case 403:
				return _('Forbidden');
			case 504:
				return _('Gateway Time-out');
			default:
				return _('Internal Server Error');
		}
	}

	/**
 	* @SWG\Definition(
 	*   definition="simpleAPIError",
 	*   type="object",
 	*   @SWG\Property(
 	*     property="message",
 	*     description="Simple message about operation",
 	*     type="string"
 	*   ),
 	*   @SWG\Property(
 	*     property="details",
 	*     description="List of error messages",
 	*     type="array",
 	*     @SWG\Items(type="string")
 	*   ),
 	*   @SWG\Property(
 	*     property="filename",
 	*     description="Name of file which throw error",
 	*     type="string"
 	*   ),
 	*   @SWG\Property(
 	*     property="fileline",
 	*     description="Line number in file which throw error",
	*     type="integer",
	*     format="int32"
 	*   )
 	* )
 	*/

	public function sendReturn()
	{
		if( $this->message == '' )
			$this->message = $this->getMessageFromCode();

		if( is_null($this->details) )
			$this->details = array();

		if( !is_null($this->getPrevious()) )
			$this->details[] = $this->getPrevious()->getMessage();

		$response = array(
			'message'  => $this->message, 
			'details'  => $this->details,
			'filename' => str_replace(PATH_BASE.'/', '', $this->file),
			'fileline' => $this->line
		);

		if( defined('DEBUG') && DEBUG )
			$response['stacktrace'] = $this->getTrace();

		//http_response_code($this->code);
		header($_SERVER["SERVER_PROTOCOL"].' '.$this->code.' '.$this->getHTTPMessageFromCode());
		Header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		Header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		Header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date dans le passé
		Header('Pragma: no-cache');
		header('Content-Type: application/json');

		print json_encode($response);
	}

}
