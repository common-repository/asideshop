<?php
/* 
Plugin Name: AsideShop
Plugin URI: http://wordpress.org/extend/plugins/asideshop
Description: A WordPress plugin which allows you to create templates for your asides posts. Instantly.
Version: 1.2
Author: Raimonds Kalnins
*/
/*  
	AsideShop
	A WordPress plugin
	
	Copyright (c) 2008 Raimonds Kalnins

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Prevent direct access to the plugin
if (!defined('ABSPATH')) {
	exit();
}

// Version number, may be used to update things in the future
global $asideshop_version;
$asideshop_version = '1.2';

// Domain for I18N
global $asideshop_domain;
$asideshop_domain = 'asideshop';

// Variable lets us know whether the loop has been ended.
global $asideshop_loop_ended;
$asideshop_loop_ended = FALSE;

// Variable which permits one ob_end_clean() for every ob_start(). Without it other plugins which use ob_start() break AsideShop.
global $asideshop_ob_started;
$asideshop_ob_started = 0;

// Template tags, used in templates
global $asideshop_patterns;
$asideshop_patterns = array(
	"%post_id%"					=> 'get_the_ID()',
	"%post_title%"				=> '$post->post_title',
	"%post_content%"			=> 'get_the_content(\'\')',
	"%post_content_filtered%"	=> 'apply_filters(\'the_content\', get_the_content(\'\'))',
	"%post_excerpt%"			=> '$post->post_excerpt',
	"%post_excerpt_filtered%"	=> 'apply_filters(\'get_the_excerpt\', $post->post_excerpt)',
	"%post_permalink%"			=> 'get_permalink()',
	"%post_date%"				=> 'the_date(\'\', \'\', \'\', FALSE)',
	"%post_date_regular%"		=> 'get_the_time(get_option(\'date_format\'))',
	"%post_time%"				=> 'get_the_time()',
	"%post_author%"				=> 'get_the_author()',
	"%post_categories%"			=> 'get_the_category_list(\', \')',
	"%post_tags%"				=> 'get_the_tag_list(\'\', \', \', \'\')',
	"%comments_url%"			=> '($post->comment_count == 0) ? get_permalink() . \'#respond\' : get_comments_link()', 
	"%comments_count%"			=> '$post->comment_count',
	"%trackback_url%"			=> 'trackback_url(FALSE)',
);

// Options where to display aside posts
global $asideshop_show_on_array;
$asideshop_show_on_array = array('front_page' => 1, 'category_view' => 0, 'tag_view' => 0, 'archive_view' => 0, 'date_view' => 0, 'author_view' => 0, 'search_view' => 1);

/** 
	// This plugin uses these options created upon plugin installation.
	
	$as_options = array(
		'enabled'				=> '',					// 0 - disabled || 1 - enabled || 2 - testing
		'version'				=> '',					// string (e.g. 1.0)
		'show_asides'			=> array(),				// array(category_id => 1)
		'templates'				=> array(
									array(
										'title' => '',		// string
										'content' => '',	// string
									),
		),
		'show_asides_on'		=> array(
									'front_page' => 1,	// integer
									'category_view' => 0, // integer
									'tag_view' => 0, // integer
									'archive_view' => 0, // integer
									'date_view' => 0, // integer
									'author_view' => 0, // integer
									'search_view' => 1, // integer
		),
		'category_to_template'	=> array(
									'' => '' 			// array(category_id => template_id)
		),
	);
*/

/**
 * asideshop_install() - Install plugin
 *
 * @global string $asideshop_version
 * @global string $asideshop_show_on_array
 * @return void
*/
function asideshop_install()
{
	global $asideshop_version, $asideshop_show_on_array;
	
	add_option('asideshop_options', array('enabled' => 0,
											'version' => $asideshop_version,
											'show_asides' => array(),
											'templates' => array(),
											'category_to_template' => array(),
											'show_asides_on' => $asideshop_show_on_array));
}

/**
 * asideshop_uninstall() - Uninstall plugin and remove all the options
 *
 * @return void
*/
function asideshop_uninstall()
{
	delete_option('asideshop_options');
}

/**
 * asideshop_create_options_page() - Initialize options page
 *
 * @return void
*/
function asideshop_create_options_page()
{
	add_options_page('AsideShop', 'AsideShop', 9, basename(__FILE__), 'asideshop_conf');
}

/**
 * $asideshop_has_loop_ended() - Check whether we are in The Loop
 *
 * @global bool $asideshop_loop_ended
*/
function asideshop_has_loop_ended()
{
	global $asideshop_loop_ended;
	
	if ($asideshop_loop_ended === TRUE) {
		return TRUE;
	}
	return FALSE;
}

/**
 * asideshop_is_enabled() - Check whether plugin is enabled
 *
 * @return bool
*/
function asideshop_is_enabled()
{
	$asideshop_options = get_option('asideshop_options');

	if ((asideshop_has_loop_ended() === FALSE && $asideshop_options['enabled'] == 1) || ($asideshop_options['enabled'] == 2 && current_user_can('activate_plugins'))) {
		return TRUE;
	}
	return FALSE;
}

/**
 * asideshop_is_displayable() - Check whether asides should be displayed
 *
 * @global array $asideshop_version
 * @return bool
*/
function asideshop_is_displayable()
{
	global $asideshop_version;
	
	$asideshop_options = get_option('asideshop_options');

	if (!array_key_exists('show_asides_on', $asideshop_options)) {
		return asideshop_is_frontpage();
	} else {
		if ($asideshop_options['show_asides_on']['front_page'] == 1 && asideshop_is_frontpage()) {
			return TRUE;
		} else if ($asideshop_options['show_asides_on']['search_view'] == 1 && is_search()) {
			return TRUE;
		} else if ($asideshop_options['show_asides_on']['archive_view'] == 1 && is_archive()) {
			return TRUE;
		} else if ($asideshop_options['show_asides_on']['category_view'] == 1 && is_category()) {
			return TRUE;
		} else if (function_exists('is_tag') && $asideshop_options['show_asides_on']['tag_view'] == 1 && is_tag()) {
			return TRUE;
		} else if ($asideshop_options['show_asides_on']['date_view'] == 1 && is_date()) {
			return TRUE;
		} else if ($asideshop_options['show_asides_on']['author_view'] == 1 && is_author()) {
			return TRUE;
		}
	}
	return FALSE;
}

/**
 * asideshop_is_frontpage() - Check whether current page is a frontpage 
 *
 * @return bool
 */
function asideshop_is_frontpage()
{
	// Backward compatibility with 2.2, pre-taxonomy version.
	$is_tag = FALSE;
	if (function_exists('is_tag') && is_tag()) {
		$is_tag = TRUE;
	}
	
	if (!is_archive() && !is_single() && !$is_tag && !is_search()) {
		return TRUE;
	}
	return FALSE;
}

/**
 * asideshop_get_categories() - Get categories
 * 
 * This function is used to get post categories rather than all categories.
 *
 * @global string $wp_version
 * @global object $wpdb
 * @return array
 */
function asideshop_get_categories()
{
	global $wp_version, $wpdb;

	// If 2.2.x
	if ((float)$wp_version < (float)"2.3") {
		$categories = $wpdb->get_results("SELECT `cat_ID`, `cat_name`, `category_parent` FROM `{$wpdb->categories}` WHERE `link_count` = 0 ORDER BY `cat_name` ASC");
	} else {
		// If 2.3 and later
		$categories = get_categories('hide_empty=0');
	}
	
	$result = array();
	
	if (!empty($categories) && is_array($categories)) {
		foreach ($categories AS $_cat) {
			// Backward compatibility with 2.2, pre-taxonomy version.
			if (empty($_cat->term_id)) {
				$cat_id			= $_cat->cat_ID;
				$cat_name		= $_cat->cat_name;
				$cat_parent		= $_cat->category_parent;
			} else {
				$cat_id			= $_cat->term_id;
				$cat_name		= $_cat->name;
				$cat_parent		= $_cat->category_parent;
			}
		
			$result[] = array(
				'cat_id'		=> $cat_id,
				'cat_name'		=> $cat_name,
				'cat_parent'	=> $cat_parent,
			);
		}
	}
	return $result;
}

/**
 * asideshop_setup() - Run misc. things before configuration panel appears
 *
 * @global string $asideshop_domain
 * @return string
*/
function asideshop_setup()
{
	global $asideshop_domain;
	
	asideshop_upgrade();
	
	load_plugin_textdomain($asideshop_domain, 'wp-content/plugins/' . $asideshop_domain);
}

/**
 * asideshop_upgrade() - Upgrade AsideShop options
 *
 * @return void
*/
function asideshop_upgrade()
{
	global $asideshop_show_on_array;
	
	$asideshop_options = get_option('asideshop_options');
	
	// Upgrade
	if (is_array($asideshop_options)) {
		if (!array_key_exists('show_asides_on', $asideshop_options)) {
			update_option('asideshop_options', $asideshop_options + array('show_asides_on' => $asideshop_show_on_array));
		} else {
			$asideshop_options['show_asides_on'] = $asideshop_options['show_asides_on'] + $asideshop_show_on_array;
			update_option('asideshop_options', $asideshop_options);
		}
	} else {
		asideshop_install();
	}
}

/**
 * asideshop_nonce_field() - Show nonce field if available
 *
 * @return mixed
*/
function asideshop_nonce_field() {
	if (!function_exists('wp_nonce_field')) {
		return;
	} else {
		return wp_nonce_field('asideshop-options');
	}
}

/**
 * asideshop_conf() - Display options page
 *
 * @global array $asideshop_patterns
 * @global string $asideshop_domain
 * @global string $wp_version
 * @global string $asideshop_show_on_array
 * @return string
*/
function asideshop_conf()
{
	global $asideshop_patterns, $asideshop_domain, $wp_version, $asideshop_show_on_array;

	asideshop_setup();
	
	if (!empty($_POST)) {
		check_admin_referer('asideshop-options');
		
		$asideshop_options = get_option('asideshop_options');
		$new_options = $_POST['asideshop_options'];

		if (!empty($new_options)) {
			$options_to_update = array('templates' => array(), 'version' => $asideshop_options['version']);			
			
			if (isset($new_options['enabled'])) {
				$options_to_update['enabled'] = $new_options['enabled'];
			}
			
			if (!empty($new_options['show_asides'])) {
				$options_to_update['show_asides'] = $new_options['show_asides'];
			}
			
			if (!empty($new_options['show_asides_on'])) {
				$options_to_update['show_asides_on'] = $new_options['show_asides_on'];
			}
			
			// Delete templates
			$c2t_to_remove = array();
			if (!empty($asideshop_options['templates'])) {
				foreach ($asideshop_options['templates'] AS $current_template_key => $current_template) {
					if (!empty($new_options['delete_templates'][$current_template_key])) {
						unset(
								// Delete templates which were not edited
								$asideshop_options['templates'][$current_template_key],
								// Delete templates which were edited
								$new_options['templates'][$current_template_key]
						);
						$c2t_to_remove[] = $current_template_key;
					}
				}
				$options_to_update['templates'] = $asideshop_options['templates'];
			}
			
			// Category to template, if template is deleted, remove from category/template relation
			if (!empty($new_options['category_to_template'])) {
				foreach ($new_options['category_to_template'] AS $c2t_key => $c2t) {
					// If default template, display post as regular 
					if (empty($c2t)) {
						unset($options_to_update['show_asides'][$c2t_key]);
					}
					
					// If template is deleted, remove from category/template relation
					if (in_array($c2t, $c2t_to_remove)) {
						$options_to_update['category_to_template'][$c2t_key] = '';
						
						// Display also post as regular
						unset($options_to_update['show_asides'][$c2t_key]);
					} else {
						$options_to_update['category_to_template'][$c2t_key] = $c2t;
					}
				}
			}
			
			// New templates would be added, edited templates would be overwritten
			if (!empty($new_options['templates'])) {
				foreach ($new_options['templates'] AS $new_template_key => $new_template) {
					if ($new_template['title'] == '') {
						// Do not add empty templates
						if ($new_template['content'] == '') {
							continue;
						}
						// Add default title if title is empty
						$new_template['title'] = 'Template-' . $new_template_key;
					}
					// Strip slashes from title and content
					array_walk($new_template, create_function('&$a', '$a = stripslashes($a);'));
					
					$options_to_update['templates'][$new_template_key] = $new_template;
				}
			}
			
			// Update 'show_asides_on' option
			foreach ($asideshop_show_on_array AS $new_show_on_key => $show_on_value) {
				if (isset($new_options['show_asides_on'][$new_show_on_key])) {
					$new_show_on_value = 1;
				} else {
					$new_show_on_value = 0;
				}
				$options_to_update['show_asides_on'][$new_show_on_key] = $new_show_on_value;
			}

			// Update all options
			update_option('asideshop_options', $options_to_update);
		}
		?>

			<div id="message" class="updated fade"><p><?php printf( __('%s Options Updated', $asideshop_domain), 'AsideShop'); ?></p></div>

		<?php
	}
	
	// Plugin options
	$asideshop_options = get_option('asideshop_options');
	
	// Is plugin enabled? 0 or 1
	$enabled = $asideshop_options['enabled'];

	// Administration panel default HTML
	$return_html = array('templates' => '', 'categories' => '');
	
	// Available tags
	$return_html['available_tags'] = '
		<div id="available-tags" style="display: none;">
			<br />
			<strong>' . __('Available tags:', $asideshop_domain) . '</strong><br />
			
				<dl style="float: left;">
					<dt style="font-weight: bold;">' . __('Post tags:', $asideshop_domain) . '</dt>
					<dd>%post_id%</dd>
					<dd>%post_title%</dd>
					<dd>%post_content%</dd>
					<dd>%post_content_filtered%</dd>
					<dd>%post_excerpt%</dd>
					<dd>%post_excerpt_filtered%</dd>
					<dd>%post_permalink%</dd>
					<dd>%post_date%</dd>
					<dd>%post_date_regular%</dd>
					<dd>%post_time%</dd>
					<dd>%post_author%</dd>
					<dd>%post_categories%</dd>
					' . ((float)$wp_version >= (float)"2.3" ? '<dd>%post_tags%</dd>' : '') . '
				</dl>
				
				<dl style="float: left;">
					<dt style="font-weight: bold;">' . __('Comment tags:', $asideshop_domain) . '</dt>
					<dd>%comments_url%</dd>
					<dd>%comments_count%</dd>
				</dl>
		</div>
	';
	
	// Initial last template array element ID, needed to determine new template element ID
	$max_template_id = 0;
	
	// Template Loop
	if (!empty($asideshop_options['templates']) && is_array($asideshop_options['templates'])) {
		// Show odd rows in different color
		$alternate = 0;
		
		// Get last template array element ID, needed to determine new template element ID
		$max_template_id = max(array_keys($asideshop_options['templates']));
		
		foreach ($asideshop_options['templates'] AS $_tmpl_key => $_tmpl) {
			// Show alternates
			$tr_alternate = '';
			if ($alternate % 2 == 0) {
				$tr_alternate = ' class="alternate"';
			}
			
			// Parse content to be display correctly
			$content = str_replace(array("\r\n", "\n", "\r", "\t"), array('\n', '\n', '\n', '\t'), $_tmpl['content']);
			
			// Content for javascript
			$js_content = preg_replace('/<\/(\w+)>/i', '<\/${1}>', $content);
			
			// Matched template elements are in $matches[0]
			preg_match_all('/%(.*?)%/i', $_tmpl['content'], $matches);

			// Content for listing
			$content = htmlentities($_tmpl['content']);
			foreach ($matches[0] AS $_match) {
				$e = ''; // error state css
				if (!array_key_exists($_match, $asideshop_patterns)
					|| ($_match == '%post_tags%' && (float)$wp_version < (float)"2.3")
					) {
					$e = ' style="color: #f00000;"';
				}
				$content = str_replace($_match, "<span {$e}><strong>{$_match}</strong></span>", $content);
			}
			
			$return_html['templates'] .= '
				<tr'.$tr_alternate.' id="row-'.$_tmpl_key.'">
					<td id="template-title-cell-'.$_tmpl_key.'" style="text-align: center;">
						<script type="text/javascript">
						//<![CDATA[
							templates[\''.$_tmpl_key.'\'] = new Object();
							templates[\''.$_tmpl_key.'\'].title = \''.attribute_escape(addslashes($_tmpl['title'])).'\';
							templates[\''.$_tmpl_key.'\'].content = \''.$js_content.'\';
						//]]>
						</script>
						
						<div id="template-title-'.$_tmpl_key.'">'.$_tmpl['title'].'</div>
						<div id="template-title-editbox-'.$_tmpl_key.'"></div>
					</td>

					<td id="template-content-cell-'.$_tmpl_key.'" style="text-align: left;">
						<div id="template-content-'.$_tmpl_key.'"><div style="font-size: smaller; white-space: pre; ">'.wordwrap($content).'</div></div>
						<div id="template-content-editbox-'.$_tmpl_key.'"></div>
					</td>
					
					<td>
						<div id="template-edit-'.$_tmpl_key.'"><button type="button" id="edit-button-'.$_tmpl_key.'" class="edit-template-button">'.__('Edit', $asideshop_domain).'</button></div>
						<div id="template-cancel-'.$_tmpl_key.'" style="display: none;"><button type="button" id="cancel-button-'.$_tmpl_key.'" class="cancel-edit-template-button">'.__('Cancel', $asideshop_domain).'</button></div>
					</td>
					<td id="template-delete-cell-'.$_tmpl_key.'"><input type="checkbox" id="delete-checkbox-'.$_tmpl_key.'" class="delete-template-checkbox" name="asideshop_options[delete_templates]['.$_tmpl_key.']" value="1" /></td>
				</tr>
			';
			$alternate++;
		}
	}
	
	// Get all categories using WordPress built-in function
	// type=post is for WordPress 2.2 version
	$categories = asideshop_get_categories();
	
	// Category Loop
	if (!empty($categories)) {
		// Show odd rows in different color
		$alternate = 0;

		// Indent subcategories with dashes
		$indent = '';

		foreach ($categories AS $_cat) {
			// Get $cat_id, $cat_name, $cat_parent
			extract($_cat);

			// Show subcategories
			if ($cat_parent == 0) {
				$indent = '';
			} else {
				$indent .= '&#8212;';
			}
			
			// Show alternates
			$tr_alternate = '';
			if ($alternate % 2 == 0) {
				$tr_alternate = ' class="alternate"';
			}
			
			// Mark asides checkbox as selected
			$asides_selected = '';
			
			if (isset($asideshop_options['show_asides'][$cat_id]) && $asideshop_options['show_asides'][$cat_id] > 0) {
				$asides_selected = "checked='checked'";
			}
			
			// Category to template options
			$category_to_template = '<option value="">' .  __('Default', $asideshop_domain) . '</option>';
			
			if (!empty($asideshop_options['templates']) && is_array($asideshop_options['templates'])) {
				foreach ($asideshop_options['templates'] AS $_tmpl_key => $_tmpl) {
					$sel = '';
					if ($asideshop_options['category_to_template'][$cat_id] == $_tmpl_key) {
						$sel = ' selected="selected"';
					}
					$category_to_template .= '<option value="'.$_tmpl_key.'" '.$sel.'>'.$_tmpl['title'].'</option>';
				}
			}
			
			$return_html['categories'] .= '
				<tr'.$tr_alternate.'>
					<td style="text-align: center;"><input type="checkbox" class="as_select" id="as-show_asides-'.$cat_id.'" name="asideshop_options[show_asides]['.$cat_id.']" value="'.$cat_id.'" '.$asides_selected.' /></td>
					<td style="text-align: left;">'.$indent.' '.$cat_name.'</td>
					<td><select class="template-select" id="template-select-'.$cat_id.'" name="asideshop_options[category_to_template]['.$cat_id.']">'.$category_to_template.'</select></td>
				</tr>
			';
			$alternate++;
		}
	} else {
		$return_html['errors'] .= '<p>' . __('No categories were found.', $asideshop_domain) . '</p>';
		$return_html['errors'] .= '<p>' . printf( __('%s operates only with post entries which are placed in categories.', $asideshop_domain), 'AsideShop') . '</p>';
	}
	
	?>
		<div class="wrap">
			<h2>AsideShop</h2>

			<script type="text/javascript">
			//<![CDATA[
				// Define templates array
				var templates = new Array;
				
				templates['new'] = new Object();
				templates['new'].title = '';
				templates['new'].content = "<div class=\"post\" id=\"post-%post_id%\">\n\t<p>\n\t\t%post_content% <a href=\"%comments_url%\">(%comments_count%)</a>\n\t</p>\n</div>";
			//]]>
			</script>
		
			<form name="asideshop_options" action="" method="post">
				<?php asideshop_nonce_field() ?>

			<?php
				// Styling for Wordpress versions prior to 2.5
				if ((float)$wp_version < (float)"2.5"):
			?>				
				
				<p class="submit"><input type="submit" name="submit" value="<?php printf( __('Update %s Options &raquo;', $asideshop_domain), 'AsideShop'); ?>" /></p>
				
				<fieldset class="options">
					<legend><?php _e('General', $asideshop_domain); ?></legend>
						<ul style="list-style-type: none;">
							<li>
								<input type="radio" id="disabled" name="asideshop_options[enabled]" value="0" <?php echo ($enabled == 0) ? ' checked="checked"' : ''; ?> />
								<label for="disabled"><?php printf( __('Disable %s.', $asideshop_domain), 'AsideShop'); ?></label> <small><?php _e('(You can still manually use custom HTML code for aside posts in your themes with <code>is_aside()</code> function.)', $asideshop_domain); ?></small>
							</li>
							<li>
								<input type="radio" id="enabled-admin" name="asideshop_options[enabled]" value="2" <?php echo ($enabled == 2) ? ' checked="checked"' : ''; ?> />
								<label for="enabled-admin"><?php printf( __('Enable %s for testing.', $asideshop_domain), 'AsideShop'); ?></label> <small><?php _e('(Only logged-in administrators can see changes on the front page.)', $asideshop_domain); ?></small>
							</li>
							<li>
								<input type="radio" id="enabled" name="asideshop_options[enabled]" value="1" <?php echo ($enabled == 1) ? ' checked="checked"' : ''; ?> />
								<label for="enabled"><strong><?php printf( __('Enable %s.', $asideshop_domain), 'AsideShop'); ?></strong></label>
							</li>
						</ul>
				</fieldset>
				
				<fieldset class="options">
					<legend><?php _e('Settings', $asideshop_domain); ?></legend>
					
					<p><?php _e('Parse templates on', $asideshop_domain); ?>:</p>
					<ul style="list-style-type: none;">
						<li>
							<input type="checkbox" id="show_aside_on_front_page" name="asideshop_options[show_asides_on][front_page]" value="1" <?php echo (!isset($asideshop_options['show_asides_on']['front_page']) || $asideshop_options['show_asides_on']['front_page'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_front_page"><?php _e('Front page', $asideshop_domain); ?></label>
						</li>
						<li>
							<input type="checkbox" id="show_aside_on_search_view" name="asideshop_options[show_asides_on][search_view]" value="1" <?php echo ($asideshop_options['show_asides_on']['search_view'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_search_view"><?php _e('Search view', $asideshop_domain); ?></label>
						</li>
						<li>
							<input type="checkbox" id="show_aside_on_archive_view" name="asideshop_options[show_asides_on][archive_view]" value="1" <?php echo ($asideshop_options['show_asides_on']['archive_view'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_archive_view"><?php _e('Archive view pages', $asideshop_domain); ?> <small><?php _e('(Includes Category, Tag, Author, Date views)', $asideshop_domain); ?></small></label>
						</li>
						<li>
							<input type="checkbox" class="view" id="show_aside_on_category_view" name="asideshop_options[show_asides_on][category_view]" value="1" <?php echo ($asideshop_options['show_asides_on']['category_view'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_category_view">&nbsp;&#9492;&nbsp; <?php _e('Category view pages', $asideshop_domain); ?></label>
						</li>
						<li>
							<input type="checkbox" class="view" id="show_aside_on_tag_view" name="asideshop_options[show_asides_on][tag_view]" value="1" <?php echo ($asideshop_options['show_asides_on']['tag_view'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_tag_view">&nbsp;&#9492;&nbsp; <?php _e('Tag view pages', $asideshop_domain); ?></label>
						</li>
						<li>
							<input type="checkbox" class="view" id="show_aside_on_date_view" name="asideshop_options[show_asides_on][date_view]" value="1" <?php echo ($asideshop_options['show_asides_on']['date_view'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_date_view">&nbsp;&#9492;&nbsp; <?php _e('Date view pages', $asideshop_domain); ?></label>
						</li>
						<li>
							<input type="checkbox" class="view" id="show_aside_on_author_view" name="asideshop_options[show_asides_on][author_view]" value="1" <?php echo ($asideshop_options['show_asides_on']['author_view'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_author_view">&nbsp;&#9492;&nbsp; <?php _e('Author view pages', $asideshop_domain); ?></label>
						</li>
					</ul>
				</fieldset>
				
				<fieldset class="options" id="templates-section">
					<legend><?php _e('Templates', $asideshop_domain); ?></legend>
					
					<p id="template-new">
						<button type="button" id="add-button-new" class="add-template-button"><?php _e('Add Template', $asideshop_domain); ?></button>
					</p>
					
					<table cellpadding="10" class="options" style="text-align: center; width: 100%;">
						<tr>
							<th style="width: 150px;"><?php _e('Name', $asideshop_domain); ?></th>
							<th style="text-align: left;"><?php _e('Template', $asideshop_domain); ?></th>
							<th style="width: 60px;"><?php _e('Edit', $asideshop_domain); ?></th>
							<th style="width: 60px;"><?php _e('Delete', $asideshop_domain); ?></th>
						</tr>
						
						<?php echo $return_html['templates']; ?>
						
						<tr id="template-title-row-new" style="background-color: #E6FFE5; display: none;">
							<td id="template-title-cell-new">
								<div id="template-title-editbox-new"></div>
							</td>

							<td id="template-content-cell-new" style="text-align: left;">
								<div id="template-content-editbox-new"></div>
							</td>
							
							<td>
								<div id="template-cancel-new">
									<button type="button" id="cancel-button-new" class="cancel-add-template-button">
										<?php _e('Cancel', $asideshop_domain); ?>
									</button>
								</div>
							</td>
							<td>&nbsp;</td>
						</tr>
					</table>
					
					<p id="delete-notice" style="background-color: #F79C9B; border: 1px solid #B95453; padding: 5px; display: none; ">
						<?php _e('After selected templates are deleted, posts in categories which were assigned to selected templates will be displayed as regular posts.', $asideshop_domain); ?>
					</p>
					
					<?php echo $return_html['available_tags']; ?>

					<p style="clear: left;">
						<?php _e('Invalid tags are marked in red (e.g. <span style="color: #f00000;"><strong>%foo%</strong></span>), they will not be parsed.', $asideshop_domain); ?>
					</p>
				</fieldset>
				
				<? if (!empty($categories)): ?>
				
				<fieldset class="options">
					<legend><?php _e('Categories', $asideshop_domain); ?></legend>
					<p><?php _e('Select the categories which contain asides posts.', $asideshop_domain); ?></p>
					
					<table cellpadding="10" class="options" style="text-align: center; width: 100%;">
						<tr>
							<th style="width: 120px;"><?php _e('Select Categories', $asideshop_domain); ?></th>
							<th style="text-align: left;"><?php _e('Category Name', $asideshop_domain); ?></th>
							<th style="width: 220px;"><?php _e('Select Template', $asideshop_domain); ?></th>
						</tr>
						<?php echo $return_html['categories']; ?>
					</table>
				</fieldset>
				
				<? endif; ?>
				
				<p class="submit"><input type="submit" name="submit" value="<?php printf( __('Update %s Options &raquo;', $asideshop_domain), 'AsideShop'); ?>" /></p>
				
			<?php
				// Styling for Wordpress 2.5 and newer
				else:
			?>
				<h3><?php _e('General', $asideshop_domain); ?></h3>
				
				<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php printf( __('Status', $asideshop_domain), 'AsideShop'); ?></th>
					<td>
						<p>
							<input type="radio" id="disabled" name="asideshop_options[enabled]" value="0" <?php echo ($enabled == 0) ? ' checked="checked"' : ''; ?> /> 
							<label for="disabled"><?php printf( __('Disable %s.', $asideshop_domain), 'AsideShop'); ?></label> <small><?php _e('(You can still manually use custom HTML code for aside posts in your themes with <code>is_aside()</code> function.)', $asideshop_domain); ?></small>
						</p>
						<p>
							<input type="radio" id="enabled-admin" name="asideshop_options[enabled]" value="2" <?php echo ($enabled == 2) ? ' checked="checked"' : ''; ?> /> 
							<label for="enabled-admin"><?php printf( __('Enable %s for testing.', $asideshop_domain), 'AsideShop'); ?></label> <small><?php _e('(Only logged-in administrators can see changes on the front page.)', $asideshop_domain); ?></small>
						</p>
						<p>
							<input type="radio" id="enabled" name="asideshop_options[enabled]" value="1" <?php echo ($enabled == 1) ? ' checked="checked"' : ''; ?> /> 
							<label for="enabled"><strong><?php printf( __('Enable %s.', $asideshop_domain), 'AsideShop'); ?></strong></label>
						</p>
					</td>
				</tr>
				</table>
				
				<h3><?php _e('Settings', $asideshop_domain); ?></h3>
				
				<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Parse templates on', $asideshop_domain); ?>:</th>
					<td>
				
						<p>
							<input type="checkbox" id="show_aside_on_front_page" name="asideshop_options[show_asides_on][front_page]" value="1" <?php echo (!isset($asideshop_options['show_asides_on']['front_page']) || $asideshop_options['show_asides_on']['front_page'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_front_page"><?php _e('Front page', $asideshop_domain); ?></label>
						</p>
						<p>
							<input type="checkbox" id="show_aside_on_search_view" name="asideshop_options[show_asides_on][search_view]" value="1" <?php echo ($asideshop_options['show_asides_on']['search_view'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_search_view"><?php _e('Search view', $asideshop_domain); ?></label>
						</p>
						<p>
							<input type="checkbox" id="show_aside_on_archive_view" name="asideshop_options[show_asides_on][archive_view]" value="1" <?php echo ($asideshop_options['show_asides_on']['archive_view'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_archive_view"><?php _e('Archive view pages', $asideshop_domain); ?> <small><?php _e('(Includes Category, Tag, Author, Date views)', $asideshop_domain); ?></small></label>
						</p>
						<p>
							<input type="checkbox" class="view" id="show_aside_on_category_view" name="asideshop_options[show_asides_on][category_view]" value="1" <?php echo ($asideshop_options['show_asides_on']['category_view'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_category_view">&nbsp;&#9492;&nbsp; <?php _e('Category view pages', $asideshop_domain); ?></label>
						</p>
						<p>
							<input type="checkbox" class="view" id="show_aside_on_tag_view" name="asideshop_options[show_asides_on][tag_view]" value="1" <?php echo ($asideshop_options['show_asides_on']['tag_view'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_tag_view">&nbsp;&#9492;&nbsp; <?php _e('Tag view pages', $asideshop_domain); ?></label>
						</p>
						<p>
							<input type="checkbox" class="view" id="show_aside_on_date_view" name="asideshop_options[show_asides_on][date_view]" value="1" <?php echo ($asideshop_options['show_asides_on']['date_view'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_date_view">&nbsp;&#9492;&nbsp; <?php _e('Date view pages', $asideshop_domain); ?></label>
						</p>
						<p>
							<input type="checkbox" class="view" id="show_aside_on_author_view" name="asideshop_options[show_asides_on][author_view]" value="1" <?php echo ($asideshop_options['show_asides_on']['author_view'] == 1) ? ' checked="checked"' : ''; ?> />
							<label for="show_aside_on_author_view">&nbsp;&#9492;&nbsp; <?php _e('Author view pages', $asideshop_domain); ?></label>
						</p>
					</td>
				</tr>
				</table>
				
				<h3><?php _e('Templates', $asideshop_domain); ?></h3>
				
				<p id="template-new">
					<button type="button" id="add-button-new" class="add-template-button"><?php _e('Add Template', $asideshop_domain); ?></button>
				</p>
				
				<table class="widefat">
					<thead>
					<tr>
						<th scope="col" style="text-align: center; width: 150px;"><?php _e('Name', $asideshop_domain); ?></th>
						<th scope="col" style="text-align: left;"><?php _e('Template', $asideshop_domain); ?></th>
						<th scope="col" style="width: 60px;"><?php _e('Edit', $asideshop_domain); ?></th>
						<th scope="col" style="width: 60px;"><?php _e('Delete', $asideshop_domain); ?></th>
					</tr>
					</thead>
					<tbody>
					
						<?php echo $return_html['templates']; ?>
							
						<tr id="template-title-row-new" style="background-color: #E6FFE5; display: none;">
							<td id="template-title-cell-new">
								<div id="template-title-editbox-new"></div>
							</td>

							<td id="template-content-cell-new" style="text-align: left;">
								<div id="template-content-editbox-new"></div>
							</td>
							
							<td>
								<div id="template-cancel-new">
									<button type="button" id="cancel-button-new" class="cancel-add-template-button">
										<?php _e('Cancel', $asideshop_domain); ?>
									</button>
								</div>
							</td>
							<td>&nbsp;</td>
						</tr>
					</tbody>
				</table>
				
				<p id="delete-notice" style="background-color: #F79C9B; border: 1px solid #B95453; padding: 5px; display: none; ">
					<?php _e('After selected templates are deleted, posts in categories which were assigned to selected templates will be displayed as regular posts.', $asideshop_domain); ?>
				</p>
				
				<?php echo $return_html['available_tags']; ?>
				
				<br class="clear" />
				
				<? if (!empty($categories)): ?>
				<h3><?php _e('Categories', $asideshop_domain); ?></h3>
				
				<p><?php _e('Select the categories which contain asides posts.', $asideshop_domain); ?></p>
				
				<table class="widefat">
					<thead>
					<tr>
						<th scope="col" style="text-align: center; width: 220px;"><?php _e('Select Categories', $asideshop_domain); ?></th>
						<th scope="col" style="text-align: left;"><?php _e('Category Name', $asideshop_domain); ?></th>
						<th scope="col" style="width: 120px;"><?php _e('Select Template', $asideshop_domain); ?></th>
					</tr>
					</thead>
					<tbody>
						<?php echo $return_html['categories']; ?>
					</tbody>
				</table>
				
				<br />
				
				<p class="submit"><input type="submit" name="submit" value="<?php _e('Save Changes', $asideshop_domain); ?>" /></p>
				
				<? endif; ?>
				
			<?php
				endif;
			?>
			</form>
			
			<script type="text/javascript">
			//<![CDATA[
				// Template ID for new template
				var new_template_id = <?php echo ++$max_template_id; ?>;
				
				// Don't close tags info box if at least one template is opened
				var opened_boxes = 0;
				
				// Show message if templates are marked for deletion
				var marked_for_deletion = 0;
				
				$j = jQuery.noConflict();

				// Unescape only frequently used chars
				var _unescape = function (text) {
					if (typeof(text) != "string") {
						text = text.toString();
					}
					
					text = text.replace(/&quot;/g, '"');
					text = text.replace(/&lt;/g, "<");
					text = text.replace(/&gt;/g, '>');
					text = text.replace(/&#039;/g, "'");
					text = text.replace(/&amp;/g, '&');
					
					return text;
				}
				
				var template_selected = function(obj)
				{
					if (obj == undefined) {
						return false;
					}
					
					var id = $j(obj).attr('id').split('-')[2];
					
					if ($j(obj).val() == '') {
						uncheck_asideshop_checkbox(id);
					}
				}
				
				var uncheck_asideshop_checkbox = function(id)
				{
					$j('#as-show_asides-' + id).attr('checked', '');
				}
				
				var uncheck_all_archive_checkbox = function()
				{
					$j('.view').attr('checked', '');
				}
				
				var ask_for_confirmation = function(id) {
					if (String($j('#template-content-editbox-' + id + ' textarea').val()) == String(templates[id].content)
					     && (String($j('#template-title-editbox-' + id + ' input').val()) == String(_unescape(templates[id].title)))
						) {
							return false;
					}
					return true;
				}
				
				var cancel_template = function(obj, action) {
					if (obj == undefined) {
						return false;
					}
					
					if (action == undefined) {
						action = 'edit';
					} else {
						if (action != 'add' && action != 'edit') {
							action = 'edit';
						}
					}

					var id = $j(obj).attr('id').split('-')[2];
					
					if (ask_for_confirmation(id)) {
						var conf = confirm("<?php _e('Are you sure you wish to cancel?\n\nAll changes you have made will be discarded, original template will be used.', $asideshop_domain); ?>");

						if (conf == false) {
							return;
						}
					}

					// Title stuff
					$j('#template-title-editbox-' + id).html('').hide();
					$j('#template-title-cell-' + id).css('verticalAlign', 'middle');
					$j('#template-title-' + id).show();
					
					// Template stuff
					$j('#template-content-' + id).show();
					$j('#template-content-editbox-' + id).html('').hide();
					
					// Actions stuff
					$j('#template-cancel-' + id).hide();
					$j('#template-edit-' + id).show();
					
					// Buttons, rows stuff
					if (action == 'add') {
						$j('#template-new').show();
						$j('#template-title-row-' + id).hide();
					}
					
					opened_boxes--;
					
					// Tags
					if (opened_boxes == 0) {
						$j('#available-tags').hide();
					}
				}
				
				var edit_template = function(obj, action) {
					if (obj == undefined) {
						return false;
					}
					
					if (action == undefined) {
						action = 'edit';
					} else {
						if (action != 'add' && action != 'edit') {
							action = 'edit';
						}
					}

					var id = $j(obj).attr('id').split('-')[2];

					if (action == 'add') {
						var _new_template_id = new_template_id;
					} else {
						var _new_template_id = id;
					}

					// Title stuff
					$j('#template-title-' + id).hide();
					$j('#template-title-cell-' + id).css('verticalAlign', 'top');
					$j('#template-title-editbox-' + id).html('<input type="text" name="asideshop_options[templates][' + _new_template_id + '][title]" value="' + templates[id].title + '" style="width: 150px;" />').show();
					
					// Template stuff
					$j('#template-content-' + id).hide();
					$j('#template-content-editbox-' + id).html('<textarea name="asideshop_options[templates][' + _new_template_id + '][content]" style="width: 100%; height: 150px; font-size: 12px;">' + templates[id].content + '<\/textarea><br />').show();
					
					// Actions stuff
					$j('#template-edit-' + id).hide();
					$j('#template-cancel-' + id).show();
					
					// Buttons, rows stuff
					if (action == 'add') {
						$j('#template-new').hide(); // Hide Add button 
						$j('#template-title-row-' + id).show();
						$j('#template-title-editbox-' + id + ' input').focus();
					} else {
						$j('#template-content-editbox-' + id + ' textarea').focus();
					}
					
					// Tags
					$j('#available-tags').show();
					
					// Etc
					opened_boxes++;
				}

				$j(document).ready(function(){
					$j('.delete-template-checkbox').click(function(){
						var id = $j(this).attr('id').split('-')[2];
						if ($j('#delete-checkbox-' + id + ':checked').length == 1) {
							$j('#delete-notice').show();
							marked_for_deletion++;
						} else {
							marked_for_deletion--;
							if (marked_for_deletion == 0) {
								$j('#delete-notice').hide();
							}
						}
					});
					
					$j('#show_aside_on_archive_view').click(function(){
						if ($j('#show_aside_on_archive_view:checked').length == 1) {
							uncheck_all_archive_checkbox();
						}
					});
					
					$j('.view').click(function(){
						if ($j(this).length == 1) {
							$j('#show_aside_on_archive_view').attr('checked', '');
						}
					});
					
					$j('.add-template-button').click(function(){
						edit_template(this, 'add');
					});
					
					$j('.edit-template-button').click(function(){
						edit_template(this, 'edit');
					});
					
					$j('.cancel-add-template-button').click(function(){
						cancel_template(this, 'add');
					});
					
					$j('.cancel-edit-template-button').click(function(){
						cancel_template(this, 'edit');
					});
					
					$j('.template-select').change(function(){
						template_selected(this);
					});

					$j('#set-default-template').click(function(){
						set_default_template();
					});
				});
			//]]>
			</script>
		</div><!-- end wrap div //-->
	<?php
}

/**
 * is_aside() - Check whether post is in category which is marked as aside category
 *
 * @param bool $get_aside_category_array Return an array of aside categories intersecting with post categories or bool
 * @return bool
*/
function is_aside($get_aside_category_array = FALSE)
{
	$as_options = get_option('asideshop_options');
	
	$result = array();
	
	if (!empty($as_options['show_asides']) && is_array($as_options['show_asides'])) {
		foreach ($as_options['show_asides'] AS $_show_asides) {
			if (in_category($_show_asides) && !empty($as_options['category_to_template'][$_show_asides])) {
				$result[] = $_show_asides;
			}
		}
	}
	
	if ($get_aside_category_array === FALSE) {
		if (count($result) > 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	} else {
		return $result;
	}
}

/**
 * asideshop_parse_template() - Parse aside post using template
 *
 * @param array $aside_cat_array Array of aside categories intersecting with post categories
 * @global object $post
 * @global array $asideshop_patterns
 * @return string
*/
function asideshop_parse_template($aside_cat_array = array())
{
	global $post, $asideshop_patterns;

	if (empty($aside_cat_array) && !is_array($aside_cat_array)) {
		return '';
	}

	$as_options = get_option('asideshop_options');

	// Get first matching aside category
	$cat_id = $aside_cat_array[0];

	// Get template ID
	$template_id = $as_options['category_to_template'][$cat_id];

	// Get template contents
	$template = $as_options['templates'][$template_id]['content'];

	// Matched elements are in $matches[0]
	preg_match_all('/%(.*?)%/i', $template, $matches);

	// Loop through matched tags, replace them with content
	foreach ($matches[0] AS $_match) {
		if (array_key_exists($_match, $asideshop_patterns)) {
			eval('$template = str_replace($_match, '.$asideshop_patterns[$_match].', $template);');
		}
	}
	return $template;
	

}

/**
 * asideshop_display_scripts() - Load javascripts
 *
 * @return void
*/
function asideshop_display_scripts()
{
	// We use JQuery
	wp_print_scripts(array('jquery'));
}

/**
 * asideshop_the_post() - Check whether post needs to be parsed
 *
 * @global int $asideshop_ob_started
 * @return void
*/
function asideshop_the_post()
{
	if (asideshop_is_enabled() && !is_admin() && !is_feed() && asideshop_is_displayable() && !is_single()) {
		global $asideshop_ob_started;

		if ($aside_cat_array = is_aside(TRUE)) {
			if ($asideshop_ob_started > 0) {
				ob_end_clean();
				$asideshop_ob_started--;
			}

			echo asideshop_parse_template($aside_cat_array);

			ob_start();
			$asideshop_ob_started++;
		} else {
			if ($asideshop_ob_started > 0) {
				ob_end_clean();
				$asideshop_ob_started--;
			}
		}
	}
}

/**
 * WP_Query_AsideShop
 *
 * Extend WP_Query with custom the_post() function
 *
 * @author Raimonds Kalnins
 * @deprecated deprecated since version 1.0.10
 **/
class WP_Query_AsideShop extends WP_Query {

	/**
	 * WP_Query_AsideShop()
	 * 
	 * Initialize WP_Query_AsideShop
	 *
	 * @param object $obj WP_Query object
	 * @return object WP_Query_AsideShop object
	 */
	function WP_Query_AsideShop($obj = '')
	{
		if ($obj != '' && is_object($obj)) {
			reset($obj);
			foreach (get_object_vars($obj) as $key => $value) {
				$this->$key = $value;	
			}
		}
		return $this;
	}
	
	/**
	 * the_post()
	 * 
	 * Custom the_post() function
	 *
	 * @return void
	 */
	function the_post()
	{
		parent::the_post();
		asideshop_the_post();
	}
}

/**
 * asideshop_loop_start() - Initialize upon the beginning of The Loop, extend WP_Query
 *
 * @global int $asideshop_ob_started
 * @return void
*/
function asideshop_loop_start()
{
  if (asideshop_is_enabled() && !is_admin() && !is_feed() && asideshop_is_displayable() && !is_single()) {
		global $asideshop_ob_started;

		ob_start();

		$asideshop_ob_started++;
	}
}

/**
 * asideshop_loop_start_27() - Initialize upon the beginning of The Loop, extend WP_Query
 *
 * @global object $wp_query
 * @global int $asideshop_ob_started
 * @return void
 * @deprecated deprecated since version 1.0.10
*/
function asideshop_loop_start_27()
{
	if (asideshop_is_enabled() && !is_admin() && !is_feed() && asideshop_is_displayable() && !is_single()) {
		global $wp_query, $asideshop_ob_started;
		
		ob_start();
		
		$asideshop_ob_started++;
		
		$wp_query = new WP_Query_AsideShop($wp_query);

		asideshop_the_post();
	}
}

/**
 * asideshop_loop_end() - Initialize upon the end of The Loop
 *
 * @global int $asideshop_ob_started
 * @global bool $asideshop_loop_ended
 * @return void
 */

function asideshop_loop_end()
{
	if (asideshop_is_enabled() && !is_admin() && !is_feed() && asideshop_is_displayable()) {
		global $asideshop_ob_started, $asideshop_loop_ended;
		if ($asideshop_ob_started > 0) {
			ob_end_clean();
			$asideshop_ob_started--;
		}
		$asideshop_loop_ended = TRUE;
	}
}

// If 2.8 and later
if ((float)$wp_version >= (float)"2.8") {
  // Hook up the_post processing
  add_action('the_post', 'asideshop_the_post');
}

// If 2.7 and earlier
if ((float)$wp_version <= (float)"2.7") {
  // Initiate upon The Loop start
  add_action('loop_start', 'asideshop_loop_start_27');
} else {
  // Initiate upon The Loop start
  add_action('loop_start', 'asideshop_loop_start');
}

// Initiate upon The Loop end
add_action('loop_end', 'asideshop_loop_end');

// We want to use jquery
add_action('admin_print_scripts', 'asideshop_display_scripts');

// Display options page
add_action('admin_menu', 'asideshop_create_options_page');

// Install script
register_activation_hook(__FILE__, 'asideshop_install');

// Uninstall script
register_deactivation_hook(__FILE__, 'asideshop_uninstall');

?>