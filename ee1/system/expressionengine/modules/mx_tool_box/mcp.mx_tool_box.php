<?php  if ( ! defined('EXT') ) exit('Invalid file request');

	/**
	 * -
	 * @package		MX Tool Box
	 * @subpackage	ThirdParty
	 * @category	Modules
	 * @author    Max Lazar <max@eec.ms>
	 * @copyright Copyright (c) 2010-2011 Max Lazar (http://eec.ms)
	 * @link		http://eec.ms/
	 */
class Mx_tool_box_CP
{
	var $base;			// the base url for this module
	var $form_base;		// base url for forms
	var $module_name = "Mx_tool_box";
    var $version = '1.2.1';


	function Mx_tool_box_CP( $switch = TRUE )
	{
        
        global $PREFS, $LANG, $DSP;
        
        $LANG->fetch_language_file('mx_tool_box');
        
		// Make a local reference to the ExpressionEngine super object

		if(defined('SITE_ID') == FALSE)
		define('SITE_ID', $PREFS->ini('site_id'));

		$this->base	 	 = BASE.AMP.'C=modules'.AMP.'M='.$this->module_name;
		$this->form_base = 'C=modules'.AMP.'M='.$this->module_name;
        
        $DSP->body .= $DSP->qdiv('itemWrapper', $DSP->heading($DSP->anchor($this->base. 
                                                                           AMP.'P=export_fields', 
                                                                           $LANG->line('export_fields')), 
                                                                           5)); 
        
        global $IN; 
         
        if ($switch) 
        { 
            switch($IN->GBL('P')) 
            { 
                 case 'import_fields'            :    $this->import_fields(); 
                     break;     
                 case 'export_fields'          :    $this->export_fields(); 
                     break; 
                 case 'layouts_clone'             :    $this->layouts_clone(); 
                     break; 
                 case 'fields_order'          :    $this->fields_order(); 
                     break; 
                 case 'fields_clone'          :    $this->fields_clone(); 
                     break; 
                 case 'clone_index'          :    $this->clone_index(); 
                     break; 
                 default                :    $this->export_fields(); 
                     break; 
            } 
        } 
        
        // uncomment this if you want navigation buttons at the top
		/*		$this->EE->cp->set_right_nav(array(
				$LANG->line('home') => $this->base.AMP.'method=clone_index',
				$LANG->line('fields_clone')		=> $this->base.AMP.'method=clone_index',
				$LANG->line('fields_order')	=> $this->base.AMP.'method=fields_order',
				$LANG->line('layouts_clone')	=> $this->base.AMP.'method=layouts_clone',
				$LANG->line('export_fields')	=> $this->base.AMP.'method=export_fields',
				$LANG->line('import_fields')	=> $this->base.AMP.'method=import_fields'
				));*/
	}
    
    function mx_tool_box_module_install() 
	{				
		global $DB;
        
        $sql = array();
        $sql[] = $DB->insert_string( 'exp_modules', 
										array('module_name' 	=> $this->module_name,
											'module_version'			=> $this->version,
											'has_cp_backend'		=> 'y'
										)
									);

		// run all sql queries
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
        					
		
		//
		// Add additional stuff needed on module install here
		// 
																									
		return TRUE;
	}

	
	/**
	 * Uninstall the Mx_tool_box module
	 */
	function mx_tool_box_module_deinstall() 
	{ 				
		global $DB;
        $query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = '".$this->module_name."'"); 

		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";

		$sql[] = "DELETE FROM exp_modules WHERE module_name = '".$this->module_name."'";

		$sql[] = "DELETE FROM exp_actions WHERE class = '".$this->module_name."'";

		$sql[] = "DELETE FROM exp_actions WHERE class = '".$this->module_name."_CP'";
										
		return TRUE;
	}

	function index()
	{
		return $this->clone_index();
	}
	//	field_group ee_channels weblog_id

	function layouts_clone ()
	{
		$vars = array();
		$vars['message']  = false;
		$vars['export_out']  = false;
				$this->EE->load->model('weblog_model');

		$vars['weblog_data'] = $this->EE->weblog_model->get_channels()->result();
		$vars['member_groups'] = $this->EE->member_model->get_member_groups('',array('can_access_publish' => 'y'))->result();

		$vars['from']  = $this->EE->input->post('from');
		$vars['to']  = $this->EE->input->post('to');
		$vars['mbr_groups']  = $this->EE->input->post('mbr_groups');

		if (!empty($vars['from']) AND !empty($vars['to']) AND !empty($vars['mbr_groups'])) {

			$layout_id = $vars['from'];

			$data = $DB->where('layout_id', $layout_id)->get('exp_layout_publish', 1)->result_array();

			if (!empty($vars['mbr_groups'])) {
				$DB->where('weblog_id', $vars['to'])->where_in('member_group', $vars['mbr_groups'])->delete('exp_layout_publish');
			}

			foreach ($vars['mbr_groups'] as $val => $key )
			{
				$data[0]['layout_id'] = '';
				$data[0]['member_group'] = $key;
				$data[0]['weblog_id'] = $vars['to'];
				$DB->insert('exp_layout_publish', $data[0]);
			}

		}

		$query = $DB->query( "SELECT DISTINCT lp.layout_id, lp.layout_id as layout_id, ch.weblog_title as weblog_title,  ch.field_group as field_group, mg.group_title as group_title, mg.group_id as group_id, ch.weblog_id
		   FROM exp_layout_publish AS lp,
		   exp_channels AS ch,
		   exp_member_groups AS mg
		   WHERE lp.site_id = " . SITE_ID . " AND  mg.group_id = lp.member_group AND lp.weblog_id = ch.weblog_id
		   ORDER BY lp.weblog_id DESC" );

		$vars['layout_publish'] =	$query->result();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $layout )
			{

			}
		}

		foreach ($vars['layout_publish'] as $layout)
		{
			$vars['layout_dropdown'][$layout->layout_id] = $layout->weblog_title.' : '.$layout->group_title;
		}

		foreach ($vars['weblog_data'] as $channel)
		{
			$vars['weblog_dropdown'][$channel->weblog_id]  = $channel->weblog_title;
		}

        $this->EE->load->library('table');
        $this->EE->load->helper('form');
        $this->EE->load->model('tools_model');

        $this->EE->jquery->tablesorter('.mainTable', '{
			headers: {
			0: {sorter: false},
			2: {sorter: false}
		},
			widgets: ["zebra"]
		}');

        $this->EE->javascript->output('
									$(".toggle_all").toggle(
										function(){
											$("input.toggle").each(function() {
												this.checked = true;
											});
										}, function (){
											var checked_status = this.checked;
											$("input.toggle").each(function() {
												this.checked = false;
											});
										}
									);');

        $this->EE->javascript->compile();

		if (!empty($errors)) {
			$vars['message'] = $LANG->line('problems');
		}

		$vars['errors'] = (isset($errors)) ? $errors : false;
		$vars['group_packs'] =  $this->group_packs();

		return $this->content_wrapper('layouts_clone', $LANG->line('layouts_clone'), $vars);
	}

	function import_fields()
	{
		global $IN, $LANG;
        
        $vars = array();
		$ignor_a  = array();
		$vars['message']  = false;
		$vars['import_out']  = false;


		$vars['import']	=	$this->EE->input->post('import');
		$vars['names']	=	$this->EE->input->post('name');
		$vars['default_group']  =	$this->EE->input->post('default_group');
		$vars['groups']  =	$this->EE->input->post('groups');
		$vars['ignor']	=$this->EE->input->post('ignor');

		$vars['im_check']	= $this->EE->input->post('im_check');
		$vars['im_check']	= (!empty($vars['im_check'])) ? true : false;

		if (!empty($vars['import'])) {
			$out = unserialize($vars['import']);
			$weblog_fields = $out['weblog_fields'];
			$field_formatting = $out['field_formatting'];

			$out	=$DB->query( "SELECT *
										   FROM exp_weblog_fields
										   WHERE site_id = " . SITE_ID . "
											ORDER BY group_id" );

			foreach ($out->result()  as $field)
			{
				$u_name[]=$field->field_name;
			}

			if (!empty($vars['ignor'])) {
				foreach ($vars['ignor']  as $ignor_id)
				{
					$ignor_a[]=$ignor_id;
				}
			}

			foreach ($weblog_fields as $key => $field)
			{
				if (!empty($vars['names'][$field->field_id])) {
					$arr_imort[$key]->field_name = $field->field_name = $vars['names'][$field->field_id];
				}

				if (!empty($vars['groups'][$field->field_id])) {
					$weblog_fields[$key]->group_id = $vars['groups'][$field->field_id];
				}

				if (in_Array($field->field_id,$ignor_a)){
					unset($weblog_fields[$key]);
				}
				else{

					if (in_Array($field->field_name,$u_name)){
						$vars['import_out'][$field->field_id]['uniq']= 0;
						$vars['im_check'] = false;
						$errors = true;
					}

					$vars['import_out'][$field->field_id]['field_id']= $field->field_id;
					$vars['import_out'][$field->field_id]['field_name']=$field->field_name;
					$vars['import_out'][$field->field_id]['field_label']=$field->field_label;

				}
			}
			if (!$vars['im_check']) {
				$vars['import']	= serialize(array('weblog_fields' => $weblog_fields , 'field_formatting' =>$field_formatting));
			}
			else  {
				foreach ($weblog_fields as $key => $field)
				{
						$clone_id = $field->field_id;
						$field->field_id = '';

						$DB->insert('exp_weblog_fields', $field);
						$new_id = $DB->insert_id();

						$this->field_weblog_data($field->field_type,$new_id);
						$this->copy_field_format($new_id, $clone_id);
						$this->layout_data($new_id, false, $field->group_id);
				}
			}
		}

		if (!empty($errors)) {
			$vars['message'] = $LANG->line('problems');
		}

		$vars['errors'] = (isset($errors)) ? $errors : false;
		$vars['group_packs'] =  $this->group_packs();

		return $this->content_wrapper('import_fields', $LANG->line('import_fields'), $vars);
	}


	function export_fields()
	{
		global $IN, $DB, $LANG;
        
        $vars = array();
		$vars['message']  = false;
		$vars['export_out']  = false;
		$export = $IN->GBL('export', 'POST');
		if (!empty($export)) {

			$channel_fields = $DB->query( "SELECT *
								   FROM exp_weblog_fields
								   WHERE site_id = " . SITE_ID . "
								    AND field_id  IN ( " .implode  (",", $export) . " )
									ORDER BY group_id" )->result;
            
            $out['channel_fields']	= array();
            foreach ($channel_fields as $row)
            {
                $out['channel_fields'][] = $row;
            }

			/*$field_formatting	= $DB->query( "SELECT *
								   FROM exp_field_formatting
								   WHERE  field_id  IN ( " .implode  (",", $export) . " )
									" )->result;
                                    
            $out['field_formatting']	= array();
            foreach ($field_formatting as $row)
            {
                $out['field_formatting'][] = (object) $row;
            }*/


			$col_id  = array ();
			/*foreach ($out['channel_fields'] as $key => $field)
			{
				if ($field['field_type']	== 'matrix') {
					$field_settings =unserialize(base64_decode($field['field_settings']));
					$col_id = array_merge($col_id, $field_settings['col_ids']);
				}

			}*/
;
			if (!empty($col_id)) {
				$DB->where_in('col_id', $col_id);
				$DB->where('site_id', SITE_ID);
				$out['matrix'] = $DB->get('exp_matrix_cols')->result_array();
			}

			$vars['export_out']= serialize($out);
		}

		if (!empty($errors)) {
			$vars['message'] = $LANG->line('problems');
		}

		$vars['errors'] = (isset($errors)) ? $errors : false;
		$vars['field_packs'] =  $this->field_packs();
		$vars['group_packs'] =  $this->group_packs();

		return $this->content_wrapper('export_fields',$LANG->line('export_fields'), $vars);
	}

	function clone_index()
	{
global $DB;
		$vars = array();
		$vars['message']  = false;


			$new_settings = $this->EE->input->post('clone');

			if (isset ($new_settings['field_order'])) {

				$out	=$DB->query( "SELECT *
								   FROM exp_weblog_fields
								   WHERE site_id = " . SITE_ID . "
									ORDER BY group_id" );

				//rebuild the array
				$r = array();
				$u_name = array();
				$message = array();
				$errors = array();


				foreach ($out->result()  as $field)
				{
					$r[$field->field_id] = $field;
					$u_name[]=$field->field_name;
				}

				foreach ($new_settings['field_order'] as $field_order => $row_id)
				{
					$clone_id =   $new_settings['copy_'.$row_id];

					if (!in_Array($new_settings['name_'.$row_id],$u_name)){

						$data = $r[$clone_id];

						$data->field_id = '';
						$data->field_label = $new_settings['label_'.$row_id];
						$data->field_name = $new_settings['name_'.$row_id];

						if ($data->field_type == 'matrix') {
							$tmp_settings = unserialize(base64_decode($data->field_settings));
							$tmp_settings['col_ids'] = $this->matrix_cloner($tmp_settings);
							$data->field_settings = base64_encode (serialize($tmp_settings));
						}

						$DB->insert('exp_weblog_fields', $data);
						$new_id = $DB->insert_id();
						$this->field_weblog_data($data->field_type,$new_id);




						$u_name[] = $new_settings['name_'.$row_id];

						$this->copy_field_format($new_id, $clone_id);
						$this->layout_data($new_id, $clone_id,$data->group_id);



					}
					else {
						$errors[$clone_id][$row_id]['label'] = $new_settings['label_'.$row_id];
						$errors[$clone_id][$row_id]['name'] = $new_settings['name_'.$row_id];
						$errors[$clone_id][$row_id]['id'] = $row_id;
					}
				}
			}

		if (!empty($errors)) {
			$vars['message'] = $LANG->line('problems');
		}

		$vars['errors'] = (isset($errors)) ? $errors : false;
		$vars['field_packs'] =  $this->field_packs();
		$vars['group_packs'] =  $this->group_packs();
		return $this->content_wrapper('clone_fields', $LANG->line('field_clone'), $vars);

	}

	function fields_order()
	{
		global $DB;
        
        $vars = array();
		$vars['message']  = false;
		$vars['order'] = $this->EE->input->post('order');

		if (!empty($vars['order'])) {
			foreach ($vars['order'] as $field_id => $order)
			{
				if(((int)$order) != 0){
				$DB->set('field_order', (int)$order);
				$DB->where('field_id', $field_id);
				$DB->update('exp_weblog_fields');
				}
			}
		}

		$vars['field_packs'] =  $this->field_packs();
		$vars['group_packs'] =  $this->group_packs();
		return $this->content_wrapper('fields_order', $LANG->line('field_order'), $vars);

	}

	function matrix_cloner ($settings)
	{
		global $DB;
        
        $out =  array ();
		$out_i = 0;

		$columns_query = $DB->where_in('col_id', $settings['col_ids'])
		->get('matrix_cols');

		foreach ($columns_query->result_array() as $column ) {
			$column['col_id'] = '';
			$DB->insert('matrix_cols', $column);
			$col_id = $DB->insert_id();
			$columns['col_id_'.$col_id] = array('type' => 'text');
			$out [$out_i] = $col_id;
			$out_i++;
		};

		$this->EE->load->dbforge();
		$DBforge->add_column('matrix_data', $columns);

		return $out;

	}

	function layout_data($new_id, $clone_id, $group_id) {
global $DB;
						$DB->select('weblog_id');
						$DB->where('field_group', $group_id);
						$DB->where('site_id', SITE_ID);
						$cquery = $DB->get('channels');

						if ($cquery->num_rows() > 0)
						{
							$ch_ids = array();

							$default_settings = array(
								'visible'		=> 'TRUE',
								'collapse'		=> 'FALSE',
								'htmlbuttons'	=> 'FALSE',
								'width'			=> '100%'
							);

							foreach ($cquery->result_array() as $row)
							{
								$ch_ids[] = $row['weblog_id'];
							}

							$query = $DB->query( "SELECT *
							   FROM exp_layout_publish
							   WHERE site_id = " . SITE_ID . "
							   AND weblog_id  IN ( " .implode  (",", $ch_ids) . " )
								ORDER BY weblog_id" );

							if ($query->num_rows() > 0)
							{
								foreach ($query->result_array() as $layout )
								{
									$field_layout  = unserialize($layout['field_layout']);

									if ($clone_id) {
										$field_layout['publish'][$new_id] = $field_layout['publish'][$clone_id];
									}
									else {
										$field_layout['publish'][$new_id] = $default_settings;
									}

									$layout['field_layout'] = serialize($field_layout);

									$DB->where('layout_id', $layout['layout_id']);
									$DB->update('exp_layout_publish', $layout);
								}
							}
						}

	}

	function copy_field_format  ($to_id , $from_id, $data=false) {
		global $DB;
        
        if (!$data) {
			$DB->select('*');
			$DB->where('field_id', $from_id);
			$query = $DB->get('exp_field_formatting')->result_array();
		}

		foreach ($query as $field_formatting)
		{
			if ($field_formatting['field_id'] == $from_id) {
				$field_formatting['field_id'] = $to_id;
				$field_formatting['formatting_id'] = '';
				$DB->insert('exp_field_formatting',$field_formatting);
			}
		}
	}

	function field_weblog_data($field_type, $field_id ) {
        global $DB;
				switch($field_type)
				{
					case 'date'	:
						$DB->query("ALTER IGNORE TABLE exp_weblog_data ADD COLUMN field_id_".$field_id." int(10) NOT NULL DEFAULT 0");
						$DB->query("ALTER TABLE exp_weblog_data ADD COLUMN field_ft_".$field_id." tinytext NULL");
						$DB->query("ALTER TABLE exp_weblog_data ADD COLUMN field_dt_".$field_id." varchar(8) AFTER field_ft_".$field_id."");
					break;
					case 'rel'	:
						$DB->query("ALTER IGNORE TABLE exp_weblog_data ADD COLUMN field_id_".$field_id." int(10) NOT NULL DEFAULT 0");
						$DB->query("ALTER TABLE exp_weblog_data ADD COLUMN field_ft_".$field_id." tinytext NULL");
					break;
					default		:
						$DB->query("ALTER TABLE exp_weblog_data ADD COLUMN field_id_".$field_id."  text");
						$DB->query("ALTER TABLE exp_weblog_data ADD COLUMN field_ft_".$field_id."  tinytext NULL");
					break;
				}

	}

	function field_packs()
	{
        global $DB;
		$out	=$DB->query( "SELECT *
							   FROM exp_weblog_fields
							   WHERE site_id = " . SITE_ID . "
								ORDER BY group_id" );

		return $out;
	}
	function group_packs()
	{
		global $DB;
        $r = array();
		$out	=$DB->query( "SELECT *
							   FROM exp_field_groups
							   WHERE site_id = " . SITE_ID . "
								ORDER BY group_id" );
		foreach ($out->result  as $group)
		{
		  $r[$group['group_id']] = $group['group_name'];
		}
		return $r;
	}

	function content_wrapper($content_view, $lang_key, $vars = array())
	{
		global $PREFS;
        
        $vars['content_view'] = $content_view;
		$vars['_base'] = $this->base;
		$vars['_form_base'] = $this->form_base;
		$vars['img_path'] = $PREFS->ini('theme_folder_url');
		//$this->_set_cp_var( 'cp_page_title', lang($lang_key) );
		//$this->EE->cp->set_breadcrumb($this->base, lang('mx_tool_box_module_name'));
        
        $function = $content_view.'_view';

		return $this->$function($vars);
	}
    
    function export_fields_view($vars)
    {
        global $DSP, $LANG;
        if(empty($vars['export_out']))
        {

            $DSP->body .= $DSP->form_open($vars['_form_base']."&P=export_fields", '');
    		$DSP->body .= "

<table class=\"mainTable padTable\" id=\"event_table\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
<tbody>
<tr>
<th style=\"width:30px\">". $LANG->line('id')."</th>
<th>".$LANG->line('field_label')."</th>
<th colspan=\"3\">".$LANG->line('field_name')."</th>

</tr>


</tbody> 
<tbody>";


				$out="";
				$c_index = '';
			
				foreach ($vars['field_packs']->result  as $field)
				{
			//
					if  ($c_index != $field['group_id']) {
					
					$out .= '<tr><th  style="width:30px">'.$vars['group_packs'][$field['group_id']].'</th><th></th><th></th><th style="width:30px;"></th><th></th></tr><tr>';
					$c_index = $field['group_id'];
					}
					$out .= '<tr  id="field_'.$field['field_id'].'" rel="'.$field['field_id'].'"><td>'.$field['field_id'].'</td><td class="label">'.$field['field_label'].'</td><td class="name">'. $field['field_name'].'</td><td></td><td><input type="checkbox" name="export[]" value="'.$field['field_id'].'"></td></tr>';

				}
				$DSP->body .= $out;

				$DSP->body .= "

</tbody>
</table>


<p class=\"centerSubmit\">


				<input name=\"edit_field_group_name\" value=\"save\" class=\"submit\" type=\"submit\">&nbsp;&nbsp;					
</p>

</form>
";
}
else
{
$DSP->body .= "
<textarea rows=\"20\">".$vars['export_out']."</textarea>";
}


    }

		/**
	 * Set cp var
	 *
	 * @access     private
	 * @param string
	 * @param string
	 * @return     void
	 */
	private function _set_cp_var( $key, $val ) {
		if ( version_compare( APP_VER, '2.6.0', '<' ) ) {
			ee()->cp->set_variable( $key, $val );
		}
		else {
			ee()->view->$key = $val;
		}
	}

}

/* End of file mcp.mx_tool_box.php */
/* Location: ./system/expressionengine/third_party/mx_tool_box/mcp.mx_tool_box.php */