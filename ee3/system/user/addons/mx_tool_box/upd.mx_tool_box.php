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
class Mx_tool_box_upd {
		
	var $version        = '1.2.1'; 
	var $module_name = "Mx_tool_box";
	
    public function __construct( $switch = TRUE ) 
    { 

    } 

    /**
     * Installer for the Mx_tool_box module
     */
    public function install() 
	{				
						
		$data = array(
			'module_name' 	 => $this->module_name,
			'module_version' => $this->version,
			'has_cp_backend' => 'y'
		);

		ee()->db->insert('modules', $data);		
		
		//
		// Add additional stuff needed on module install here
		// 
																									
		return TRUE;
	}

	
	/**
	 * Uninstall the Mx_tool_box module
	 */
	public function uninstall() 
	{ 				
		
		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => $this->module_name));
		
		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_groups');
		
		ee()->db->where('module_name', $this->module_name);
		ee()->db->delete('modules');
		
		ee()->db->where('class', $this->module_name);
		ee()->db->delete('actions');
		
		ee()->db->where('class', $this->module_name.'_mcp');
		ee()->db->delete('actions');
										
		return TRUE;
	}
	
	/**
	 * Update the Mx_tool_box module
	 * 
	 * @param $current current version number
	 * @return boolean indicating whether or not the module was updated 
	 */
	
	public function update($current = '')
	{
		return true;
	}
    
}

/* End of file upd.mx_tool_box.php */ 
/* Location: ./system/expressionengine/third_party/mx_tool_box/upd.mx_tool_box.php */ 