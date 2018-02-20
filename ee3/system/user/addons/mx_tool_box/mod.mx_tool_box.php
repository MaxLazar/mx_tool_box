<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	/**
	 * -
	 * @package		MX Tool Box
	 * @subpackage	ThirdParty
	 * @category	Modules
	 * @author    Max Lazar <max@eec.ms>
	 * @copyright Copyright (c) 2010-2011 Max Lazar (http://eec.ms)
	 * @link		http://eec.ms/
	 */
class Mx_tool_box {

	var $return_data;
	
	public function __construct()
	{		

	}
	
		
	/**
     * Helper public function for getting a parameter
	 */		 
	public function _get_param($key, $default_value = '')
	{
		$val = ee()->TMPL->fetch_param($key);
		
		if($val == '') {
			return $default_value;
		}
		return $val;
	}

	/**
	 * Helper funciton for template logging
	 */	
	public function _error_log($msg)
	{		
		ee()->TMPL->log_item("mx_tool_box ERROR: ".$msg);		
	}		
}

/* End of file mod.mx_tool_box.php */ 
/* Location: ./system/expressionengine/third_party/mx_tool_box/mod.mx_tool_box.php */ 