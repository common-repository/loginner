<?php
/*
Plugin Name: Loginner
Plugin URI: http://www.duckinformatica.it
Description: Loginner lets you open other Wordpress backends under your control in one click.
Author: duckinformatica 
Version: 1.0
Author URI: http://www.duckinformatica.it
*/

/*
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2, 
    as published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/


// ACTION AND FILTERS

//add_action('init', 'loginner_init');
add_filter('plugin_action_links', 'loginner_add_settings_link', 10, 2 );
add_action('admin_menu', 'loginner_menu');
add_action('admin_bar_menu', 'loginner_admin_bar', 1000 );


//GET ARRAY OF STORED VALUES
$loginner_options = loginner_get_options_stored();


// PUBLIC FUNCTIONS

function loginner_init() {
	// DISABLED IN THE ADMIN PAGES
	if (is_admin()) {
		//wp_enqueue_script('jquery-ui-sortable');
		return;
	}
}    


function loginner_menu() {
	add_options_page('Loginner', 'Loginner', 'manage_options', 'loginner_options', 'loginner_options');
}


function loginner_add_settings_link($links, $file) {
	static $this_plugin;
	if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);
 
	if ($file == $this_plugin){
		$settings_link = '<a href="admin.php?page=loginner_options">'.__("Settings").'</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
} 


function loginner_admin_bar () {
	global $wp_admin_bar, $loginner_options;
	
	$current_user = wp_get_current_user();
	if ( !is_array($loginner_options['user']) or !in_array($current_user->user_login, $loginner_options['user']) or !is_admin_bar_showing() ) {
		return;
	}
	
	/* Add the main siteadmin menu item */
	$wp_admin_bar->add_menu( array( 'id' => 'loginner', 'title' =>  'Loginner' ));
	
	$i = 0;

	foreach ($loginner_options['account'] as $dati) {
		if ($dati['url']=='') {
			continue;
		}
		
		if ($dati['type']=='wordpress') {
			$wp_admin_bar->add_menu( array( 'id' => 'loginner_'.$i, 
				'parent' => 'loginner',
				'title' =>  $dati['name'], 
				'href' => 'javascript:void(0);',
				'meta' => array(
					'onclick' => 'javascript:document.loginner_form_'.$i.'.submit();'
				)
				));
			echo '
				<div style="display:none;">
					<form action="'.$dati['url'].'/wp-login.php" name="loginner_form_'.$i.'" target="_blank" method="post">
					<input type="hidden" name="log" value="'.$dati['user'].'" />
					<input type="hidden" name="pwd" value="'.$dati['pass'].'" />
					</form>
				</div>
			';
		}
		$i++;
	}
}


function loginner_options () {
	
	$option_name = 'loginner';

	//must check that the user has the required capability 
	if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	$out = '';
	
	// SETTINGS FORM

	// See if the user has posted us some information
	if( isset($_POST[$option_name.'_last_account'])) {
		$option = array();

		for ($i=0; $i<=$_POST[$option_name.'_last_account']; $i++) {
			if ($_POST[$option_name.'_'.$i.'name']=='') {
				continue;
			}
			$option['account'][] = array('type'=>'wordpress', 
				'name'=> esc_html($_POST[$option_name.'_'.$i.'name']), 
				'url'=>  esc_html($_POST[$option_name.'_'.$i.'url']), 
				'user'=> esc_html($_POST[$option_name.'_'.$i.'user']), 
				'pass'=> esc_html($_POST[$option_name.'_'.$i.'pass'])
				);
		}
		for ($i=0; $i<=$_POST[$option_name.'_last_wpuser']; $i++) {
			$option['user'][] = esc_html($_POST[$option_name.'_'.$i.'wpuser']);
		}
		
		//$option['debug'] = (isset($_POST[$option_name.'_debug']) and $_POST[$option_name.'_debug']=='on') ? true : false;
		update_option($option_name, $option);
		// Put a settings updated message on the screen
		$out .= '<div class="updated"><p><strong>'.__('Settings saved.', 'menu-test' ).'</strong></p></div>';
	}
	
	//GET (EVENTUALLY UPDATED) ARRAY OF STORED VALUES
	$option = loginner_get_options_stored();


	$out .= '
	<style>
		#loginner_form h3 { cursor: default; }
		#loginner_form input[type=text] { width:170px; margin:0; padding:0; }
		#poststuff ul { list-style-position: inside; list-style-type: square; }
	</style>
	
	<div class="wrap">
	<h2>'.__( 'Loginner by Duck Informatica', 'menu-test' ).'</h2>
	<div id="poststuff" style="padding-top:10px; position:relative;">

	<div style="float:left; width:700px; padding-right:1%;">

		<form id="loginner_form" name="form1" method="post" action="">
			
			Manage your accounts, one account per line (all fields are mandatory):<br /><br />
			
			<table id="sct_tests" class="widefat">
			<thead>
			<tr>
				<th style="width:100px;">Account Name</th>
				<th style="width:200px;">Site url</th>
				<th style="width:200px;">Username</th>
				<th style="width:200px;">Password</th>
			</tr>
			</thead>
		';
			
		$i = 0;
		foreach (array_keys($option['account']) as $acc) {
			$out .= '
				<tr>
					<td><input type="text" name="'.$option_name.'_'.$i.'name" value="'.stripslashes($option['account'][$acc]['name']).'" /></td>
					<td><input type="text" name="'.$option_name.'_'.$i.'url"  value="'.stripslashes($option['account'][$acc]['url']).'" /></td>
					<td><input type="text" name="'.$option_name.'_'.$i.'user" value="'.stripslashes($option['account'][$acc]['user']).'" /></td>
					<td><input type="text" name="'.$option_name.'_'.$i.'pass" value="'.stripslashes($option['account'][$acc]['pass']).'" /></td>
				</tr>
				';
			$i++;
		}

		for ($j=$i; $j<=$i+1; $j++) {
			$out .= '
					<tr>
						<td><input type="text" name="'.$option_name.'_'.$j.'name" value="" /></td>
						<td><input type="text" name="'.$option_name.'_'.$j.'url"  value="" /></td>
						<td><input type="text" name="'.$option_name.'_'.$j.'user" value="" /></td>
						<td><input type="text" name="'.$option_name.'_'.$j.'pass" value="" /></td>
					</tr>
					';
		}
		
		$out .= '
			</table>
			<br />
			Make Loginner available to the following users:
			<br/>
			<ul style="list-style: none outside none;">
			';
		
		$users = get_users( $args );
		
		$k = 0;
		foreach ($users as $user) {
				$enabled = (is_array($option['user']) and in_array($user->user_login, $option['user'])) ? 'checked="checked"' : '';
        $out .=  '<li><input type="checkbox" name="'.$option_name.'_'.$k.'wpuser" value="'.$user->user_login.'" '.$enabled.' /> '
					.$user->user_login.' ('.$user->display_name.')</li>';
				$k++;
    }
		$out .= '
			</ul>
	
			<p class="submit">
				<input type="hidden" name="'.$option_name.'_last_account" value="'.$j.'" />
				<input type="hidden" name="'.$option_name.'_last_wpuser" value="'.($k-1).'" />
				<input type="submit" name="Submit" class="button-primary" value="'.esc_attr('Save Changes').'" />
			</p>
			<br />
			'
			.loginner_box_content('Instructions &amp; info', '
				<ul>
					<li>Site Url parameter example: http://www.test.com</li>
					<li>It\'s safer to store and assign only non-administrative accounts</li>
					<li>To delete an account, empty the account name</li>
				</ul>
				<br />
				WARNING: THIS PLUGIN CAN BE INSECURE IF NOT USED CAUTIOUSLY. USE IT AT YOUR RISK!
			')
			.loginner_box_content('Additional info', '
				For more info and plugins by Duck Informatica: 
				<b><a href="http://www.duckinformatica.it/prodotti-e-servizi/free-wordpress-plugins/" target="_blank">www.duckinformatica.it</a></b>
			')
			.'
		</form>
	</div>
	
	</div>
	</div>
	';

	echo $out; 
	return;
}




// PRIVATE FUNCTIONS

function loginner_box_content ($title, $content) {
	if (is_array($content)) {
		$content_string = '<table>';
		foreach ($content as $name=>$value) {
			$content_string .= '<tr>
				<td style="width:130px;">'.__($name, 'menu-test' ).':</td>	
				<td>'.$value.'</td>
				</tr>';
		}
		$content_string .= '</table>';
	} else {
		$content_string = $content;
	}

	$out = '
		<div class="postbox">
			<h3>'.__($title, 'menu-test' ).'</h3>
			<div class="inside">'.$content_string.'</div>
		</div>
		';
	return $out;
}


function loginner_get_options_stored () {
	//GET ARRAY OF STORED VALUES
	$options = get_option('loginner');
	 
	if(!is_array($options)) {
		$options = array();
	}	
	
//	$options_default = array();
	
	// MERGE DEFAULT AND STORED OPTIONS
//	$options = array_merge($options_default, $options);
	
	return $options;
}
