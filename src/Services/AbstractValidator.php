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
    /**
     * @var array
     */
    protected $errors;

    /**
     * Construct a validator.
     */
    public function __construct() {
        $this->errors = [];
    }

    /**
     * Callback for a validation or parsing error.
     *
     * @param string $n
     * @param string $message
     * @param string $file
     * @param string $line
     * @param string $context
     */
    public function validationError($n, $message, $file, $line, $context) : void {
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

    abstract public function validate(DOMDocument $dom, $path, $clearErrors = true);

    /**
     * Return true if the document had errors.
     *
     * @return bool
     */
    public function hasErrors() {
        return count($this->errors) > 0;
    }

    /**
     * Count the errors in validation.
     *
     * @return int
     */
    public function countErrors() {
        return count($this->errors);
    }

    /**
     * Get a list of the errors.
     *
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Clear out the errors and start fresh.
     */
    public function clearErrors() : void {
        $this->errors = [];
    }
}
