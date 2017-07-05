<?php

declare(strict_types=1);

namespace betterphp\error_reporting;

class sentry_reporter extends reporter {

    private $hostname = null;
    private $username = null;
    private $password = null;
    private $project_id = null;

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
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->project_id = $project_id;
    }

    /**
     * Gets the URL to report internal errors to
     *
     * @return string The URL
     */
    public function get_internal_report_url(): string {
        if ($this->hostname === null
        || $this->username === null
        || $this->password === null
        || $this->project_id === null) {
            throw new \Exception('URL has not been set');
        }

        return "https://{$this->username}:{$this->password}@{$this->hostname}/{$this->project_id}";
    }

    /**
     * Gets the URL to report client errors to
     *
     * @return string The URL
     */
    public function get_client_report_url(): string {
        if ($this->hostname === null
        || $this->username === null
        || $this->password === null
        || $this->project_id === null) {
            throw new \Exception('URL has not been set');
        }

        return "https://{$this->username}@{$this->hostname}/{$this->project_id}";
    }

    /**
     * Gets the client side setup code
     *
     * @return string The JavaScript code
     */
    public function get_client_script(): string {
        $client_url = $this->get_client_report_url();
        $client_options = json_encode([
            'environment' => $this->environment,
            'release' => $this->release,
        ]);

        $user_context = json_encode($this->user_context);

        $code = <<<CODE
            Raven.config({$client_url}, {$client_options}).install();
            Raven.setUserContext($user_context);
CODE;

        return $code;
    }

     /**
     * @inheritDoc
     */
    public function register_reporting_handler(): void {
        $this->client = new \Raven_Client($this->get_internal_report_url(), [
            'sample_rate' => 1,
            'environment' => $this->environment,
            'release' => $this->release,
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
