<?php

/**
 * Unit tests for Updatronix_AutoUpdateDelay ledger pipeline.
 *
 * Tests stable_ledger_hash for all four types (plugin, theme, core, translation)
 * including edge cases with missing fields and empty data.
 *
 * @package updatronix
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @covers \Updatronix_AutoUpdateDelay
 */
final class AutoUpdateDelayLedgerTest extends TestCase {
    /**
     * Reflection method for stable_ledger_hash.
     *
     * @var \ReflectionMethod|null
     */
    private static ?\ReflectionMethod $stableLedgerHash = null;

    public static function setUpBeforeClass(): void {
        self::$stableLedgerHash = new \ReflectionMethod(
            Updatronix_AutoUpdateDelay::class,
            'stable_ledger_hash'
        );
        self::$stableLedgerHash->setAccessible(true);
    }

    /**
     * Invoke stable_ledger_hash with given type and item.
     *
     * @param string $type 'plugin'|'theme'|'core'|'translation'.
     * @param object $item Offer object.
     * @return string Hash string.
     */
    private function hash(string $type, object $item): string {
        return self::$stableLedgerHash->invoke(null, $type, $item);
    }

    // --- Plugin ---

    public function test_plugin_hash_with_plugin_and_new_version(): void {
        $item = (object) [
            'plugin' => 'akismet/akismet.php',
            'new_version' => '5.3',
        ];
        $hash = $this->hash('plugin', $item);
        self::assertSame(64, strlen($hash));
        // Same input must produce same hash.
        self::assertSame($hash, $this->hash('plugin', $item));
    }

    public function test_plugin_hash_is_case_insensitive(): void {
        $upper = $this->hash('plugin', (object) [
            'plugin' => 'AKISMET/AKISMET.php',
            'new_version' => '5.3',
        ]);
        $lower = $this->hash('plugin', (object) [
            'plugin' => 'akismet/akismet.php',
            'new_version' => '5.3',
        ]);
        self::assertSame($lower, $upper);
    }

    public function test_plugin_hash_without_new_version_falls_back(): void {
        $item = (object) ['plugin' => 'hello.php'];
        $hash = $this->hash('plugin', $item);
        self::assertSame(64, strlen($hash));
    }

    public function test_plugin_hash_without_plugin_falls_back(): void {
        $item = (object) ['new_version' => '1.0'];
        $hash = $this->hash('plugin', $item);
        self::assertSame(64, strlen($hash));
    }

    // --- Theme ---

    public function test_theme_hash_with_theme_and_new_version(): void {
        $item = (object) [
            'theme' => 'twentytwentyfive',
            'new_version' => '2.0',
        ];
        $hash = $this->hash('theme', $item);
        self::assertSame(64, strlen($hash));
        // Same input must produce same hash.
        self::assertSame($hash, $this->hash('theme', $item));
    }

    public function test_theme_hash_is_case_insensitive(): void {
        $upper = $this->hash('theme', (object) [
            'theme' => 'TWENTYTWENTYFIVE',
            'new_version' => '2.0',
        ]);
        $lower = $this->hash('theme', (object) [
            'theme' => 'twentytwentyfive',
            'new_version' => '2.0',
        ]);
        self::assertSame($lower, $upper);
    }

    // --- Core ---

    public function test_core_hash_includes_current_and_version(): void {
        $item = (object) [
            'current' => '6.5.0',
            'version' => '6.6.1',
        ];
        $hash = $this->hash('core', $item);
        self::assertSame(64, strlen($hash));
        // Same input must produce same hash.
        self::assertSame($hash, $this->hash('core', $item));
    }

    public function test_core_hash_differs_when_offered_version_differs(): void {
        $hash_a = $this->hash('core', (object) [
            'current' => '6.5.0',
            'version' => '6.6.1',
        ]);
        $hash_b = $this->hash('core', (object) [
            'current' => '6.5.0',
            'version' => '6.6.2',
        ]);
        self::assertNotSame(
            $hash_a,
            $hash_b,
            'Two different core offers for the same running version must produce different hashes.'
        );
    }

    public function test_core_hash_without_version_uses_current_only(): void {
        $item = (object) ['current' => '6.5.0'];
        $hash = $this->hash('core', $item);
        self::assertSame(64, strlen($hash));
    }

    public function test_core_hash_with_only_version(): void {
        $item = (object) ['version' => '6.6.1'];
        $hash = $this->hash('core', $item);
        self::assertSame(64, strlen($hash));
    }

    public function test_core_hash_without_current_or_version_falls_back(): void {
        $item = (object) ['response' => 'autoupdate'];
        $hash = $this->hash('core', $item);
        self::assertSame(64, strlen($hash));
    }

    // --- Translation ---

    public function test_translation_hash_with_all_fields(): void {
        $item = (object) [
            'type' => 'plugin',
            'slug' => 'akismet',
            'language' => 'fr_FR',
            'version' => '1.0',
        ];
        $hash = $this->hash('translation', $item);
        self::assertSame(64, strlen($hash));
        self::assertSame($hash, $this->hash('translation', $item));
    }

    public function test_translation_hash_without_fields_falls_back(): void {
        $item = (object) ['some_other' => 'data'];
        $hash = $this->hash('translation', $item);
        self::assertSame(64, strlen($hash));
    }

    // --- Determinism ---

    public function test_same_input_always_produces_same_hash(): void {
        $item = (object) [
            'plugin' => 'hello.php',
            'new_version' => '1.1',
        ];
        $first = $this->hash('plugin', $item);
        for ($i = 0; $i < 10; $i++) {
            self::assertSame($first, $this->hash('plugin', $item));
        }
    }
}