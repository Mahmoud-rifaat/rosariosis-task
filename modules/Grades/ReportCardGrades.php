<?php
//echo '<pre>'; var_dump($_REQUEST); echo '</pre>';

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] )
		&& AllowEdit()
		&& $_REQUEST['tab_id'] )
	{
		foreach ( (array) $_REQUEST['values'] as $id => $columns )
		{
			// FJ fix SQL bug invalid numeric data.

			if (  ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
				&& ( empty( $columns['BREAK_OFF'] ) || is_numeric( $columns['BREAK_OFF'] ) )
				&& ( empty( $columns['GPA_VALUE'] ) || is_numeric( $columns['GPA_VALUE'] ) )
				&& ( empty( $columns['UNWEIGHTED_GP'] ) || is_numeric( $columns['UNWEIGHTED_GP'] ) )
				&& ( empty( $columns['GP_SCALE'] ) || is_numeric( $columns['GP_SCALE'] ) )
				&& ( empty( $columns['GP_PASSING_VALUE'] ) || is_numeric( $columns['GP_PASSING_VALUE'] ) )
				&& ( empty( $columns['HR_GPA_VALUE'] ) || is_numeric( $columns['HR_GPA_VALUE'] ) )
				&& ( empty( $columns['HHR_GPA_VALUE'] ) || is_numeric( $columns['HHR_GPA_VALUE'] ) )
				&& ( empty( $columns['HRS_GPA_VALUE'] ) || is_numeric( $columns['HRS_GPA_VALUE'] ) ) )
			{
				$table = ( $_REQUEST['tab_id'] !== 'new' ? 'report_card_grades' : 'report_card_grade_scales' );

				if ( $id !== 'new' )
				{
					DBUpdate(
						$table,
						$columns,
						[ 'ID' => (int) $id ]
					);
				}

				// New: check for Title, Scale Value & Minimum Passing Grade.
				elseif ( ( $columns['TITLE'] || $columns['TITLE'] == '0' )
					&& ( $_REQUEST['tab_id'] !== 'new' ||
						( $columns['GP_SCALE'] && is_numeric( $columns['GP_PASSING_VALUE'] ) ) ) )
				{
					$insert_columns = [ 'SCHOOL_ID' => UserSchool(), 'SYEAR' => UserSyear() ];

					if ( $_REQUEST['tab_id'] !== 'new' )
					{
						$insert_columns['GRADE_SCALE_ID'] = (int) $_REQUEST['tab_id'];
					}

					DBInsert(
						$table,
						$insert_columns + $columns
					);
				}
			}
			else
			{
				$error[] = _( 'Please enter valid Numeric data.' );
			}
		}
	}

	// Unset modfunc, values & redirect URL.
	RedirectURL( [ 'modfunc', 'values' ] );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		if ( DeletePrompt( _( 'Report Card Grade' ) ) )
		{
			DBQuery( "DELETE FROM report_card_grades
				WHERE ID='" . (int) $_REQUEST['id'] . "'" );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}
	elseif ( DeletePrompt( _( 'Report Card Grading Scale' ) ) )
	{
		$delete_sql = "DELETE FROM report_card_grades
			WHERE GRADE_SCALE_ID='" . (int) $_REQUEST['id'] . "';";

		$delete_sql .= "DELETE FROM report_card_grade_scales
			WHERE ID='" . (int) $_REQUEST['id'] . "';";

		DBQuery( $delete_sql );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

//FJ fix SQL bug invalid numeric data
echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	if ( User( 'PROFILE' ) === 'admin' )
	{
		$grade_scales_RET = DBGet( "SELECT ID,TITLE
			FROM report_card_grade_scales
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );

		if ( ! isset( $_REQUEST['tab_id'] )
			|| $_REQUEST['tab_id'] == ''
			|| $_REQUEST['tab_id'] !== 'new'
			&& empty( $grade_scales_RET[$_REQUEST['tab_id']] ) )
		{
			if ( ! empty( $grade_scales_RET ) )
			{
				$_REQUEST['tab_id'] = key( $grade_scales_RET ) . '';
			}
			else
			{
				$_REQUEST['tab_id'] = 'new';
			}
		}
	}
	else
	{
		$course_period_RET = DBGet( "SELECT GRADE_SCALE_ID,DOES_BREAKOFF,TEACHER_ID
			FROM course_periods
			WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );

		if ( empty( $course_period_RET[1]['GRADE_SCALE_ID'] ) )
		{
			ErrorMessage( [ _( 'This course is not graded.' ) ], 'fatal' );
		}

		$grade_scales_RET = DBGet( "SELECT ID,TITLE
			FROM report_card_grade_scales
			WHERE ID='" . (int) $course_period_RET[1]['GRADE_SCALE_ID'] . "'", [], [ 'ID' ] );

		if ( $course_period_RET[1]['DOES_BREAKOFF'] == 'Y' )
		{
			$teacher_id = $course_period_RET[1]['TEACHER_ID'];

			$gradebook_config = ProgramUserConfig( 'Gradebook', $teacher_id );
		}

		$_REQUEST['tab_id'] = key( $grade_scales_RET ) . '';
	}

	$tabs = [];

	foreach ( (array) $grade_scales_RET as $id => $grade_scale )
	{
		$tabs[] = [ 'title' => $grade_scale[1]['TITLE'], 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=' . $id ];
	}

	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		$sql = "SELECT ID,TITLE,SORT_ORDER,GPA_VALUE,BREAK_OFF,COMMENT,GRADE_SCALE_ID,UNWEIGHTED_GP
			FROM report_card_grades
			WHERE GRADE_SCALE_ID='" . (int) $_REQUEST['tab_id'] . "'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY BREAK_OFF IS NULL,BREAK_OFF DESC,SORT_ORDER IS NULL,SORT_ORDER";

		$sql_count = "SELECT COUNT(1)
			FROM report_card_grades
			WHERE GRADE_SCALE_ID='" . (int) $_REQUEST['tab_id'] . "'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'";

		$sql .= SQLLimitForList( $sql_count );

		$functions = [
			'TITLE' => '_makeTextInput',
			'BREAK_OFF' => '_makeGradesInput',
			'SORT_ORDER' => '_makeTextInput',
			'GPA_VALUE' => '_makeGradesInput',
			'UNWEIGHTED_GP' => '_makeGradesInput',
			'COMMENT' => '_makeTextInput',
		];

		$LO_columns = [
			'TITLE' => _( 'Title' ),
			'BREAK_OFF' => _( 'Breakoff' ),
			'GPA_VALUE' => _( 'GPA Value' ),
			'UNWEIGHTED_GP' => _( 'Unweighted GP Value' ),
			'SORT_ORDER' => _( 'Order' ),
			'COMMENT' => _( 'Comment' ),
		];

		$link['add']['html'] = [
			'TITLE' => _makeTextInput( '', 'TITLE' ),
			'BREAK_OFF' => _makeGradesInput( '', 'BREAK_OFF' ),
			'GPA_VALUE' => _makeGradesInput( '', 'GPA_VALUE' ),
			'UNWEIGHTED_GP' => _makeGradesInput( '', 'UNWEIGHTED_GP' ),
			'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
			'COMMENT' => _makeTextInput( '', 'COMMENT' ),
		];

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&tab_id=' . $_REQUEST['tab_id'];
		$link['remove']['variables'] = [ 'id' => 'ID' ];

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$tabs[] = [
				'title' => button( 'add', '', '', 'smaller' ),
				'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new',
			];
		}
	}
	else
	{
		$sql = "SELECT ID,TITLE,GP_SCALE,GP_PASSING_VALUE,COMMENT,
			HHR_GPA_VALUE,HR_GPA_VALUE,HRS_GPA_VALUE,SORT_ORDER,
			'' AS HONOR_ROLL_GPA_MIN
			FROM report_card_grade_scales
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER,ID";

		$functions = [
			'TITLE' => '_makeTextInput',
			'GP_SCALE' => '_makeGradesInput',
			'GP_PASSING_VALUE' => '_makeGradesInput',
			'COMMENT' => '_makeTextInput',
			'HONOR_ROLL_GPA_MIN' => '_makeHonorRollGPAMinInputs',
			'SORT_ORDER' => '_makeTextInput',
		];

		$LO_columns = [
			'TITLE' => _( 'Grade Scale' ),
			'GP_SCALE' => _( 'Scale Value' ),
			'GP_PASSING_VALUE' => _( 'Minimum Passing Grade' ),
			'COMMENT' => _( 'Comment' ),
			'HONOR_ROLL_GPA_MIN' => _( 'Honor Roll GPA Min' ),
			'SORT_ORDER' => _( 'Sort Order' ),
		];

		$link['add']['html'] = [
			'TITLE' => _makeTextInput( '', 'TITLE' ),
			'GP_SCALE' => _makeGradesInput( '', 'GP_SCALE' ),
			'GP_PASSING_VALUE' => _makeGradesInput( '', 'GP_PASSING_VALUE' ),
			'COMMENT' => _makeTextInput( '', 'COMMENT' ),
			'HONOR_ROLL_GPA_MIN' => _makeHonorRollGPAMinInputs( '', 'HONOR_ROLL_GPA_MIN' ),
			'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
		];

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&tab_id=new';
		$link['remove']['variables'] = [ 'id' => 'ID' ];

		$tabs[] = [
			'title' => button( 'add', '', '', 'smaller' ),
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new',
		];
	}

	$LO_ret = DBGet( $sql, $functions );

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update&tab_id=' . $_REQUEST['tab_id']  ) . '" method="POST">';
	DrawHeader( '', SubmitButton() );
	echo '<br />';

	$LO_options = [ 'search' => false,
		'header' => WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=' . $_REQUEST['tab_id'] ) ];

	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		ListOutput(
			$LO_ret,
			$LO_columns,
			'Grade',
			'Grades',
			$link,
			[],
			// @since 10.9 Add pagination for list > 1000 results
			$LO_options + [ 'pagination' => true ]
		);
	}
	else
	{
		ListOutput( $LO_ret, $LO_columns, 'Grade Scale', 'Grade Scales', $link, [], $LO_options );
	}

	echo '<br /><div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}

/**
 * @param $value
 * @param $name
 */
function _makeGradesInput( $value, $name )
{
	global $THIS_RET,
		$teacher_id,
		$gradebook_config;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	if ( $name === 'COMMENT' )
	{
		$extra = 'size=15 maxlength=100';
	}
	elseif ( $name === 'BREAK_OFF'
		&& $teacher_id
		&& ! empty( $THIS_RET['ID'] )
		&& isset( $gradebook_config[UserCoursePeriod() . '-' . $THIS_RET['ID']] )
		&& $gradebook_config[UserCoursePeriod() . '-' . $THIS_RET['ID']] != '' )
	{
		// Breakoff configured by Teacher.
		return '<span style="color:blue">' .
			$gradebook_config[UserCoursePeriod() . '-' . $THIS_RET['ID']] . '%</span>';
	}
	else
	{
		$extra = ' type="number" min="0" max="99999" step="0.01"';

		if ( $value )
		{
			$value = number_format( (float) $value, 2, '.', '' );
		}

		if ( $id !== 'new'
			&& ( $name === 'GP_SCALE'
				|| $name === 'GP_PASSING_VALUE' ) )
		{
			$extra .= ' required';
		}
	}

	if ( $name === 'BREAK_OFF'
		&& $value !== '' )
	{
		// Append "%" to displayed Breakoff value.
		$value = [ $value, $value . '%' ];
	}

	return TextInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
		$extra
	);
}

/**
 * Make Honor Roll GPA Min Inputs
 * - High Honor Roll GPA Min
 * - Honor Roll GPA Min
 * - Honor Roll by Subject GPA Min
 *
 * Local function
 * DBGet() callback
 *
 * @since 11.7
 *
 * @param  string $value Value.
 * @param  string $name  Column name. Defaults to 'HONOR_ROLL_GPA_MIN'.
 *
 * @return string        Honor Roll GPA Min Inputs
 */
function _makeHonorRollGPAMinInputs( $value, $name = 'HONOR_ROLL_GPA_MIN' )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	$columns = [
		'HHR_GPA_VALUE' => _( 'High Honor Roll GPA Min' ),
		'HR_GPA_VALUE' => _( 'Honor Roll GPA Min' ),
		'HRS_GPA_VALUE' => _( 'Honor Roll by Subject GPA Min' ),
	];

	$extra = ' type="number" min="0" max="99999" step="0.01"';

	$return = '';

	foreach ( $columns as $name => $title )
	{
		$value = '';

		if ( $id !== 'new' )
		{
			$value = $THIS_RET[ $name ];
		}

		if ( $value )
		{
			$value = number_format( (float) $value, 2, '.', '' );
		}

		$return .= TextInput(
			$value,
			'values[' . $id . '][' . $name . ']',
			$title,
			$extra
		);

		if ( $id === 'new'
			|| is_null( $value )
			|| trim( $value ) == '' )
		{
			$return .= '<br>';
		}
	}

	// Add HTML inside colorBox on mobile devices using the responsive table 2 colorBox class.
	return '<div id="divHonorRoll' . $id . '" class="rt2colorBox">' . $return . '</div>';
}

/**
 * @param $value
 * @param $name
 */
function _makeTextInput( $value, $name )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	if ( $name === 'TITLE' )
	{
		if ( $_REQUEST['tab_id'] === 'new' )
		{
			// Scale Title.
			$extra = 'maxlength=100';
		}
		else
		{
			$extra = 'size=4 maxlength=5';
		}

		if ( $id !== 'new' )
		{
			$extra .= ' required';
		}
	}
	elseif ( $name === 'COMMENT' )
	{
		$extra = 'size=15 maxlength=1000';
	}
	elseif ( $name === 'SORT_ORDER' )
	{
		$extra = ' type="number" min="-9999" max="9999"';
	}
	else
	{
		$extra = 'size=4 maxlength=5';
	}

	return TextInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
		$extra
	);
}
