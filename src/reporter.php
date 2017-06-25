<?php

declare(strict_types=1);

namespace betterphp\error_reporting;

abstract class reporter {

    protected $show_errors = false;
    protected $redirect_url = '';
    protected $environment = 'unknown';

    private static $instance = null;

    /**
     * Creates a new reporter instance
     */
    public function __construct() {
        // Want to log or display all errors
        error_reporting(E_ALL);

        // These need to be set in order for errors to cbe correctly captured
        ini_set('display_errors', 'On');
        ini_set('html_errors', 'Off');
    }

    /**
     * Gets the instance of the current error reporter or creates a new one
     *
     * @return reporter The reporter
     */
    public static function get(): reporter {
        $class_name = get_called_class();

        if (self::$instance === null) {
            self::$instance = new $class_name();
        }

        return self::$instance;
    }

    /**
     * Sets if error message should be shown to the uset or they should be redirected to a friendly page
     *
     * @param boolean $show_errors If errors should be shown
     *
     * @return void
     */
    public function set_show_errors(bool $show_errors): void {
        $this->show_errors = $show_errors;
    }

    /**
     * Sets the URL that users should be redirected to if an error is thrown
     *
     * @param string $url The URL to redirect to
     *
     * @return void
     */
    public function set_redirect_url(string $url): void {
        $this->redirect_url = $url;
    }

    /**
     * Sets the name of the environment that the error happened in
     *
     * @param string $environment The name of the environment
     *
     * @return void
     */
    public function set_environment(string $environment): void {
        $this->environment = $environment;
    }

    /**
     * Wraps the die() function to make tests easier
     *
     * @return void
     */
    private function terminate(): void {
        die();
    }

    /**
     * Used to end and discard any existing output buffers
     *
     * @return void
     */
    private function clear_all_output(): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Redirect the browser to the configured error page URL
     *
     * @return void
     */
    private function redirect_to_error_url(): void {
        // Don't do anything if the error has been suppressed
        if (error_reporting() === 0) {
            return;
        }

        // Clear any existing output so that we can do a redirect or show the message cleanly.
        $this->clear_all_output();

        // Don't redirect to nowhere
        if ($this->redirect_url === '') {
            echo 'Internal error';
        } else {
            header('Location: ' . $this->redirect_url);
        }

        $this->terminate();
    }

    /**
     * Sets up error handlers to redirect the user to a friendly URL
     *
     * @return void
     */
    public function register_redirect_handler(): void {
        set_error_handler(function (int $err_no, string $err_message, string $err_file, int $err_line): void {
            $this->redirect_to_error_url();
        });

        // Strange nesting is to make sure this handler gets called last
        register_shutdown_function(function (): void {
            register_shutdown_function(function (): void {
                $last_error = error_get_last();

                if ($last_error !== null) {
                    $this->redirect_to_error_url();
                }
            });
        });
    }

    /**
     * Outputs an error messages in a vaguely formatted way
     *
     * @param string $message The message to output
     *
     * @return void
     */
    private function show_error(string $message): void {
        // Don't do anything if the error has been suppressed
        if (error_reporting() === 0) {
            return;
        }

        // Clear any existing output so that we can do a redirect or show the message cleanly.
        $this->clear_all_output();

        echo '<pre>', htmlentities($message), '</pre>';

        $this->terminate();
    }

    /**
     * Formats a friendly message from error info
     *
     * @param integer $err_no The value of the E_ constant
     * @param string $err_message The error message
     * @param string $err_file The file that the error occured in
     * @param integer $err_line The line that it occured on
     *
     * @return string A formatted message
     */
    private function get_error_message(int $err_no, string $err_message, string $err_file, int $err_line): string {
        $error_constants = [];

        foreach (get_defined_constants(true)['Core'] as $name => $value) {
            if (substr($name, 0, 2) === 'E_') {
                $error_constants[$value] = $name;
            }
        }

        $level = ($error_constants[$err_no] ?? 'E_UNKNOWN');

        return "{$level}: {$err_message} in {$err_file} on line {$err_line}";
    }

    /**
     * Sets up an error handler to clear all output and display the error message
     *
     * @return void
     */
    public function register_output_handler(): void {
        set_exception_handler(function (\Throwable $exception) {
            $this->show_error($exception->getMessage() . "\n" . $exception->getTraceAsString());
        });

        set_error_handler(function (int $err_no, string $err_message, string $err_file, int $err_line): void {
            $this->show_error($this->get_error_message($err_no, $err_message, $err_file, $err_line));
        });

        // Strange nesting is to make sure this handler gets called last
        register_shutdown_function(function (): void {
            register_shutdown_function(function (): void {
                $last_error = error_get_last();

                if ($last_error !== null) {
                    $this->show_error($this->get_error_message(
                        $last_error['type'],
                        $last_error['message'],
                        $last_error['file'],
                        $last_error['line']
                    ));
                }
            });
        });
    }

    /**
     * Sets up any handlers for reporting of errors to the provider
     *
     * @return void
     */
    abstract public function register_reporting_handler(): void;

}
