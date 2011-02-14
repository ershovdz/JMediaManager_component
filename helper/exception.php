<?php
defined('_JEXEC') or die( 'Restricted access' );

error_reporting(E_ALL);
define('DEBUG', true);
define('LINEBREAK', "\r\n");

class CJmmException extends Exception
{
	protected $severity;
	
	public function __construct($message = '', $code = '', $severity = '', $filename = '', $lineno = '') 
	{
		$this->message = $message;
		$this->code = $code;
		$this->severity = $severity;
		$this->file = $filename;
		$this->line = $lineno;
	}
	
	public function getSeverity() 
	{
		return $this->severity;
	}
	
	public function AddErrorMessage($message = "")
	{
		$this->message .= LINEBREAK . $message;
		throw $this;
	}
}

abstract class CErrorHandler 
{
	
	public static $LIST = array();
	
	private function __construct() 
	{
	}
	
	public static function initiate( $log = false ) 
	{
		set_error_handler( 'CErrorHandler::err_handler' );
		set_exception_handler( 'CErrorHandler::exc_handler' );
		if ( $log !== false )
		{
			if ( ! ini_get('log_errors') )
			{
				ini_set('log_errors', true);
			}
			if ( ! ini_get('error_log') )
			{
				ini_set('error_log', $log);
			}
		}
	}
	
	public static function err_handler($errno, $errstr, $errfile, $errline, $errcontext) 
	{
		$l = error_reporting();
		if ( $l & $errno ) 
		{
			$exit = false;
			switch ( $errno ) 
			{
			case E_USER_ERROR:
				$type = 'Fatal Error';
				$exit = true;
				break;
			case E_USER_WARNING:
			case E_WARNING:
				$type = 'Warning';
				break;
			case E_USER_NOTICE:
			case E_NOTICE:
			case @E_STRICT:
				$type = 'Notice';
				break;
			case @E_RECOVERABLE_ERROR:
				$type = 'Catchable';
				break;
			default:
				$type = 'Unknown Error';
				$exit = true;
				break;
			}
			
			$exception = new CJmmException($type.': '.$errstr, 0, $errno, $errfile, $errline);
			
			if ( $exit )
			{
				CErrorHandler::exc_handler($exception);
				exit();
			}
			else
			{
				CErrorHandler::exc_handler($exception);
			}
		}
		return false;
	}
	
	public static function exc_handler($exception) 
	{
		$log = $exception->getMessage() . "\n" . $exception->getTraceAsString() . LINEBREAK;
		if ( ini_get('log_errors') )
		error_log($log, 0);
		if(DEBUG)
		{
			print("Exception: $log" );
		}
		else
		{
			print("Fatal error happend: see log file for more info" );
		}
	}
}

//CErrorHandler::initiate(JPATH_COMPONENT . DS . 'jmm_error.log');