<?php

declare(strict_types=1);

/*
 * This file is part of TyPrint.
 *
 * (c) TyPrint Core Team <https://typrint.org>
 *
 * This source file is subject to the GNU General Public License version 3
 * that is with this source code in the file LICENSE.
 */

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect()) // @TODO 4.0 no need to call this manually
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP83Migration' => true,

        'header_comment' => [
            'header' => <<<'EOF'
                This file is part of TyPrint.

                (c) TyPrint Core Team <https://typrint.org>

                This source file is subject to the GNU General Public License version 3
                that is with this source code in the file LICENSE.
                EOF,
        ],
        'array_syntax' => ['syntax' => 'short'],
        'method_chaining_indentation' => true,
        'modernize_strpos' => true,
        'modernize_types_casting' => true,
        'single_line_after_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_blank_lines_after_class_opening' => true,
        'visibility_required' => true,
        'declare_strict_types' => true,
        'explicit_string_variable' => true,
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space' => true,
        'multiline_comment_opening_closing' => true,
        'single_blank_line_at_eof' => true,
        'no_empty_phpdoc' => true,
        'phpdoc_order' => [
            'order' => ['param',  'return', 'throws', 'since', 'deprecated', 'see'],
        ],
        'phpdoc_param_order' => true,
        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_add_missing_param_annotation' => [
            'only_untyped' => false,
        ],

        'native_constant_invocation' => false,
        'native_function_invocation' => false,
    ])
    ->setFinder(
        (new Finder())
            ->ignoreDotFiles(false)
            ->ignoreVCSIgnored(true)
            ->notName('tp-config-sample.php')
            ->notName('*.phar')
            ->notContains('// @phpcsf-ignore')
    );
