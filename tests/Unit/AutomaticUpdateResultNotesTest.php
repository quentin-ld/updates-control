<?php

/**
 * Unit tests for Updatronix_Automatic_Update_Result_Notes.
 *
 * @package updatronix
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test merging skin messages with WP_Error results in update note output.
 *
 * @covers \Updatronix_Automatic_Update_Result_Notes
 */
final class AutomaticUpdateResultNotesTest extends TestCase {
    public function test_success_path_returns_only_skin_lines(): void {
        $out = Updatronix_Automatic_Update_Result_Notes::merge_skin_messages_with_wp_result(
            ['<p>Line one</p>', 'Line two'],
            true
        );
        self::assertSame("Line one\nLine two", $out);
    }

    public function test_fs_unavailable_prepended_before_misleading_skin_messages(): void {
        $err = new WP_Error('fs_unavailable', 'Could not access filesystem.');
        $out = Updatronix_Automatic_Update_Result_Notes::merge_skin_messages_with_wp_result(
            ['Updating plugin: Foo', 'You already have the latest version of this plugin.'],
            $err
        );
        self::assertSame(
            "[fs_unavailable] Could not access filesystem.\n\nUpdating plugin: Foo\nYou already have the latest version of this plugin.",
            $out
        );
    }

    public function test_wp_error_only_when_messages_empty(): void {
        $err = new WP_Error('disk_full', 'No space left.');
        $out = Updatronix_Automatic_Update_Result_Notes::merge_skin_messages_with_wp_result([], $err);
        self::assertSame('[disk_full] No space left.', $out);
    }

    public function test_non_string_message_entries_skipped(): void {
        $out = Updatronix_Automatic_Update_Result_Notes::merge_skin_messages_with_wp_result(
            ['OK', null, 3, ''],
            true
        );
        self::assertSame('OK', $out);
    }
}
