<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	KonradSC <konrd@yahoo.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('bulk_account_settings_users')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//use connected database
	$domain_uuid = $_SESSION['domain_uuid'] ?? '';
	$user_uuid = $_SESSION['user_uuid'] ?? '';
	$database = database::new(['config' => config::load(), 'domain_uuid' => $domain_uuid]);
	$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);

//set valid user options
	$user_options = [];
	$user_options[] = 'user_enabled';
	$user_options[] = 'group';
	$user_options[] = 'password';
	$user_options[] = 'user_status';
	$user_options[] = 'time_zone';

//check for the ids
	if (!empty($_REQUEST)) {
		$user_uuids = $_REQUEST["id"];
		$option_selected = $_REQUEST["option_selected"];
		if (!in_array($option_selected, $user_options, true)) {
			die('Invalid option');
		}
		$new_setting = $_REQUEST["new_setting"];
		$i = 0;
		foreach($user_uuids as $user_uuid) {
			$user_uuid = check_str($user_uuid);
			if (is_uuid($user_uuid)) {
				//user_status or user_enabled
				if($option_selected == "user_status" || $option_selected == "user_enabled"){
					$array["users"][$i]["domain_uuid"] = $domain_uuid;
					$array["users"][$i]["user_uuid"] = $user_uuid;
					$array["users"][$i][$option_selected] = $new_setting;
				}

				//password
				if($option_selected == "password"){
					$array["users"][$i]["domain_uuid"] = $domain_uuid;
					$array["users"][$i]["user_uuid"] = $user_uuid;
					// set strength from 0 to 4
					$password_strength = 0;
					$password_strength += intval($settings->get('user', 'password_number', true));
					$password_strength += intval($settings->get('user', 'password_lowercase', true));
					$password_strength += intval($settings->get('user', 'password_uppercase', true));
					$password_strength += intval($settings->get('user', 'password_special', true));
					//generate a password using strength and length (default of 20 characters if not set)
					$array['users'][$i]['password'] = generate_password($settings->get('user', 'password_length', 20), $password_strength);
				}

				//timezone
				if($option_selected == "time_zone"){
					$sql = 'select user_setting_uuid from v_user_settings ';
					$sql .= 'where domain_uuid = :domain_uuid ';
					$sql .= 'and user_uuid = :user_uuid ';
					$sql .= "and user_setting_subcategory = 'time_zone' ";
					$parameters = [];
					$parameters['domain_uuid'] = $domain_uuid;
					$parameters['user_uuid'] = $user_uuid;
					$user_setting_uuid = $database->select($sql, $parameters, 'column');
					if (!empty($user_setting_uuid)) {
						$array["user_settings"][$i]["domain_uuid"] = $domain_uuid;
						$array["user_settings"][$i]["user_uuid"] = $user_uuid;
						$array["user_settings"][$i]["user_setting_uuid"] = $user_setting_uuid;
						$array["user_settings"][$i]["user_setting_value"] = $new_setting;
					}
				}
				$i++;
			}
		}
		$database->app_name = 'bulk_account_settings';
		$database->app_uuid = '6b4e03c9-c302-4eaa-b16d-e1c5c08a2eb7';
		$database->save($array);
		//$message = $database->message;
	}

//redirect the browser
	$_SESSION["message"] = $text['message-update'];
	header("Location: bulk_account_settings_users.php?option_selected=".$option_selected."");
	return;
