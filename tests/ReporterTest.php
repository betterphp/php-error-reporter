<?php

declare(strict_types=1);

use betterphp\utils\reflection;
use betterphp\native_mock\native_mock;

use betterphp\error_reporting\reporter;

/**
 * @covers betterphp\error_reporting\reporter
 */
class ReporterTest extends ReporterTestCase {

    use native_mock;

    public function setUp() {
        $this->nativeMockSetUp();
    }

    public function tearDown() {
        $this->nativeMockTearDown();
    }

    private function getMockReporter(): reporter {
        $reporter = $this->getMockBuilder(reporter::class)
                         ->getMockForAbstractClass();

        $noop = function (): void {
            return;
        };

        $this->redefineMethod(reporter::class, 'terminate', $noop);
        $this->redefineMethod(reporter::class, 'clear_all_output', $noop);

        return $reporter;
    }

    public function testSetIniValues(): void {
        error_reporting(0);
        ini_set('display_errors', 'Off');
        ini_set('html_errors', 'On');

        $this->getMockReporter();

        // All values should have been changed
        $this->assertSame(E_ALL, error_reporting());
        $this->assertSame('On', ini_get('display_errors'));
        $this->assertSame('Off', ini_get('html_errors'));
    }

    public function testGet(): void {
        $new_reporter = $this->getMockReporter()::get();
        $other_new_reporter = $this->getMockReporter()::get();

        // Static call to get() should have returned a reporter
        $this->assertInstanceOf(reporter::class, $new_reporter);

        // Another call should return the same instance
        $this->assertSame($new_reporter, $other_new_reporter);
    }

    /**
     * @dataProvider dataSet
     */
    public function testSet(string $method_name, string $property_name, $value): void {
        $reporter = $this->getMockReporter();

        // Set an expected value
        $reporter->$method_name($value);

        $actual = reflection::get_property($reporter, $property_name);

        // Should have been set
        $this->assertSame($value, $actual);
    }

    public function dataSet(): array {
        return [
            ['set_show_errors', 'show_errors', true],
            ['set_redirect_url', 'redirect_url', 'such url, very invalid'],
            ['set_environment', 'environment', 'much_env'],
        ];
    }

    public function testRedirectToErrorUrl(): void {
        $reporter = $this->getMockReporter();

        $new_headers = [];

        $this->redefineFunction('header', function (string $header) use (&$new_headers) {
            $new_headers[] = $header;
        });

        $expected_url = 'such_url/very_handler.html';
        $expected_header = "Location: {$expected_url}";

        $reporter->set_redirect_url($expected_url);

        reflection::call_method($reporter, 'redirect_to_error_url');

        $this->assertCount(1, $new_headers);
        $this->assertSame($expected_header, $new_headers[0]);
    }

    public function testRedirectToErrorUrlWithDisabledErrors(): void {
        $reporter = $this->getMockReporter();

        // Turn off error_reporting
        $initial_value = error_reporting(0);

        ob_start();
        reflection::call_method($reporter, 'redirect_to_error_url');
        $output = ob_get_clean();

        // Now turn it back on
        error_reporting($initial_value);

        // Normally a message would output if there is no URL set
        $this->assertEmpty($output);
    }

    public function testRedirectToErrorUrlWithNoUrl(): void {
        $reporter = $this->getMockReporter();

        ob_start();
        reflection::call_method($reporter, 'redirect_to_error_url');
        $output = ob_get_clean();

        $this->assertSame('Internal error', $output);
    }

    public function testRegisterRedirectHandler(): void {
        $reporter = $this->getMockReporter();

        // This is a placeholder
        $this->assertTrue(true);
    }

    public function testShowError(): void {
        $reporter = $this->getMockReporter();

        // This is a placeholder
        $this->assertTrue(true);
    }

    public function testGetErrorMessage(): void {
        $reporter = $this->getMockReporter();

        $expected_message = 'E_NOTICE: Watch out in file on line 123';
        $actual_message = reflection::call_method($reporter, 'get_error_message', [
            E_NOTICE,
            'Watch out',
            'file',
            123,
        ]);

        $this->assertSame($expected_message, $actual_message);
    }

    public function testRegisterOutputHandler(): void {
        $reporter = $this->getMockReporter();

        // This is a placeholder
        $this->assertTrue(true);
    }

}
