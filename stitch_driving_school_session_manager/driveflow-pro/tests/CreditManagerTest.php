<?php
use WP_Mock\Tools\WPMockTestCase;

/**
 * Tests for DriveFlow_Credit_Manager.
 */
class CreditManagerTest extends WPMockTestCase {

    /** @var mixed */
    private $orig_wpdb;

    protected function setUp(): void {
        parent::setUp();
        $this->orig_wpdb = $GLOBALS['wpdb'] ?? null;
        if ( ! class_exists( 'DriveFlow_Credit_Manager' ) ) {
            WP_Mock::userFunction( 'current_time', array( 'return' => '2026-09-20 10:00:00' ) );
            WP_Mock::userFunction( 'wp_generate_password', array( 'return' => 'fake_token_abc123' ) );
            require __DIR__ . '/../includes/class-credit-manager.php';
        }
    }

    protected function tearDown(): void {
        $GLOBALS['wpdb'] = $this->orig_wpdb;
        parent::tearDown();
    }

    /** Build a minimal wpdb stub that returns fixed values for the common query methods. */
    private function stub_wpdb( $get_row = null, $get_var = null ): object {
        return new class( $get_row, $get_var ) {
            /** @var mixed */ private $row;
            /** @var mixed */ private $var;
            public string $prefix = 'wp_';
            public string $last_error = '';
            public function __construct( $row, $var ) {
                $this->row = $row;
                $this->var = $var;
            }
            public function prepare( string $q, ...$a ): string { return $q; }
            public function get_row( string $q, string $out ) { return $this->row; }
            public function get_var( string $q ) { return $this->var; }
            public function insert( string $t, array $d, array $f = array() ): int { return 1; }
            public function update( string $t, array $d, array $w, ...$rest ): int { return 1; }
        };
    }

    public function test_get_student_by_token_returns_null_for_unknown(): void {
        $GLOBALS['wpdb'] = $this->stub_wpdb( null, null );
        $result = DriveFlow_Credit_Manager::get_student_by_token( 'bad_token' );
        $this->assertNull( $result );
    }

    public function test_get_student_by_token_returns_row(): void {
        $expected = array( 'id' => 5, 'name' => 'Marcus Johnson', 'booking_token' => 'abc', 'total_sessions' => 10 );
        $GLOBALS['wpdb'] = $this->stub_wpdb( $expected, null );
        $result = DriveFlow_Credit_Manager::get_student_by_token( 'abc' );
        $this->assertSame( $expected, $result );
    }

    public function test_add_extra_sessions_returns_true_on_success(): void {
        WP_Mock::userFunction( 'get_current_user_id', array( 'return' => 1 ) );
        $GLOBALS['wpdb'] = $this->stub_wpdb( null, null );
        $result = DriveFlow_Credit_Manager::add_extra_sessions( 5, 3, 'test note' );
        $this->assertTrue( $result );
    }

    public function test_get_magic_booking_url_contains_token(): void {
        WP_Mock::userFunction( 'home_url', array( 'return' => 'https://example.com/' ) );
        $row = array( 'id' => 9, 'booking_token' => 'fake_token_abc123', 'name' => 'Sarah' );
        $GLOBALS['wpdb'] = $this->stub_wpdb( $row, 'fake_token_abc123' );
        $url = DriveFlow_Credit_Manager::get_magic_booking_url( 9 );
        $this->assertStringContainsString( 'fake_token_abc123', $url );
    }
}
