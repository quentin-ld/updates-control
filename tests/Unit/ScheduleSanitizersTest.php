<?php

/**
 * Unit tests for schedule sanitizer functions (no WordPress runtime).
 *
 * @package updatronix
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @covers updatronix_sanitize_schedule_array
 * @covers updatronix_merge_partial_schedule_into
 * @covers updatronix_sanitize_schedule_wall_time
 */
final class ScheduleSanitizersTest extends TestCase {
    private const BASE_DIR = '/tmp/updatronix-tests';

    public static function setUpBeforeClass(): void {
        if (!defined('ABSPATH')) {
            define('ABSPATH', '/tmp/');
        }
    }
    /**
     * Load the options file before each test.
     */
    protected function setUp(): void {
        require_once dirname(__DIR__, 2) . '/inc/settings/options.php';
    }

    // --- updatronix_sanitize_schedule_wall_time ---

    /**
     * Valid time strings are normalized to HH:mm.
     *
     * @return array<string, array{string, string}>
     */
    public static function provide_valid_wall_times(): array {
        return [
            'zero-padded' => ['03:04', '03:04'],
            'single-digit hour' => ['3:04', '03:04'],
            'single-digit minute' => ['03:4', '03:00'],
            'midnight' => ['00:00', '00:00'],
            'end of day' => ['23:59', '23:59'],
            'leading/trailing space' => [' 03:04 ', '03:00'],
        ];
    }

    /**
     * @dataProvider provide_valid_wall_times
     */
    public function test_sanitize_wall_time_valid(string $input, string $expected): void {
        $this->assertSame($expected, updatronix_sanitize_schedule_wall_time($input));
    }

    /**
     * Invalid time strings fall back to '03:00'.
     *
     * @return array<string, array{string}>
     */
    public static function provide_invalid_wall_times(): array {
        return [
            'empty string' => [''],
            'hour out of range' => ['99:99'],
            'non-numeric' => ['ab:cd'],
            'partial hour' => ['3'],
            'hour only with colon' => ['03:'],
            'minute only' => [':04'],
            'negative hour' => ['-1:00'],
            'hour overflow' => ['24:00'],
            'minute overflow' => ['00:60'],
            'garbage' => ['not-a-time'],
            'spaces only' => ['   '],
        ];
    }

    /**
     * @dataProvider provide_invalid_wall_times
     */
    public function test_sanitize_wall_time_invalid_falls_back(string $input): void {
        $this->assertSame('03:00', updatronix_sanitize_schedule_wall_time($input));
    }

    // --- updatronix_sanitize_schedule_array ---

    /**
     * Empty input returns all defaults.
     */
    public function test_sanitize_schedule_array_empty_input(): void {
        $result = updatronix_sanitize_schedule_array([]);
        $this->assertSame('', $result['update_check']['recurrence']);
        $this->assertSame('', $result['update_check']['time']);
        $this->assertFalse($result['delay_updates']['enabled']);
        $this->assertSame(0, $result['delay_updates']['delay_value']);
    }

    /**
     * Missing update_check subtree returns defaults for those keys.
     */
    public function test_sanitize_schedule_array_missing_update_check(): void {
        $result = updatronix_sanitize_schedule_array(['delay_updates' => ['enabled' => true, 'delay_value' => 3]]);
        $this->assertSame('', $result['update_check']['recurrence']);
        $this->assertSame('', $result['update_check']['time']);
        $this->assertTrue($result['delay_updates']['enabled']);
        $this->assertSame(3, $result['delay_updates']['delay_value']);
    }

    /**
     * Missing delay_updates subtree returns defaults for those keys.
     */
    public function test_sanitize_schedule_array_missing_delay_updates(): void {
        $result = updatronix_sanitize_schedule_array(['update_check' => ['recurrence' => 'daily', 'time' => '04:15']]);
        $this->assertSame('daily', $result['update_check']['recurrence']);
        $this->assertSame('04:15', $result['update_check']['time']);
        $this->assertFalse($result['delay_updates']['enabled']);
        $this->assertSame(0, $result['delay_updates']['delay_value']);
    }

    /**
     * Invalid recurrence falls back to empty string.
     */
    public function test_sanitize_schedule_array_invalid_recurrence(): void {
        $result = updatronix_sanitize_schedule_array(['update_check' => ['recurrence' => 'monthly']]);
        $this->assertSame('', $result['update_check']['recurrence']);
    }

    /**
     * Delay enabled with no delay_value defaults to 0 (then sanitized to 0 because enabled=false).
     */
    public function test_sanitize_schedule_array_delay_enabled_no_value(): void {
        $result = updatronix_sanitize_schedule_array(['delay_updates' => ['enabled' => true]]);
        $this->assertTrue($result['delay_updates']['enabled']);
        $this->assertSame(1, $result['delay_updates']['delay_value'], 'When enabled and no value given, should default to 1.');
    }

    /**
     * Oversized delay_value is capped at 365.
     */
    public function test_sanitize_schedule_array_delay_oversize(): void {
        $result = updatronix_sanitize_schedule_array(['delay_updates' => ['enabled' => true, 'delay_value' => 999]]);
        $this->assertSame(365, $result['delay_updates']['delay_value']);
    }

    /**
     * Delay disabled with a value forces value to 0.
     */
    public function test_sanitize_schedule_array_delay_disabled_clears_value(): void {
        $result = updatronix_sanitize_schedule_array(['delay_updates' => ['enabled' => false, 'delay_value' => 30]]);
        $this->assertFalse($result['delay_updates']['enabled']);
        $this->assertSame(0, $result['delay_updates']['delay_value']);
    }

    /**
     * Hourly recurrence strips time regardless of what was passed.
     */
    public function test_sanitize_schedule_array_hourly_clears_time(): void {
        $result = updatronix_sanitize_schedule_array(['update_check' => ['recurrence' => 'hourly', 'time' => '05:00']]);
        $this->assertSame('hourly', $result['update_check']['recurrence']);
        $this->assertSame('', $result['update_check']['time']);
    }

    // --- updatronix_merge_partial_schedule_into ---

    /**
     * Empty partial returns baseline unchanged.
     */
    public function test_merge_partial_empty_returns_baseline(): void {
        $baseline = [
            'update_check' => ['recurrence' => 'daily', 'time' => '03:00'],
            'delay_updates' => ['enabled' => true, 'delay_value' => 7],
        ];
        $result = updatronix_merge_partial_schedule_into([], $baseline);
        $this->assertSame($baseline, $result);
    }

    /**
     * Partial overrides only the specified keys.
     */
    public function test_merge_partial_partial_override(): void {
        $baseline = [
            'update_check' => ['recurrence' => 'daily', 'time' => '03:00'],
            'delay_updates' => ['enabled' => true, 'delay_value' => 7],
        ];
        $result = updatronix_merge_partial_schedule_into(
            ['update_check' => ['recurrence' => 'weekly']],
            $baseline
        );
        $this->assertSame('weekly', $result['update_check']['recurrence']);
        $this->assertSame('03:00', $result['update_check']['time'], 'Time should remain from baseline.');
        $this->assertSame(true, $result['delay_updates']['enabled']);
        $this->assertSame(7, $result['delay_updates']['delay_value']);
    }

    /**
     * Partial with null-like values uses array_key_exists, so explicit false is kept.
     */
    public function test_merge_partial_explicit_false_overrides(): void {
        $baseline = [
            'update_check' => ['recurrence' => 'daily', 'time' => '03:00'],
            'delay_updates' => ['enabled' => true, 'delay_value' => 7],
        ];
        $result = updatronix_merge_partial_schedule_into(
            ['delay_updates' => ['enabled' => false]],
            $baseline
        );
        $this->assertFalse($result['delay_updates']['enabled']);
        $this->assertSame(7, $result['delay_updates']['delay_value'], 'delay_value should stay from baseline.');
    }

    /**
     * Partial with non-array update_check is ignored.
     */
    public function test_merge_partial_non_array_update_check_ignored(): void {
        $baseline = [
            'update_check' => ['recurrence' => 'daily', 'time' => '03:00'],
            'delay_updates' => ['enabled' => false, 'delay_value' => 0],
        ];
        $result = updatronix_merge_partial_schedule_into(
            ['update_check' => 'invalid'],
            $baseline
        );
        $this->assertSame($baseline, $result);
    }

    /**
     * Partial with non-array delay_updates is ignored.
     */
    public function test_merge_partial_non_array_delay_updates_ignored(): void {
        $baseline = [
            'update_check' => ['recurrence' => 'daily', 'time' => '03:00'],
            'delay_updates' => ['enabled' => false, 'delay_value' => 0],
        ];
        $result = updatronix_merge_partial_schedule_into(
            ['delay_updates' => 'invalid'],
            $baseline
        );
        $this->assertSame($baseline, $result);
    }
}