<?php declare(strict_types=1);
$finder = PhpCsFixer\Finder::create()
	->in(__DIR__ . DIRECTORY_SEPARATOR . 'bin')
	->in(__DIR__ . DIRECTORY_SEPARATOR . 'src')
	->in(__DIR__ . DIRECTORY_SEPARATOR . 'www')
	->in(__DIR__ . DIRECTORY_SEPARATOR . 'tests')
	->in(__DIR__ . DIRECTORY_SEPARATOR . '.phan')
	->notName('_autoload.php')
;
$config = (new PhpCsFixer\Config())
	->setRiskyAllowed(true)
	->setIndent("\t")
	->setRules([
		'header_comment' => [
			'header' => <<< 'EOD'
This file is part of letswifi; a system for easy eduroam device enrollment

Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
SPDX-License-Identifier: BSD-3-Clause
EOD
		],
		'@PSR2' => true,
		'@Symfony' => true,
		'@Symfony:risky' => true,
		'@PHP73Migration' => true,
		'@PHP71Migration:risky' => true,
		'align_multiline_comment' => [
			'comment_type' => 'all_multiline',
		],
		'array_indentation' => true,
		'array_push' => true,
		'array_syntax' => [
			'syntax' => 'short',
		],
		'backtick_to_shell_exec' => true,
		'blank_line_after_namespace' => true,
		'blank_line_after_opening_tag' => false, /* declare strict types definition goes here */
		'braces' => [ /* Does screw up the declare( strict_types=1 ); definition :'( */
			'allow_single_line_closure' => true,
			'position_after_anonymous_constructs' => 'same',
			'position_after_control_structures' => 'same',
			'position_after_functions_and_oop_constructs' => 'next',
		],
		'cast_spaces' => [
			'space' => 'none',
		],
		'class_attributes_separation' => true,
		'class_definition' => [ /* default */
			'multi_line_extends_each_single_line' => false,
			'single_item_single_line' => false,
			'single_line' => false,
		],
		'class_keyword_remove' => false,
		'combine_consecutive_issets' => true,
		'combine_consecutive_unsets' => true,
		'combine_nested_dirname' => true,
		'comment_to_phpdoc' => true,
		'concat_space' => [
			'spacing' => 'one',
		],
		'constant_case' => ['case' => 'lower'], // constants such as true, false, null
		'date_time_immutable' => true,
		'declare_strict_types' => true,
		'dir_constant' => true,
		'elseif' => true,
		'encoding' => true,
		'ereg_to_preg' => true,
		'error_suppression' => [
			'mute_deprecation_error' => false,
			'noise_remaining_usages' => true,
			'noise_remaining_usages_exclude' => [
				'openssl_x509_checkpurpose',
				'openssl_x509_fingerprint',
				'openssl_x509_read',
			]
		],
		'escape_implicit_backslashes' => [
			'double_quoted' => true,
			'heredoc_syntax' => true,
			'single_quoted' => false,
		],
		'explicit_indirect_variable' => true,
		'explicit_string_variable' => true,
		'fopen_flag_order' => true,
		'fopen_flags' => ['b_mode' => false],
		'full_opening_tag' => true,
		'fully_qualified_strict_types' => true,
		'function_declaration' => false, /* Would remove spaces in function definitions */
		'function_to_constant' => [
			'functions' => [ /* default */
				'get_called_class',
				'get_class',
				'get_class_this',
				'php_sapi_name',
				'phpversion',
				'pi',
			],
		],
		'function_typehint_space' => true,
		'global_namespace_import' => [
			'import_classes' => true,
			'import_constants' => false,
			'import_functions' => false,
		],
		'implode_call' => true,
		'include' => true,
		'indentation_type' => true,
		'is_null' => true,
		'line_ending' => true,
		'linebreak_after_opening_tag' => false, /* declare strict types definition goes here */
		'logical_operators' => true,
		'lowercase_cast' => true,
		'lowercase_keywords' => true,
		'lowercase_static_reference' => true,
		'magic_constant_casing' => true,
		'method_argument_space' => [
			'keep_multiple_spaces_after_comma' => false,
			'on_multiline' => 'ignore', /* ensure_fully_multiline indents weirdly */
			'after_heredoc' => true,
		],
		'method_chaining_indentation' => true,
		'modernize_types_casting' => true,
		'multiline_comment_opening_closing' => true,
		'multiline_whitespace_before_semicolons' => [
			'strategy' => 'new_line_for_chained_calls',
		],
		'native_constant_invocation' => [
			'exclude' => ['null', 'false', 'true'], /* default */
			'fix_built_in' => true,
			'include' => [],
		],
		'native_function_casing' => true,
		'native_function_invocation' => [
			'exclude' => [], /* default */
			'include' => ['@all'], /* default */
			'scope' => 'all', /* default */
		],
		'new_with_braces' => true,
		'no_alias_functions' => true,
		'no_alternative_syntax' => true,
		'no_blank_lines_after_phpdoc' => false, /* yes for functions, classes. no for file. Can't choose, so false for now */
		'no_break_comment' => [
			'comment_text' => '@todo document implicit fall-through',
		],
		'no_closing_tag' => true,
		'no_empty_comment' => true,
		'no_empty_phpdoc' => true,
		'no_empty_statement' => true,
		'no_extra_blank_lines' => [
			'tokens' => [
				'extra',
			],
		],
		'no_homoglyph_names' => true,
		'no_leading_namespace_whitespace' => true,
		'no_mixed_echo_print' => [
			'use' => 'echo',
		],
		'no_php4_constructor' => true,
		'no_short_bool_cast' => true,
		'no_singleline_whitespace_before_semicolons' => true,
		'no_spaces_after_function_name' => true,
		'no_spaces_inside_parenthesis' => false, /* we can't force these using php_cs, but at least don't remove them */
		'no_superfluous_elseif' => true,
		'no_superfluous_phpdoc_tags' => false,
		'no_trailing_whitespace' => true,
		'no_trailing_whitespace_in_comment' => true,
		'no_trailing_whitespace_in_string' => true,
		'no_unneeded_control_parentheses' => [
			'statements' => ['break', 'clone', 'continue', 'echo_print', 'return', 'switch_case', 'yield'], /* default */
		],
		'no_unneeded_final_method' => true,
		'no_unreachable_default_argument_value' => true,
		'no_unused_imports' => true,
		'no_useless_else' => true,
		'no_useless_return' => true,
		'no_useless_sprintf' => true,
		'no_whitespace_before_comma_in_array' => [
			// There's also an implicit rule here, that fixes stuff like array( 1 , 2 ) => array( 1, 2 )
			'after_heredoc' => true,
		],
		'no_whitespace_in_blank_line' => true,
		'non_printable_character' => [
			'use_escape_sequences_in_strings' => true,
		],
		'normalize_index_brace' => true,
		'object_operator_without_whitespace' => true,
		'ordered_class_elements' => [
			'order' => [
				'use_trait',
				'constant_public',
				'constant_protected',
				'constant_private',
				'property_public',
				'property_protected',
				'property_private',
				'construct',
				'destruct',
				'magic',
				'phpunit',
				'method_public',
				'method_protected',
				'method_private',
			],
		],
		'ordered_imports' => [
			'sort_algorithm' => 'alpha',
			'case_sensitive' => true,
		],
		'ordered_traits' => true,
		'phpdoc_add_missing_param_annotation' => true,
		'phpdoc_align' => [
			'align' => 'vertical',
			'tags' => ['param', 'return', 'throws', 'type', 'var'],
		],
		'phpdoc_annotation_without_dot' => true,
		'phpdoc_indent' => true,
		'phpdoc_inline_tag_normalizer' => true,
		'phpdoc_no_access' => true,
		'phpdoc_no_empty_return' => false,
		'phpdoc_no_package' => true,
		'phpdoc_no_useless_inheritdoc' => true,
		'phpdoc_order' => true,
		'phpdoc_separation' => true,
		'phpdoc_single_line_var_spacing' => true,
		'phpdoc_summary' => false,
		'phpdoc_tag_type' => true,
		'phpdoc_to_comment' => false, /* converts Psalm suppress to comment, so turn it off */
		'phpdoc_trim' => true,
		'phpdoc_trim_consecutive_blank_line_separation' => true,
		'phpdoc_types' => true,
		'phpdoc_types_order' => [
			'null_adjustment' => 'always_first', /* default */
			'sort_algorithm' => 'alpha',
		],
		'phpdoc_var_without_name' => true,
		'php_unit_construct' => true,
		'php_unit_mock_short_will_return' => true,
		'php_unit_set_up_tear_down_visibility' => true,
		'php_unit_test_annotation' => true,
		'pow_to_exponentiation' => true,
		'psr_autoloading' => false, /* PSR4 is in conflict with SPL (Standard Php Library), we use SPL, not PSR4 */
		'return_assignment' => true,
		'return_type_declaration' => true,
		'self_accessor' => true,
		'semicolon_after_instruction' => true,
		'set_type_to_cast' => true,
		'short_scalar_cast' => true,
		'simplified_null_return' => true,
		'single_blank_line_at_eof' => true,
		'single_class_element_per_statement' => true,
		'single_import_per_statement' => true,
		'single_line_after_imports' => true,
		'space_after_semicolon' => [
			'remove_in_empty_for_expressions' => true,
		],
		'spaces_inside_parentheses' => [
			'space' => 'single',
		],
		'standardize_increment' => true,
		'standardize_not_equals' => true,
		'static_lambda' => true,
		'strict_comparison' => true,
		'strict_param' => true,
		'string_line_ending' => true,
		'switch_case_semicolon_to_colon' => true,
		'ternary_operator_spaces' => true,
		'ternary_to_elvis_operator' => true,
		'ternary_to_null_coalescing' => true,
		'trailing_comma_in_multiline' => [
			'after_heredoc' => true,
			'elements' => ['arrays', 'arguments'],
		],
		'trim_array_spaces' => true,
		'unary_operator_spaces' => true,
		'visibility_required' => true,
		'yoda_style' => ['equal' => true, 'identical' => true, 'less_and_greater' => true, 'always_move_variable' => true],
	])
	->setFinder($finder)
;
return $config;
