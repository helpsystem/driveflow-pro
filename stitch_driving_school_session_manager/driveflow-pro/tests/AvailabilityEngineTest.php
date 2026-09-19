<?php
use PHPUnit\Framework\TestCase;
use WP_Mock\Tools\WPMockTestCase;

/**
 * Tests for DriveFlow_Availability_Engine::check_collision().
 *
 * The tests stub $wpdb so no real database is needed.
 */
class AvailabilityEngineTest extends WPMockTestCase {

    private $original_wpdb;

    protected function setUp(): void {
        parent::setUp();
        $this->original_wpdb = $GLOBALS['wpdb'] ?? null;
    }

    protected function tearDown(): void {
        $GLOBALS['wpdb'] = $this->original_wpdb;
        parent::tearDown();
    }

    private function make_wpdb( array $rows ): object {
        $db = $this->createMock( stdClass::class );
        $db->prefix = 'wp_';
        // PHPUnit mock can't stub magic properties, so we use a small helper object.
        return new class( $rows ) {
            public string $prefix = 'wp_';
            private array $rows;
            public function __construct( array $rows ) { $this->rows = $rows; }
            public function prepare( string $q, ...$a ): string { return $q; }
            public function get_results( string $q, string $output ): array { return $this->rows; }
        };
    }

    private function load_engine(): void {
        if ( ! class_exists( 'DriveFlow_Availability_Engine' ) ) {
            WP_Mock::userFunction( 'current_time', [ 'return' => '2026-09-20 10:00:00' ] );
            require __DIR__ . '/../includes/class-availability-engine.php';
        }
    }

    public function test_no_conflict_when_no_rows(): void {
        $this->load_engine();
        $GLOBALS['wpdb'] = $this->make_wpdb( [] );

        $result = DriveFlow_Availability_Engine::check_collision(
            'Mr. Anderson', '8WD4931',
            '2026-09-20 09:00:00', '2026-09-20 11:00:00'
        );

        $this->assertFalse( $result['conflict'] );
        $this->assertEmpty( $result['reason'] );
    }

    public function test_conflict_detected_for_instructor(): void {
        $this->load_engine();
        $GLOBALS['wpdb'] = $this->make_wpdb( [
            [ 'conflict_type' => 'instructor', 'session_number' => '42',
              'scheduled_start' => '2026-09-20 09:00:00', 'scheduled_end' => '2026-09-20 11:00:00' ],
        ] );

        $result = DriveFlow_Availability_Engine::check_collision(
            'Mr. Anderson', '8WD4931',
            '2026-09-20 09:30:00', '2026-09-20 11:30:00'
        );

        $this->assertTrue( $result['conflict'] );
        $this->assertStringContainsString( 'instructor', strtolower( $result['reason'] ) );
    }

    public function test_conflict_detected_for_vehicle(): void {
        $this->load_engine();
        $GLOBALS['wpdb'] = $this->make_wpdb( [
            [ 'conflict_type' => 'vehicle', 'session_number' => '7',
              'scheduled_start' => '2026-09-20 08:00:00', 'scheduled_end' => '2026-09-20 10:00:00' ],
        ] );

        $result = DriveFlow_Availability_Engine::check_collision(
            'Ms. Rivera', '8WD4931',
            '2026-09-20 09:00:00', '2026-09-20 11:00:00'
        );

        $this->assertTrue( $result['conflict'] );
        $this->assertStringContainsString( 'vehicle', strtolower( $result['reason'] ) );
    }

    public function test_excluded_session_not_flagged(): void {
        $this->load_engine();
        // When a session is excluded (same id = rescheduling itself), no conflict.
        $GLOBALS['wpdb'] = $this->make_wpdb( [] ); // exclude_id filters it out at SQL level.

        $result = DriveFlow_Availability_Engine::check_collision(
            'Mr. Anderson', '8WD4931',
            '2026-09-20 09:00:00', '2026-09-20 11:00:00',
            42 // exclude_id
        );

        $this->assertFalse( $result['conflict'] );
    }
}
