<?php


// add_action('Warehouse.php|header_head', 'MyPluginHeadLoadJSCSS');


function MyPluginHeadLoadJSCSS()
{
	echo '<script src="plugins/Simple_Report/js/myjavascriptfile.js"></script>';
}


// add_action('Students/Student.php|header', 'SimpleReportMenuEntry');
add_action('Warehouse.php|header_head', 'SimpleReportMenuEntry');


function SimpleReportMenuEntry()
{
	include __DIR__ . '/../../modules/Students/Menu.php';

	// global $menu;

	$menu['Students']['admin'] = array_merge(
		$menu['Students']['admin'],
		[
			5 => 'More',
			'Students/Report.php' => _('Simple Report'),
		]
	);

	// echo '<pre>';
	// var_dump($menu['Students']['admin']);
	// echo '</pre>';
	// exit;
}
