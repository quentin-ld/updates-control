<?php
/**
 * Unit tests for Updatronix_Security sanitization helpers.
 *
 * @package updatronix
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test redact_sensitive_text, sanitize_message, sanitize_trace, and related helpers.
 *
 * @covers \Updatronix_Security
 */
final class SecurityTest extends TestCase {
	// --- redact_sensitive_text (via sanitize_trace which does not html_entity_decode) ---

	/**
	 * Token query parameters are redacted.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function provide_token_redaction(): array {
		return array(
			'token'        => array( '?token=secret', '?token=[redacted]' ),
			'signature'    => array( '?signature=abc123', '?signature=[redacted]' ),
			'sig'          => array( '?sig=xyz', '?sig=[redacted]' ),
			'key'          => array( '?key=val', '?key=[redacted]' ),
			'access_token' => array( '?access_token=eyJ', '?access_token=[redacted]' ),
			'auth'         => array( '?auth=key', '?auth=[redacted]' ),
			'auth_token'   => array( '?auth_token=xyz', '?auth_token=[redacted]' ),
		);
	}

	/**
	 * Token query parameters are redacted when found in a trace.
	 *
	 * @dataProvider provide_token_redaction
	 *
	 * @param string $input    Raw trace input.
	 * @param string $expected Expected redacted fragment.
	 */
	public function test_redact_sensitive_text_redacts_tokens( string $input, string $expected ): void {
		$this->assertStringContainsString( $expected, Updatronix_Security::sanitize_trace( $input ) );
	}

	/**
	 * ?author= and ?authorization= must NOT be redacted (auth false-positive regression guard).
	 *
	 * @return array<string, array{string}>
	 */
	public static function provide_auth_false_positive(): array {
		return array(
			'author param'         => array( '?author=John' ),
			'authorization param'  => array( '?authorization=Bearer x' ),
			'authorize param'      => array( '?authorize=true' ),
			'authentication param' => array( '?authentication=basic' ),
		);
	}

	/**
	 * Auth-named parameters are not falsely redacted.
	 *
	 * @dataProvider provide_auth_false_positive
	 *
	 * @param string $input Raw trace input.
	 */
	public function test_redact_sensitive_text_does_not_redact_auth_substrings( string $input ): void {
		$result = Updatronix_Security::sanitize_trace( $input );
		$this->assertStringNotContainsString( '[redacted]', $result );
	}

	/**
	 * Emails are redacted.
	 */
	public function test_redact_sensitive_text_redacts_email(): void {
		$result = Updatronix_Security::sanitize_trace( 'user@example.com' );
		$this->assertStringContainsString( '[redacted-email]', $result );
	}

	/**
	 * Paths under ABSPATH/WP_PLUGIN_DIR/WP_CONTENT_DIR are replaced with basename + [redacted].
	 * The basename of ABSPATH (/tmp) is 'tmp', so the full path portion is replaced with 'tmp[redacted]'.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function provide_path_redaction(): array {
		return array(
			'abspath-root'   => array( '/tmp/index.php (42)', 'tmp[redacted] (42)' ),
			'abspath-nested' => array( '/tmp/wp-admin/admin.php', 'tmp[redacted]' ),
			'plugin-dir'     => array( '/tmp/plugins/akismet/akismet.php', 'tmp[redacted]' ),
			'content-dir'    => array( '/tmp/wp-content/themes/twentytwentyfour/style.css', 'tmp[redacted]' ),
		);
	}

	/**
	 * Known path prefixes are replaced with [redacted].
	 *
	 * @dataProvider provide_path_redaction
	 *
	 * @param string $input    Raw trace input.
	 * @param string $expected Expected redacted fragment.
	 */
	public function test_redact_sensitive_text_redacts_paths( string $input, string $expected ): void {
		$result = Updatronix_Security::sanitize_trace( $input );
		$this->assertStringContainsString( $expected, $result );
	}

	/**
	 * The full ABSPATH should NOT appear in the output.
	 */
	public function test_redact_sensitive_text_does_not_expose_abspath(): void {
		$result = Updatronix_Security::sanitize_trace( '/tmp/wp-content/plugins/test.php' );
		$this->assertStringNotContainsString( '/tmp/', $result );
	}

	// --- sanitize_message (includes html_entity_decode before redaction) ---

	/**
	 * HTML entity bypass: encoded = must be decoded before redaction.
	 * The input must use URL-style ?token= prefix so the regex matches.
	 */
	public function test_sanitize_message_redacts_html_entity_encoded_token(): void {
		$input  = '?token&#61;secret';
		$result = Updatronix_Security::sanitize_message( $input );
		$this->assertStringContainsString( '[redacted]', $result );
		$this->assertStringNotContainsString( 'secret', $result );
	}

	/**
	 * HTML entity hex encoded equals sign must also be decoded.
	 */
	public function test_sanitize_message_redacts_html_entity_hex_encoded_token(): void {
		$input  = '?token&#x3d;secret';
		$result = Updatronix_Security::sanitize_message( $input );
		$this->assertStringContainsString( '[redacted]', $result );
		$this->assertStringNotContainsString( 'secret', $result );
	}

	/**
	 * Normal message without sensitive data is unchanged.
	 */
	public function test_sanitize_message_preserves_innocuous_text(): void {
		$input  = 'Plugin updated successfully from 1.0 to 2.0.';
		$result = Updatronix_Security::sanitize_message( $input );
		$this->assertSame( $input, $result );
	}

	/**
	 * HTML entities in normal messages are decoded for readability.
	 */
	public function test_sanitize_message_decodes_html_entities(): void {
		$input  = 'Update &#8230; completed';
		$result = Updatronix_Security::sanitize_message( $input );
		$this->assertStringContainsString( "\u{2026}", $result ); // Ellipsis.
	}

	// --- sanitize_trace ---

	/**
	 * Trace is truncated to max length.
	 */
	public function test_sanitize_trace_truncates_to_max_length(): void {
		$input  = str_repeat( 'a', 70000 );
		$result = Updatronix_Security::sanitize_trace( $input, 100 );
		$this->assertSame( 100, mb_strlen( $result ) );
	}

	/**
	 * Empty input returns empty string.
	 */
	public function test_sanitize_trace_empty_input(): void {
		$this->assertSame( '', Updatronix_Security::sanitize_trace( '' ) );
	}

	// --- sanitize_message edge cases ---

	/**
	 * Empty input returns empty string.
	 */
	public function test_sanitize_message_empty_input(): void {
		$this->assertSame( '', Updatronix_Security::sanitize_message( '' ) );
	}

	/**
	 * Message with oversize input is truncated.
	 */
	public function test_sanitize_message_truncates_oversize(): void {
		$input  = str_repeat( 'x', 70000 );
		$result = Updatronix_Security::sanitize_message( $input );
		$this->assertSame( 65535, mb_strlen( $result ) );
	}
}
