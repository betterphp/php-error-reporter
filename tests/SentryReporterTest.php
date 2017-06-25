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

    /**
     * @dataProvider dataGetReporttUrl
     */
    public function testGetReportUrl(array $expected_credentials, string $method_name, string $expected_url): void {
        $reporter = new sentry_reporter();

        $reporter->set_report_url(...array_values($expected_credentials));

        $this->assertSame($expected_url, $reporter->$method_name());
    }

    public function dataGetReporttUrl(): array {
        $creds = [
            'hostname' => 'very.host',
            'username' => 'such_user',
            'password' => 'many_secure',
            'project_id' => 1337,
        ];

        return [
            [
                $creds,
                'get_internal_report_url',
                "https://{$creds['username']}:{$creds['password']}@{$creds['hostname']}/{$creds['project_id']}",
            ],
            [
                $creds,
                'get_client_report_url',
                "https://{$creds['username']}@{$creds['hostname']}/{$creds['project_id']}",
            ],
        ];
    }

    /**
     * @dataProvider dataGetReportUrlWithNoUrlSet
     */
    public function testGetReportUrlWithNoUrlSet(string $method_name): void {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('URL has not been set');

        $reporter = new sentry_reporter();
        $reporter->$method_name();
    }

    public function dataGetReportUrlWithNoUrlSet(): array {
        return [
            ['get_internal_report_url'],
            ['get_client_report_url'],
        ];
    }

}
