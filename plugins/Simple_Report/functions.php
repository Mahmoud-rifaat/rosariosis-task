<?php


add_action('Warehouse.php|header_head', 'MyPluginHeadLoadJSCSS');

/**
 * Load JS and CSS in HTML head
 * Load mystylesheet.css & myjavascriptfile.js files.
 *
 * @uses Warehouse.php|header_head action hook
 */
function MyPluginHeadLoadJSCSS()
{
	echo '<script src="plugins/Simple_Report/js/myjavascriptfile.js"></script>';
}
