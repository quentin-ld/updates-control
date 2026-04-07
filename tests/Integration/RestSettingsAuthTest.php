<?php
/**
 * REST API auth smoke tests for Updatronix settings route.
 *
 * @package updatronix
 */

declare(strict_types=1);

/**
 * Verify REST API authentication and authorization for the settings and logs routes.
 *
 * @coversNothing
 */
final class RestSettingsAuthTest extends WP_UnitTestCase {
    /**
     * @var array<string, mixed>
     */
    private $rest_test_server_backup = [];

    /**
     * Restore $_SERVER keys and clean up REST auth globals.
     *
     * @return void
     */
    protected function tearDown(): void {
        foreach ($this->rest_test_server_backup as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
        $this->rest_test_server_backup = [];
        unset($GLOBALS['wp_rest_auth_cookie']);

        parent::tearDown();
    }

    /**
     * Simulate cookie-based REST authentication for a given HTTP method.
     *
     * WordPress only enforces the REST CSRF nonce when cookie authentication is in use
     * (`$GLOBALS['wp_rest_auth_cookie'] === true`). Simulate that for mutation tests.
     *
     * @see rest_cookie_check_errors()
     *
     * @param string $method HTTP method to simulate (e.g. 'POST').
     * @return void
     */
    private function begin_rest_cookie_auth_simulation(string $method): void {
        $this->rest_test_server_backup['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = $method;
    }

    /**
     * Set or unset the X-WP-Nonce server header for REST requests.
     *
     * @param string|null $nonce Value for X-WP-Nonce, or null to omit the header.
     * @return void
     */
    private function set_rest_nonce_header(?string $nonce): void {
        $key = 'HTTP_X_WP_NONCE';
        if (!array_key_exists($key, $this->rest_test_server_backup)) {
            $this->rest_test_server_backup[$key] = $_SERVER[$key] ?? null;
        }
        if ($nonce === null) {
            unset($_SERVER[$key]);
        } else {
            $_SERVER[$key] = $nonce;
        }
    }

    /**
     * Dispatch a REST request after explicitly running cookie authentication.
     *
     * `rest_do_request()` dispatches without `WP_REST_Server::serve_request()`, so
     * `check_authentication()` (cookie + `X-WP-Nonce`) is skipped unless called explicitly.
     *
     * @param WP_REST_Request $request REST request to dispatch.
     * @return WP_REST_Response|WP_Error WP_Error only when authentication failed.
     */
    private function rest_dispatch_with_cookie_auth(WP_REST_Request $request) {
        $auth = rest_get_server()->check_authentication();
        if (is_wp_error($auth)) {
            return $auth;
        }

        return rest_do_request($request);
    }

    public function test_get_settings_returns_403_when_not_logged_in(): void {
        wp_set_current_user(0);

        $request = new WP_REST_Request('GET', '/updatronix/v1/settings');
        $response = rest_do_request($request);

        $status = $response->get_status();
        self::assertContains($status, [401, 403], 'Anonymous user must not read settings.');
    }

    public function test_get_settings_returns_200_for_administrator_with_cap(): void {
        $user_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);

        $request = new WP_REST_Request('GET', '/updatronix/v1/settings');
        $response = rest_do_request($request);

        self::assertSame(200, $response->get_status());
        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertArrayHasKey('options', $data);
        self::assertIsArray($data['options']);
    }

    /**
     * @dataProvider low_privilege_roles_without_cap
     */
    public function test_get_settings_returns_403_for_user_without_manage_cap(string $role): void {
        $user_id = self::factory()->user->create(['role' => $role]);
        wp_set_current_user($user_id);

        $request = new WP_REST_Request('GET', '/updatronix/v1/settings');
        $response = rest_do_request($request);

        self::assertSame(403, $response->get_status(), 'User without ' . UPDATRONIX_CAP_MANAGE . ' must not read settings.');
    }

    /**
     * @dataProvider low_privilege_roles_without_cap
     */
    public function test_get_logs_returns_403_for_user_without_manage_cap(string $role): void {
        $user_id = self::factory()->user->create(['role' => $role]);
        wp_set_current_user($user_id);

        $request = new WP_REST_Request('GET', '/updatronix/v1/logs');
        $response = rest_do_request($request);

        self::assertSame(403, $response->get_status(), 'User without ' . UPDATRONIX_CAP_MANAGE . ' must not read logs.');
    }

    /**
     * Provide roles that lack the manage_updatronix capability.
     *
     * @return list<array{0: string}>
     */
    public static function low_privilege_roles_without_cap(): array {
        return [
            ['subscriber'],
            ['editor'],
        ];
    }

    public function test_post_settings_with_cookie_auth_and_valid_nonce_returns_200(): void {
        $user_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
        $GLOBALS['wp_rest_auth_cookie'] = true;
        $this->begin_rest_cookie_auth_simulation('POST');
        $this->set_rest_nonce_header(wp_create_nonce('wp_rest'));

        $request = new WP_REST_Request('POST', '/updatronix/v1/settings');
        $request->set_param('logging_enabled', true);

        $response = $this->rest_dispatch_with_cookie_auth($request);
        self::assertInstanceOf(WP_REST_Response::class, $response);

        self::assertSame(200, $response->get_status());
        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertArrayHasKey('options', $data);
    }

    /**
     * Under simulated cookie auth, a mutating request without `X-WP-Nonce` clears the session
     * (see {@see rest_cookie_check_errors()}); the route then rejects the request (typically 401).
     */
    public function test_post_settings_with_cookie_auth_and_missing_nonce_is_rejected(): void {
        $user_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
        $GLOBALS['wp_rest_auth_cookie'] = true;
        $this->begin_rest_cookie_auth_simulation('POST');
        $this->set_rest_nonce_header(null);

        $auth = rest_get_server()->check_authentication();
        self::assertNotWPError($auth, 'Missing nonce should not yield a WP_Error from check_authentication().');
        self::assertSame(0, get_current_user_id(), 'Cookie auth without nonce clears the current user (see rest_cookie_check_errors()).');

        $request = new WP_REST_Request('POST', '/updatronix/v1/settings');
        $request->set_param('logging_enabled', false);

        $response = rest_do_request($request);

        self::assertContains(
            $response->get_status(),
            [401, 403],
            'Mutating REST request with cookie auth but no nonce must not succeed.'
        );
    }

    /**
     * Invalid nonce must not succeed (403 from core).
     */
    public function test_post_settings_with_cookie_auth_and_invalid_nonce_returns_403(): void {
        $user_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
        $GLOBALS['wp_rest_auth_cookie'] = true;
        $this->begin_rest_cookie_auth_simulation('POST');
        $this->set_rest_nonce_header('invalid-nonce');

        $auth = rest_get_server()->check_authentication();
        self::assertWPError($auth);
        self::assertSame('rest_cookie_invalid_nonce', $auth->get_error_code());

        $response = rest_get_server()->error_to_response($auth);
        self::assertSame(403, $response->get_status());
        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertArrayHasKey('code', $data);
        self::assertSame('rest_cookie_invalid_nonce', $data['code']);
    }
}
