<?php

/**
 * Unit tests for Updatronix_Core_Update_Log_Versions (pure helpers, no WordPress runtime).
 *
 * @package updatronix
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @covers \Updatronix_Core_Update_Log_Versions
 */
final class CoreUpdateLogVersionsTest extends TestCase {
    public function test_parse_wp_version_from_file_contents_extracts_literal(): void {
        $contents = "<?php\n\$wp_version = '6.9.4';\n";
        self::assertSame('6.9.4', Updatronix_Core_Update_Log_Versions::parse_wp_version_from_file_contents($contents));
    }

    public function test_parse_wp_version_from_file_contents_double_quotes(): void {
        $contents = '<?php $wp_version = "7.0-RC2";';
        self::assertSame('7.0-RC2', Updatronix_Core_Update_Log_Versions::parse_wp_version_from_file_contents($contents));
    }

    public function test_parse_wp_version_from_file_contents_empty_when_not_found(): void {
        self::assertSame('', Updatronix_Core_Update_Log_Versions::parse_wp_version_from_file_contents(''));
        self::assertSame('', Updatronix_Core_Update_Log_Versions::parse_wp_version_from_file_contents('no version here'));
    }

    public function test_resolve_core_version_after_triple_prefers_disk(): void {
        self::assertSame(
            '6.9.2',
            Updatronix_Core_Update_Log_Versions::resolve_core_version_after_triple('6.9.2', '6.9.1', '6.9.0')
        );
    }

    public function test_resolve_core_version_after_triple_falls_back_to_pending_then_bloginfo(): void {
        self::assertSame(
            '7.0-RC2',
            Updatronix_Core_Update_Log_Versions::resolve_core_version_after_triple('', '7.0-RC2', '6.9.4')
        );
        self::assertSame(
            '6.9.4',
            Updatronix_Core_Update_Log_Versions::resolve_core_version_after_triple('', '', '6.9.4')
        );
    }

    public function test_resolve_action_type_downgrade_update_same_version(): void {
        self::assertSame('downgrade', Updatronix_Core_Update_Log_Versions::resolve_action_type('7.0', '6.9', 'update'));
        self::assertSame('update', Updatronix_Core_Update_Log_Versions::resolve_action_type('6.9', '7.0', 'update'));
        self::assertSame('same_version', Updatronix_Core_Update_Log_Versions::resolve_action_type('6.9.4', '6.9.4', 'update'));
    }

    public function test_resolve_action_type_uses_default_when_either_version_empty(): void {
        self::assertSame('update', Updatronix_Core_Update_Log_Versions::resolve_action_type('', '7.0', 'update'));
        self::assertSame('install', Updatronix_Core_Update_Log_Versions::resolve_action_type('1.0', '', 'install'));
    }
}
