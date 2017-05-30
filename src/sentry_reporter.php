<?php

declare(strict_types=1);

namespace betterphp\error_reporting;

class sentry_reporter extends reporter {

    private $report_url = '';

    private $client;
    private $handler;

    /**
     * Sets the Sentry URL that info will be sent to
     *
     * @param string $hostname The domain name of the sentry server
     * @param string $username The username to send
     * @param string $password The password to send
     * @param integer $project_id The sentry ID of the project
     *
     * @return void
     */
    public function set_report_url(string $hostname, string $username, string $password, int $project_id): void {
        $this->report_url = "https://{$username}:{$password}@{$hostname}/{$project_id}";
    }

     /**
     * @inheritDoc
     */
    public function register_reporting_handler(): void {
        // This makes no sense if there is nowhere to send errors
        if ($this->report_url === '') {
            throw new \Exception('Report URL is not set');
        }

        $this->client = new \Raven_Client($this->report_url, [
            'sample_rate' => 1,
            'environment' => $this->environment,
            'tags' => [
                'php_version' => phpversion(),
            ],
        ]);

        $this->handler = new \Raven_ErrorHandler($this->client);
        $this->handler->registerExceptionHandler();
        $this->handler->registerErrorHandler();
        $this->handler->registerShutdownFunction();
    }

    /**
     * Gets the Sentry client instance
     *
     * @return \Raven_Client the client
     */
    public function get_client(): \Raven_Client {
        return $this->client;
    }

}
