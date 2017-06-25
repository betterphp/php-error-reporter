<?php

declare(strict_types=1);

use betterphp\error_reporting\sentry_reporter;
use betterphp\utils\reflection;

/**
 * @covers betterphp\error_reporting\sentry_reporter
 */
class SentryReporterTest extends ReporterTestCase {

    public function testSetUrl(): void {
        $reporter = new sentry_reporter();

        $expected_hostname = 'very.host';
        $expected_username = 'such_user';
        $expected_password = 'many_secure';
        $expected_project_id = 1337;

        $reporter->set_report_url($expected_hostname, $expected_username, $expected_password, $expected_project_id);

        $actual_hostname = reflection::get_property($reporter, 'hostname');
        $actual_username = reflection::get_property($reporter, 'username');
        $actual_password = reflection::get_property($reporter, 'password');
        $actual_project_id = reflection::get_property($reporter, 'project_id');

        // All should have been set correctly
        $this->assertSame($expected_hostname, $actual_hostname);
        $this->assertSame($expected_username, $actual_username);
        $this->assertSame($expected_password, $actual_password);
        $this->assertSame($expected_project_id, $actual_project_id);
    }

}
