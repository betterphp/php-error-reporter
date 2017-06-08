<?php

declare(strict_types=1);

namespace betterphp\error_reporting;

class sentry_reporter extends reporter {

    private $report_url = '';
    private $user_context = [];

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

        // Reset the user context if it's been set before registering handlers.
        if (!empty($this->user_context)) {
            $this->set_user_context($this->user_context, false);
        }
    }

    /**
     * Gets the Sentry client instance
     *
     * @return \Raven_Client the client
     */
    public function get_client(): \Raven_Client {
        return $this->client;
    }

    /**
     * Sets a list of fields used to identify a user
     *
     * @param array $fields Key value pair of fields
     * @param boolean $merge If the new values shoudl be merged with the existing ones or replace them
     *
     * @return void
     */
    public function set_user_context(array $fields, bool $merge = true): void {
        // User context is tracked internally to allow for it bneing set before the handler is registered.
        if ($merge) {
            $fields = array_merge($this->user_context, $fields);
        }

        $this->user_context = $fields;

        if ($this->client !== null) {
            $this->client->user_context($fields, false);
        }
    }

    /**
     * Gets a list of user fields
     *
     * @return array Key value pair of fields
     */
    public function get_user_context(): array {
        return $this->user_context;
    }

}
