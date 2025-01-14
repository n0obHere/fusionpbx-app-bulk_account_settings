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
	if (permission_exists('bulk_account_settings_devices')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//device options
	$device_options = [];
	$device_options[] = 'device_enabled';
	$device_options[] = 'device_profile_uuid';
	$device_options[] = 'device_template';
	$device_options[] = 'line_1_server_address';
	$device_options[] = 'line_1_server_address_primary';
	$device_options[] = 'line_1_server_address_secondary';
	$device_options[] = 'line_1_outbound_proxy_primary';
	$device_options[] = 'line_1_outbound_proxy_secondary';
	$device_options[] = 'line_1_sip_port';
	$device_options[] = 'line_1_sip_transport';
	$device_options[] = 'line_1_register_expires';

//use connected database
	$domain_uuid = $_SESSION['domain_uuid'] ?? '';
	$user_uuid = $_SESSION['user_uuid'] ?? '';
	$database = database::new(['config' => config::load(), 'domain_uuid' => $domain_uuid]);
	$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);

//check for the ids
	if (!empty($_REQUEST)) {

		$device_uuids = $_REQUEST["id"];
		$option_selected = check_str($_REQUEST["option_selected"]);
		$new_setting = check_str($_REQUEST["new_setting"]);

		//ensure the option selected is valid
		if (!empty($option_selected) && !in_array($option_selected, $device_options)) {
			die('invalid option');
		}

		foreach($device_uuids as $device_uuid) {
			$device_uuid = check_str($device_uuid);
			if (is_uuid($device_uuid)) {
				//line settings
				if (preg_match ('/line/', $option_selected)) {

					preg_match ('/line_(.)/', $option_selected, $matches);
					$line_number = $matches[1];
					$matches = null;
					preg_match ('/line_._(.*$)/', $option_selected, $matches);
					$option_line = $matches[1];

					$sql = "select * from v_device_lines ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and device_uuid = :device_uuid ";
					$sql .= "and line_number = :line_number ";
					$parameters = [];
					$parameters['domain_uuid'] = $domain_uuid;
					$parameters['device_uuid'] = $device_uuid;
					$parameters['line_number'] = $line_number;
					$devices = $database->select($sql, $parameters, 'all');
					if (is_array($devices)) {
						foreach ($devices as &$row) {
							$device_line_uuid = $row["device_line_uuid"];
						}
					}

					$array["device_lines"][$i]["device_line_uuid"] = $device_line_uuid;
					$array["device_lines"][$i][$option_line] = $new_setting;
					$array["device_lines"][$i]["domain_uuid"] = $domain_uuid;
					$array["device_lines"][$i]["device_uuid"] = $device_uuid;
					$database->app_name = 'bulk_account_settings';
					$database->app_uuid = '6b4e03c9-c302-4eaa-b16d-e1c5c08a2eb7';
					$database->save($array);
				}
				//other device settings
				else {
					$array["devices"][$i]["domain_uuid"] = $domain_uuid;
					$array["devices"][$i]["device_uuid"] = $device_uuid;
					$array["devices"][$i][$option_selected] = $new_setting;
					$database->app_name = 'bulk_account_settings';
					$database->app_uuid = '6b4e03c9-c302-4eaa-b16d-e1c5c08a2eb7';
					$database->save($array);
				}
			}
		}
	}

//redirect the browser
	$_SESSION["message"] = $text['message-update'];
	header("Location: bulk_account_settings_devices.php?option_selected=".$option_selected."");
	return;
