<?php

/**
 * Unit tests for Updatronix_Export_Body_Builder pure helpers (no WordPress runtime).
 *
 * Locks the field-normalization and column-allowlist invariants that the pre-1.1
 * hardening cycle must preserve byte-for-byte:
 *
 * - `normalize_field()` strips control characters and flattens newlines (security
 *   note S2 — prevents log-injection / line-spoofing in the plain-text report).
 * - `default_export_columns()` / `normalize_export_columns()` keep the column
 *   allowlist and boolean coercion stable.
 * - `truncation_footer()` renders the documented counts.
 *
 * @package updatronix
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @covers \Updatronix_Export_Body_Builder
 */
final class ExportBodyBuilderTest extends TestCase {
    public function test_normalize_field_strips_control_characters(): void {
        self::assertSame(
            'abc',
            Updatronix_Export_Body_Builder::normalize_field("a\x00b\x01c\x7f")
        );
    }

    public function test_normalize_field_flattens_crlf_and_cr_to_single_space(): void {
        self::assertSame(
            'line1 line2',
            Updatronix_Export_Body_Builder::normalize_field("line1\r\nline2")
        );
        self::assertSame(
            'a b',
            Updatronix_Export_Body_Builder::normalize_field("a\rb")
        );
    }

    public function test_normalize_field_trims_outer_whitespace(): void {
        self::assertSame('hi', Updatronix_Export_Body_Builder::normalize_field('  hi  '));
    }

    public function test_normalize_field_preserves_internal_tab(): void {
        self::assertSame("a\tb", Updatronix_Export_Body_Builder::normalize_field("a\tb"));
    }

    public function test_default_export_columns_are_all_visible(): void {
        self::assertSame(
            [
                'table_heading' => true,
                'action_type' => true,
                'run_context' => true,
                'user' => true,
                'status' => true,
                'category' => true,
            ],
            Updatronix_Export_Body_Builder::default_export_columns()
        );
    }

    public function test_normalize_export_columns_defaults_when_empty(): void {
        self::assertSame(
            Updatronix_Export_Body_Builder::default_export_columns(),
            Updatronix_Export_Body_Builder::normalize_export_columns([])
        );
    }

    public function test_normalize_export_columns_overrides_known_keys(): void {
        $result = Updatronix_Export_Body_Builder::normalize_export_columns(['status' => false]);

        self::assertFalse($result['status']);
        self::assertTrue($result['user']);
    }

    public function test_normalize_export_columns_ignores_unknown_keys(): void {
        $result = Updatronix_Export_Body_Builder::normalize_export_columns([
            'bogus' => false,
            'user' => false,
        ]);

        self::assertArrayNotHasKey('bogus', $result);
        self::assertFalse($result['user']);
        self::assertCount(6, $result);
    }

    /**
     * @dataProvider boolean_coercion_provider
     *
     * @param mixed $input    Raw column value from the request.
     * @param bool  $expected Coerced boolean.
     */
    public function test_normalize_export_columns_coerces_booleans(mixed $input, bool $expected): void {
        $result = Updatronix_Export_Body_Builder::normalize_export_columns(['status' => $input]);

        self::assertSame($expected, $result['status']);
    }

    /**
     * @return array<string, array{0: mixed, 1: bool}>
     */
    public static function boolean_coercion_provider(): array {
        return [
            'string false' => ['false', false],
            'string no' => ['no', false],
            'string off' => ['off', false],
            'int zero' => [0, false],
            'empty string' => ['', false],
            'string true' => ['true', true],
            'string one' => ['1', true],
            'string yes' => ['yes', true],
            'bool true' => [true, true],
        ];
    }

    public function test_truncation_footer_reports_counts(): void {
        $footer = Updatronix_Export_Body_Builder::truncation_footer(3, 10);

        self::assertStringContainsString('3 of 10', $footer);
    }
}
