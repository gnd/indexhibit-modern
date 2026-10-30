<?php if (!defined('SITE')) exit('No direct script access allowed');

class upgrade_220
{
	var $error;
	var $messages;
	var $version = '2.2.0';
	var $charset = 'utf8';
	var $collate = 'utf8_unicode_ci';

	function upgrade()
	{
		$OBJ =& get_instance();
		
		// passwords are now stored with password_hash() which needs more than 32 chars
		$OBJ->access->db_ensure_password_column_length();
		
		$OBJ->db->updateArray(PX.'settings', array('version' => '2.2.0'), "adm_id = '1'");
	}
}
