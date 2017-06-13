<?php

declare(strict_types=1);

use betterphp\error_reporting\reporter;

class ReporterTest extends ReporterTestCase {

    private function getMockReporter() {
        return $this->getMockBuilder(reporter::class)
                    ->getMockForAbstractClass();
    }

    public function testSetIniValues() {
        error_reporting(0);
        ini_set('display_errors', 'Off');
        ini_set('html_errors', 'On');

        $this->getMockReporter();

        // All values should have been changed
        $this->assertSame(E_ALL, error_reporting());
        $this->assertSame('On', ini_get('display_errors'));
        $this->assertSame('Off', ini_get('html_errors'));
    }

    public function testGet() {
        $new_reporter = $this->getMockReporter()::get();
        $other_new_reporter = $this->getMockReporter()::get();

        // Static call to get() should have returned a reporter
        $this->assertInstanceOf(reporter::class, $new_reporter);

        // Another call should return the same instance
        $this->assertSame($new_reporter, $other_new_reporter);
    }

}
