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
class Mx_tool_box_mcp
{
	var $base;			// the base url for this module
	var $form_base;		// base url for forms
	var $module_name = "mx_tool_box";


	public function __construct( $switch = TRUE )
	{


		if(defined('SITE_ID') == FALSE)
		define('SITE_ID', ee()->config->item('site_id'));

		$this->base	 	 = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module_name;
		$this->form_base = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module_name;

        // uncomment this if you want navigation buttons at the top
        ee()->cp->set_right_nav(array(
			ee()->lang->line('home') => $this->base.AMP.'method=clone_index',
			ee()->lang->line('fields_clone')		=> $this->base.AMP.'method=clone_index',
			ee()->lang->line('fields_order')	=> $this->base.AMP.'method=fields_order',
			ee()->lang->line('layouts_clone')	=> $this->base.AMP.'method=layouts_clone',
			ee()->lang->line('export_fields')	=> $this->base.AMP.'method=export_fields',
			ee()->lang->line('import_fields')	=> $this->base.AMP.'method=import_fields'
        ));
        
        $sidebar = ee('CP/Sidebar')->make();
        $this->menu['home'] = $sidebar->addHeader(lang('mx_tool_box_module_name'));
        
        $list = $this->menu['home']
            ->addBasicList();
        $list->addItem(lang('fields_clone'), ee('CP/URL', 'addons/settings/mx_tool_box/clone_index'));
        $list->addItem(lang('fields_order'), ee('CP/URL', 'addons/settings/mx_tool_box/fields_order'));
        $list->addItem(lang('layouts_clone'), ee('CP/URL', 'addons/settings/mx_tool_box/layouts_clone'));
        $list->addItem(lang('export_fields'), ee('CP/URL', 'addons/settings/mx_tool_box/export_fields'));
        $list->addItem(lang('import_fields'), ee('CP/URL', 'addons/settings/mx_tool_box/import_fields'));
        $list->addItem(lang('gallery_to_files'), ee('CP/URL', 'addons/settings/mx_tool_box/gallery_to_files'));
        $list->addItem(lang('ee1_to_ee3'), ee('CP/URL', 'addons/settings/mx_tool_box/ee1_to_ee3'));
        $list->addItem(lang('set_ee1_rel_in_ee3'), ee('CP/URL', 'addons/settings/mx_tool_box/set_ee1_rel_in_ee3'));
        
        
	}

	public function index()
	{
		return '&nbsp';
	}
    
    
    public function gallery_to_files()
    {
        $word_separator = ee()->config->item('word_separator');

		ee()->load->helper('url');
        
        //categories
        $group = [
            'site_id'   => SITE_ID,
            'group_name'=> 'Files'
        ];
        ee()->db->insert('category_groups', $group);
        $cat_group_id = ee()->db->insert_id();
        
        $gal_q = ee()->db->select()
            ->from('galleries')
            ->get();
        if ($gal_q->num_rows()>0)
        {
            foreach ($gal_q->result_array() as $row)
            {
                //directory
                $dir = [
                    'site_id'   => SITE_ID,
                    'name'      => $row['gallery_full_name'],
                    'server_path'=>$row['gallery_upload_path'],
                    'url'       => $row['gallery_image_url']
                ];
                
                ee()->db->insert('upload_prefs', $dir);
                $dir_id = ee()->db->insert_id();
                
                $cat_q = ee()->db->select()
                    ->from('gallery_categories')
                    ->where('gallery_id', $row['gallery_id'])
                    ->get();
                $categories = [];
                if ($cat_q->num_rows()>0)
                {
                    foreach ($cat_q->result_array() as $gal_cat)
                    {
                        $category = [
                            'cat_name'  => $gal_cat['cat_name'],
                            'cat_description'  => $gal_cat['cat_description'],
                            'cat_order'  => $gal_cat['cat_order'],
                            'cat_url_title' => url_title($gal_cat['cat_name'], $word_separator, TRUE),
                            'site_id'   => SITE_ID,
                            'group_id'  => $cat_group_id
                        ];
                    }
                    ee()->db->insert('categories', $category);
                    $cat_id = ee()->db->insert_id();
                    $categories[$gal_cat['cat_id']] = $cat_id;
                }
                
                //images
                $images_q = ee()->db->select()
                    ->from('gallery_entries')
                    ->where('gallery_id', $row['gallery_id'])
                    ->get();
                foreach ($images_q->result_array() as $gal_image)
                {
                    $mime = ($gal_image['extension']=='.png')?'image/png':'image/jpeg';
                    $file = [
                        'site_id'   => SITE_ID,
                        'title'     => $gal_image['title'],
                        'upload_location_id'=>$dir_id,
                        'file_name' => $gal_image['filename'].$gal_image['extension'],
                        'description'=> $gal_image['caption'],
                        'credit'=> $gal_image['custom_field_two'],
                        'location'=> $gal_image['custom_field_one'],
                        'file_hw_original'=>$gal_image['height'].' '.$gal_image['width'],
                        'upload_date'=> $gal_image['entry_date'],
                        'modified_date'=> $gal_image['entry_date'],
                        'mime_type' => $mime
                    ];
                    ee()->db->insert('files', $file);
                    $file_id = ee()->db->insert_id();
                    
                    if (array_key_exists($gal_image['cat_id'], $categories))
                    {
                        $file_cat = [
                            'file_id'   => $file_id,
                            'cat_id'   => $categories[$gal_image['cat_id']]
                        ];
                        ee()->db->insert('file_categories', $file_cat);
                    }
                    
                }
            }
        }
        
        ee('CP/Alert')->makeStandard('mx_tool_box')
                    ->asSuccess()
                    ->withTitle(lang('i_complite'))
                    ->addToBody(lang('gallery_import_complete'))
                    ->defer();
                    
        ee()->functions->redirect(ee('CP/URL', 'addons/settings/mx_tool_box')->compile());
    }
    
    
    public function ee1_to_ee3()
    {
        if (ee()->input->post('weblog_id')!==false)
        {
            //map channel fiels
            $fields_map = [];
            $gallery_fields = [];
            $rel_fields = [];
            $old_fields_q = ee()->db->select('field_id, field_name, field_type, field_related_to')
                ->from('weblog_fields')
                ->get();
            foreach ($old_fields_q->result_array() as $row)
            {
                $new_field_q = ee()->db->select('field_id, field_name')
                    ->from('channel_fields')
                    ->where('field_name', $row['field_name'])
                    ->get();
                if ($new_field_q->num_rows()>0)
                {
                    $fields_map[$row['field_id']] = $new_field_q->row('field_id');
                    if ($row['field_type']=='rel')
                    {
                        if ($row['field_related_to']=='gallery')
                        {
                            $gallery_fields[] = $row['field_id'];
                        }
                        else
                        {
                            $rel_fields[] = $row['field_id'];
                        }
                    }
                }
            }
            
            //for gallery migration, we need names of upload directories
            $dir_map = [];
            $dir_q = ee()->db->select('id, name')
                ->from('upload_prefs')
                ->where('site_id', SITE_ID)
                ->get();
            foreach ($dir_q->result_array() as $row)
            {
                $gal_q = ee()->db->select('gallery_id, gallery_full_name')
                    ->from('galleries')
                    ->where('gallery_full_name', $row['name'])
                    ->get();
                if ($gal_q->num_rows()>0)
                {
                    $dir_map[$gal_q->row('gallery_id')] = $row['id'];
                }
            }
            
            //insert data
            $titles_q = ee()->db->select('entry_id, title, url_title, status, entry_date, edit_date, expiration_date')
                ->from('weblog_titles')
                ->where('weblog_id', ee()->input->post('weblog_id'))
                ->get();
            if ($titles_q->num_rows()>0)
            {
                foreach($titles_q->result_array() as $row)
                {
                    $title_insert = [
                        'site_id'   => SITE_ID,
                        'channel_id'=> ee()->input->post('channel_id'),
                        'author_id'  => 1,
                        'title'  => $row['title'],
                        'url_title'  => $row['url_title'],
                        'status'  => $row['status'],
                        'entry_date'  => $row['entry_date'],
                        'edit_date'  => $row['edit_date'],
                        'expiration_date'  => $row['expiration_date']
                    ];
                    ee()->db->insert('channel_titles', $title_insert);
                    $entry_id = ee()->db->insert_id();
                    
                    $data_q = ee()->db->select()
                        ->from('weblog_data')
                        ->where('entry_id', $row['entry_id'])
                        ->get();
                    $data = $data_q->row_array();
                    $data_insert = [
                        'entry_id'  => $entry_id,
                        'site_id'   => SITE_ID,
                        'channel_id'=> ee()->input->post('channel_id')
                    ];
                    foreach ($data as $key=>$val)
                    {
                        $num = str_replace('field_id_', '', $key);
                        if (array_key_exists($num, $fields_map))
                        {
                            //is it gallery field
                            if (in_array($num, $gallery_fields))
                            {
                                if ($val==0)
                                {
                                    $val = '';
                                }
                                else
                                {
                                    //grab gallery relation
                                    $gal_q = ee()->db->select('gallery_id, filename, extension')
                                        ->from('gallery_entries')
                                        ->join('weblog_relationships', 'gallery_entries.entry_id=weblog_relationships.rel_child_id', 'left')
                                        ->where('rel_id', $val)
                                        ->get();
                                    if ($gal_q->num_rows()>0)
                                    {
                                        $val = '{filedir_'.$dir_map[$gal_q->row('gallery_id')].'}'.$gal_q->row('filename').$gal_q->row('extension');
                                    }
                                }
                            }
                            
                            $idx = 'field_id_'.$fields_map[$num];
                            $data_insert[$idx] = $val;
                            $data_insert['field_ft_'.$fields_map[$num]] = 'none';
                        }
                    }
                    ee()->db->insert('channel_data', $data_insert);
                }
            }
            ee('CP/Alert')->makeStandard('mx_tool_box')
                    ->asSuccess()
                    ->withTitle(lang('i_complite'))
                    ->addToBody(lang('ee1_to_ee3_import_complete'))
                    ->defer();
                    
            ee()->functions->redirect(ee('CP/URL', 'addons/settings/mx_tool_box')->compile());
        }
        else
        {
            $vars = [];
            return $this->content_wrapper('ee1_to_ee3', ee()->lang->line('ee1_to_ee3'), $vars);
        }
    }
    
    
    public function set_ee1_rel_in_ee3()
    {
        //map channel fiels
        $fields_map = [];
        $old_fields_q = ee()->db->select('field_id, field_name, field_type, field_related_to')
            ->from('weblog_fields')
            ->where('field_type', 'rel')
            ->where('field_related_to', 'blog')
            ->get();
        foreach ($old_fields_q->result_array() as $row)
        {
            $new_field_q = ee()->db->select('field_id, field_name')
                ->from('channel_fields')
                ->where('field_name', $row['field_name'])
                ->get();
            if ($new_field_q->num_rows()>0)
            {
                $fields_map[$row['field_id']] = $new_field_q->row('field_id');
            }
        }
        
        //grab relation by url_title
        foreach ($fields_map as $old_field_id=>$new_field_id)
        {
            $entries_q = ee()->db->select('field_id_'.$old_field_id.' AS rel_id')
                ->from('weblog_data')
                ->where('field_id_'.$old_field_id.' !=', 0)
                ->get();
            foreach ($entries_q->result_array() as $row)
            {
                ee()->db->select('old_parent.title AS old_parent_title, old_parent.entry_id AS old_parent_id, old_child.title AS old_child_title, parent.entry_id AS parent_entry_id, child.entry_id AS child_entry_id')
                    ->from('weblog_relationships')
                    
                    ->join('weblog_titles AS old_parent', 'old_parent.entry_id=weblog_relationships.rel_parent_id', 'left')
                    ->join('channel_titles AS parent', 'old_parent.url_title=parent.url_title', 'left')
                    
                    ->join('weblog_titles AS old_child', 'old_child.entry_id=weblog_relationships.rel_child_id', 'left')
                    ->join('channel_titles AS child', 'old_child.url_title=child.url_title', 'left')
                    
                    ->where('rel_id', $row['rel_id']);
                //echo ee()->db->_compile_select();
                $rel_q = ee()->db->get();
                $imported_rels = [];
                foreach ($rel_q->result_array() as $row)
                {
                    if (!empty($row['child_entry_id']))
                    {
  
                            $new_rel = [
                                'parent_id' => $row['parent_entry_id'],
                                'child_id' => $row['child_entry_id'],
                                'field_id' => $new_field_id
                            ];
                            
                            ee()->db->insert('relationships', $new_rel);
    

                    }
                }
            }
        }
        
        ee('CP/Alert')->makeStandard('mx_tool_box')
                ->asSuccess()
                ->withTitle(lang('i_complite'))
                ->addToBody(lang('relationships_set'))
                ->defer();
                
        ee()->functions->redirect(ee('CP/URL', 'addons/settings/mx_tool_box')->compile());

    }
    
    
	//	field_group ee_channels channel_id

	public function layouts_clone ()
	{
		$vars = array();
		$vars['message']  = false;
		$vars['export_out']  = false;
				ee()->load->model('channel_model');

		$vars['channel_data'] = ee()->channel_model->get_channels()->result();
		$vars['member_groups'] = ee()->member_model->get_member_groups('',array('can_access_publish' => 'y'))->result();

		$vars['from']  = ee()->input->post('from');
		$vars['to']  = ee()->input->post('to');
		$vars['mbr_groups']  = ee()->input->post('mbr_groups');

		if (!empty($vars['from']) AND !empty($vars['to']) AND !empty($vars['mbr_groups'])) {

			$layout_id = $vars['from'];

			$data = ee()->db->where('layout_id', $layout_id)->get('exp_layout_publish', 1)->result_array();

			if (!empty($vars['mbr_groups'])) {
				ee()->db->where('channel_id', $vars['to'])->where_in('member_group', $vars['mbr_groups'])->delete('exp_layout_publish');
			}

			foreach ($vars['mbr_groups'] as $val => $key )
			{
				$data[0]['layout_id'] = '';
				$data[0]['member_group'] = $key;
				$data[0]['channel_id'] = $vars['to'];
				ee()->db->insert('exp_layout_publish', $data[0]);
			}

		}

		$query = ee()->db->query( "SELECT DISTINCT lp.layout_id, lp.layout_id as layout_id, ch.channel_title as channel_title,  ch.field_group as field_group, mg.group_title as group_title, mg.group_id as group_id, ch.channel_id
		   FROM exp_layout_publish AS lp,
		   exp_channels AS ch,
		   exp_member_groups AS mg
		   WHERE lp.site_id = " . SITE_ID . " AND  mg.group_id = lp.member_group AND lp.channel_id = ch.channel_id
		   ORDER BY lp.channel_id DESC" );

		$vars['layout_publish'] =	$query->result();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $layout )
			{

			}
		}

		foreach ($vars['layout_publish'] as $layout)
		{
			$vars['layout_dropdown'][$layout->layout_id] = $layout->channel_title.' : '.$layout->group_title;
		}

		foreach ($vars['channel_data'] as $channel)
		{
			$vars['channel_dropdown'][$channel->channel_id]  = $channel->channel_title;
		}

        ee()->load->library('table');
        ee()->load->helper('form');
        ee()->load->model('tools_model');

        ee()->jquery->tablesorter('.mainTable', '{
			headers: {
			0: {sorter: false},
			2: {sorter: false}
		},
			widgets: ["zebra"]
		}');

        ee()->javascript->output('
									$(".toggle_all").toggle(
										public function(){
											$("input.toggle").each(public function() {
												this.checked = true;
											});
										}, public function (){
											var checked_status = this.checked;
											$("input.toggle").each(public function() {
												this.checked = false;
											});
										}
									);');

        ee()->javascript->compile();

		if (!empty($errors)) {
			$vars['message'] = ee()->lang->line('problems');
		}

		$vars['errors'] = (isset($errors)) ? $errors : false;
		$vars['group_packs'] =  $this->group_packs();

		return $this->content_wrapper('layouts_clone', ee()->lang->line('layouts_clone'), $vars);
	}

	public function import_fields()
	{
		$vars = array();
		$ignor_a  = array();
		$vars['message']  = false;
		$vars['import_out']  = false;


		$vars['import']	=	ee()->input->post('import');
		$vars['names']	=	ee()->input->post('name');
		$vars['default_group']  =	ee()->input->post('default_group');
		$vars['groups']  =	ee()->input->post('groups');
		$vars['ignor']	=ee()->input->post('ignor');

		$vars['im_check']	= ee()->input->post('im_check');
		$vars['im_check']	= (!empty($vars['im_check'])) ? true : false;
        
        $cols_q = ee()->db->query("SHOW FIELDS FROM exp_channel_fields");
        $cols = [];
        foreach ($cols_q->result_array() as $row)
        {
            $cols[] = $row['Field'];
        }

		if (!empty($vars['import'])) {

			$out = unserialize($vars['import']);

			$channel_fields = $out['channel_fields'];
			$field_formatting = (isset($out['field_formatting']))?$out['field_formatting']:[];

			$out	=ee()->db->query( "SELECT *
										   FROM exp_channel_fields
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

			foreach ($channel_fields as $key => $field)
			{
				$field_id = (is_object($field))?$field->field_id:$field['field_id'];
                $field_name = (is_object($field))?$field->field_name:$field['field_name'];
                $field_label = (is_object($field))?$field->field_label:$field['field_label'];
                if (!empty($vars['names'][$field_id])) {
					if (!isset($arr_imort[$key]))
                    {
                        $arr_imort[$key] = new stdClass();
                    }
                    $arr_imort[$key]->field_name = $field_name = $vars['names'][$field_id];
				}

				if (!empty($vars['groups'][$field_id])) {
                    if (is_object($field))
                    {
                        $channel_fields[$key]->group_id = $vars['groups'][$field_id];
                    }
                    else
                    {
                        $channel_fields[$key]['group_id'] = $vars['groups'][$field_id];
                    }
				}
                
                if (is_array($field))
                {
                    if ($field['field_type']=='rel')
                    {
                        if ($field['field_related_to']=='blog')
                        {
                            $field['field_type'] = 'relationship';
                        }
                        elseif ($field['field_related_to']=='gallery')
                        {
                            $field['field_type'] = 'file';
                        }
                    }
                    elseif ($field['field_type']=='wysiwyg')
                    {
                        $field['field_type'] = 'rte';
                    }
                }
                
                

				if (in_Array($field_id,$ignor_a)){
					unset($channel_fields[$key]);
				}
				else{

					if (in_Array($field_name,$u_name)){
						$vars['import_out'][$field_id]['uniq']= 0;
						$vars['im_check'] = false;
						$errors = true;
					}

					$vars['import_out'][$field_id]['field_id']= $field_id;
					$vars['import_out'][$field_id]['field_name']=$field_name;
					$vars['import_out'][$field_id]['field_label']=$field_label;

				}
			}
			if (!$vars['im_check']) {
				$vars['import']	= serialize(array('channel_fields' => $channel_fields , 'field_formatting' =>$field_formatting));
			}
			else  {
				foreach ($channel_fields as $key => $field)
				{
						if (is_array($field))
                        {
                            if ($field['field_type']=='rel')
                            {
                                if ($field['field_related_to']=='blog')
                                {
                                    $field['field_type'] = 'relationship';
                                }
                                elseif ($field['field_related_to']=='gallery')
                                {
                                    $field['field_type'] = 'file';
                                }
                            }
                            elseif ($field['field_type']=='wysiwyg')
                            {
                                $field['field_type'] = 'rte';
                            }
                        }
                        
                        $field_id = (is_object($field))?$field->field_id:$field['field_id'];
                        $field_type = (is_object($field))?$field->field_type:$field['field_type'];
                        $group_id = (is_object($field))?$field->group_id:$field['group_id'];
                        
                        $clone_id = $field_id;
                        if ((is_object($field)))
                        {
						  $field->field_id = '';
                        }
                        else
                        {
                            $field['field_id'] = '';
                        }
                        
                        $fields_row = [];
                        foreach ((array)$field as $key=>$item)
                        {
                            if (in_array($key, $cols))
                            {
                                $fields_row[$key] = $item;
                            }
                        }

						ee()->db->insert('exp_channel_fields', $fields_row);
						$new_id = ee()->db->insert_id();

						$this->field_channel_data($field_type,$new_id);
						//$this->copy_field_format($new_id, $clone_id);
						$this->layout_data($new_id, false, $group_id);
				}
			}
		}

		if (!empty($errors)) {
			$vars['message'] = ee()->lang->line('problems');
		}

		$vars['errors'] = (isset($errors)) ? $errors : false;
		$vars['group_packs'] =  $this->group_packs();

		return $this->content_wrapper('import_fields', ee()->lang->line('import_fields'), $vars);
	}


	public function export_fields()
	{
		$vars = array();
		$vars['message']  = false;
		$vars['export_out']  = false;
		$export = ee()->input->post('export');
		if (!empty($export)) {

			$out['channel_fields']	=ee()->db->query( "SELECT *
								   FROM exp_channel_fields
								   WHERE site_id = " . SITE_ID . "
								    AND field_id  IN ( " .implode  (",", $export) . " )
									ORDER BY group_id" )->result();

			/*$out['field_formatting']	= ee()->db->query( "SELECT *
								   FROM exp_field_formatting
								   WHERE  field_id  IN ( " .implode  (",", $export) . " )
									" )->result();*/


			$col_id  = array ();
			foreach ($out['channel_fields'] as $key => $field)
			{
				if ($field->field_type	== 'matrix') {
					$field_settings =unserialize(base64_decode($field->field_settings));
					$col_id = array_merge($col_id, $field_settings['col_ids']);
				}

			}
;
			if (!empty($col_id)) {
				ee()->db->where_in('col_id', $col_id);
				ee()->db->where('site_id', SITE_ID);
				$out['matrix'] = ee()->db->get('exp_matrix_cols')->result_array();
			}

			$vars['export_out']= serialize($out);
		}

		if (!empty($errors)) {
			$vars['message'] = ee()->lang->line('problems');
		}

		$vars['errors'] = (isset($errors)) ? $errors : false;
		$vars['field_packs'] =  $this->field_packs();
		$vars['group_packs'] =  $this->group_packs();

		return $this->content_wrapper('export_fields',ee()->lang->line('export_fields'), $vars);
	}

	public function clone_index()
	{

		$vars = array();
		$vars['message']  = false;


			$new_settings = ee()->input->post('clone');

			if (isset ($new_settings['field_order'])) {

				$out	=ee()->db->query( "SELECT *
								   FROM exp_channel_fields
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

						ee()->db->insert('exp_channel_fields', $data);
						$new_id = ee()->db->insert_id();
						$this->field_channel_data($data->field_type,$new_id);




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
			$vars['message'] = ee()->lang->line('problems');
		}

		$vars['errors'] = (isset($errors)) ? $errors : false;
		$vars['field_packs'] =  $this->field_packs();
		$vars['group_packs'] =  $this->group_packs();
		return $this->content_wrapper('clone_fields', ee()->lang->line('field_clone'), $vars);

	}

	public function fields_order()
	{
		$vars = array();
		$vars['message']  = false;
		$vars['order'] = ee()->input->post('order');

		if (!empty($vars['order'])) {
			foreach ($vars['order'] as $field_id => $order)
			{
				if(((int)$order) != 0){
				ee()->db->set('field_order', (int)$order);
				ee()->db->where('field_id', $field_id);
				ee()->db->update('exp_channel_fields');
				}
			}
		}

		$vars['field_packs'] =  $this->field_packs();
		$vars['group_packs'] =  $this->group_packs();
		return $this->content_wrapper('fields_order', ee()->lang->line('field_order'), $vars);

	}

	public function matrix_cloner ($settings)
	{
		$out =  array ();
		$out_i = 0;

		$columns_query = ee()->db->where_in('col_id', $settings['col_ids'])
		->get('matrix_cols');

		foreach ($columns_query->result_array() as $column ) {
			$column['col_id'] = '';
			ee()->db->insert('matrix_cols', $column);
			$col_id = ee()->db->insert_id();
			$columns['col_id_'.$col_id] = array('type' => 'text');
			$out [$out_i] = $col_id;
			$out_i++;
		};

		ee()->load->dbforge();
		ee()->dbforge->add_column('matrix_data', $columns);

		return $out;

	}

	public function layout_data($new_id, $clone_id, $group_id) {

						ee()->db->select('channel_id');
						ee()->db->where('field_group', $group_id);
						ee()->db->where('site_id', SITE_ID);
						$cquery = ee()->db->get('channels');

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
								$ch_ids[] = $row['channel_id'];
							}

							$query = ee()->db->query( "SELECT *
							   FROM exp_layout_publish
							   WHERE site_id = " . SITE_ID . "
							   AND channel_id  IN ( " .implode  (",", $ch_ids) . " )
								ORDER BY channel_id" );

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

									ee()->db->where('layout_id', $layout['layout_id']);
									ee()->db->update('exp_layout_publish', $layout);
								}
							}
						}

	}

	public function copy_field_format  ($to_id , $from_id, $data=false) {
		if (!$data) {
			ee()->db->select('*');
			ee()->db->where('field_id', $from_id);
			$query = ee()->db->get('exp_field_formatting')->result_array();
		}

		foreach ($query as $field_formatting)
		{
			if ($field_formatting['field_id'] == $from_id) {
				$field_formatting['field_id'] = $to_id;
				$field_formatting['formatting_id'] = '';
				ee()->db->insert('exp_field_formatting',$field_formatting);
			}
		}
	}

	public function field_channel_data($field_type, $field_id ) {

				switch($field_type)
				{
					case 'date'	:
						ee()->db->query("ALTER IGNORE TABLE exp_channel_data ADD COLUMN field_id_".$field_id." int(10) NOT NULL DEFAULT 0");
						ee()->db->query("ALTER TABLE exp_channel_data ADD COLUMN field_ft_".$field_id." tinytext NULL");
						ee()->db->query("ALTER TABLE exp_channel_data ADD COLUMN field_dt_".$field_id." varchar(8) AFTER field_ft_".$field_id."");
					break;
					case 'rel'	:
						ee()->db->query("ALTER IGNORE TABLE exp_channel_data ADD COLUMN field_id_".$field_id." int(10) NOT NULL DEFAULT 0");
						ee()->db->query("ALTER TABLE exp_channel_data ADD COLUMN field_ft_".$field_id." tinytext NULL");
					break;
					default		:
						ee()->db->query("ALTER TABLE exp_channel_data ADD COLUMN field_id_".$field_id."  text");
						ee()->db->query("ALTER TABLE exp_channel_data ADD COLUMN field_ft_".$field_id."  tinytext NULL");
					break;
				}

	}

	public function field_packs()
	{

		$out	=ee()->db->query( "SELECT *
							   FROM exp_channel_fields
							   WHERE site_id = " . SITE_ID . "
								ORDER BY group_id" );

		return $out;
	}
	public function group_packs()
	{
		$r = array();
		$out	=ee()->db->query( "SELECT *
							   FROM exp_field_groups
							   WHERE site_id = " . SITE_ID . "
								ORDER BY group_id" );
		foreach ($out->result()  as $group)
		{
					$r[$group->group_id] = $group->group_name;
		}
		return $r;
	}

	public function content_wrapper($content_view, $lang_key, $vars = array())
	{
		$vars['content_view'] = $content_view;
		$vars['_base'] = $this->base;
		$vars['_form_base'] = $this->form_base;
		$vars['img_path'] = ee()->config->item('theme_folder_url');
		$this->_set_cp_var( 'cp_page_title', lang($lang_key) );
		ee()->cp->set_breadcrumb($this->base, lang('mx_tool_box_module_name'));

		return ee()->load->view('_wrapper', $vars, TRUE);
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