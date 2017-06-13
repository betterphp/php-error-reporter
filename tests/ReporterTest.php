<?php

declare(strict_types=1);

use betterphp\utils\reflection;

use betterphp\error_reporting\reporter;

class ReporterTest extends ReporterTestCase {

    private function getMockReporter(): reporter {
        return $this->getMockBuilder(reporter::class)
                    ->getMockForAbstractClass();
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

}
