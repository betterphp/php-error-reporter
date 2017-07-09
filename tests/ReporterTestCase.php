<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ReporterTestCase extends TestCase {

    protected function captureRegisteredFunction(string $register_name, array &$captured = null) {
        if ($captured === null) {
            $captured = [];
        }

        $this->redefineFunction(
            $register_name,
            function (callable $handler) use (&$captured) {
                $captured[] = $handler;
            }
        );
    }

}
