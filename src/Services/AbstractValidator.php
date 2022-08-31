<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use DOMDocument;

abstract class AbstractValidator {
    protected array $errors;

    /**
     * Construct a validator.
     */
    public function __construct() {
        $this->errors = [];
    }

    /**
     * Callback for a validation or parsing error.
     */
    public function validationError(int $n, string $message, string $file, int $line) : void {
        $lxml = libxml_get_last_error();

        if ($lxml) {
            $this->errors[] = [
                'message' => $lxml->message,
                'file' => $lxml->file,
                'line' => $lxml->line,
            ];
        } else {
            $this->errors[] = [
                'message' => $message,
                'file' => $file,
                'line' => $line,
            ];
        }
    }

    abstract public function validate(DOMDocument $dom, string $path, bool $clearErrors = true): void;

    /**
     * Return true if the document had errors.
     */
    public function hasErrors(): bool {
        return count($this->errors) > 0;
    }

    /**
     * Count the errors in validation.
     */
    public function countErrors(): int {
        return count($this->errors);
    }

    /**
     * Get a list of the errors.
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Clear out the errors and start fresh.
     */
    public function clearErrors() : void {
        $this->errors = [];
    }
}
