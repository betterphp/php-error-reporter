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
     * Sets up error handlers to redirect the user to a friendly URL
     *
     * @return void
     */
    public function register_redirect_handler(): void {
        $redirect_to_error_url = function (): void {
            // Don't do anything if the error has been suppressed
            if (error_reporting() === 0) {
                return;
            }

            // Clear any existing output so that we can do a redirect or show the message cleanly.
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Don't redirect to nowhere
            if ($this->redirect_url === '') {
                echo 'Internal error';
            } else {
                header('Location: ' . $this->redirect_url);
            }

            die();
        };

        set_error_handler(function (
            int $err_no,
            string $err_message,
            string $err_file,
            int $err_line
        ) use ($redirect_to_error_url): void {
            $redirect_to_error_url();
        });

        // Strange nesting is to make sure this handler gets called last
        register_shutdown_function(function () use ($redirect_to_error_url): void {
            register_shutdown_function(function () use ($redirect_to_error_url): void {
                $last_error = error_get_last();

                if ($last_error !== null) {
                    $redirect_to_error_url();
                }
            });
        });
    }

    /**
     * Sets up an error handler to clear all output and display the error message
     *
     * @return void
     */
    public function register_output_handler(): void {
        $show_error = function (string $message): void {
            // Don't do anything if the error has been suppressed
            if (error_reporting() === 0) {
                return;
            }

            // Clear any existing output so that we can do a redirect or show the message cleanly.
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            echo '<pre>', htmlentities($message), '</pre>';

            die();
        };

        $get_error_message = function (int $err_no, string $err_message, string $err_file, int $err_line): string {
            $error_constants = [];

            foreach (get_defined_constants(true)['Core'] as $name => $value) {
                if (substr($name, 0, 2) === 'E_') {
                    $error_constants[$value] = $name;
                }
            }

            $level = ($error_constants[$err_no] ?? 'E_UNKNOWN');

            return "{$level}: {$err_message} in {$err_file} on line {$err_line}";
        };

        set_exception_handler(function (\Throwable $exception) use ($show_error) {
            $show_error($exception->getMessage() . "\n" . $exception->getTraceAsString());
        });

        set_error_handler(function (
            int $err_no,
            string $err_message,
            string $err_file,
            int $err_line
        ) use (
            $show_error,
            $get_error_message
        ): void {
            $show_error($get_error_message($err_no, $err_message, $err_file, $err_line));
        });

        // Strange nesting is to make sure this handler gets called last
        register_shutdown_function(function () use ($show_error, $get_error_message): void {
            register_shutdown_function(function () use ($show_error, $get_error_message): void {
                $last_error = error_get_last();

                if ($last_error !== null) {
                    $show_error($get_error_message(
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
