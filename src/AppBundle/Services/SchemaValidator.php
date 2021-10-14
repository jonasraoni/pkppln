<?php

/*
 * Copyright (C) 2015-2016 Michael Joyce <ubermichael@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AppBundle\Services;

use DOMDocument;

/**
 * Simple wrapper around around DOMDocument->validate().
 */
class SchemaValidator
{
    /**
     * @var array
     */
    private $errors;

    /**
     * Construct a validator.
     */
    public function __construct()
    {
        $this->errors = array();
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
    public function validationError($n, $message, $file, $line, $context)
    {
        $lxml = libxml_get_last_error();

        if ($lxml) {
            $this->errors[] = array(
                'message' => $lxml->message,
                'file' => $lxml->file,
                'line' => $lxml->line,
            );
        } else {
            $this->errors[] = array(
                'message' => $message,
                'file' => $file,
                'line' => $line,
            );
        }
    }

    /**
     * Validate a DOM document.
     *
     * @param DOMDocument $dom
     * @param bool        $clearErrors
     */
    public function validate(DOMDocument $dom, $path, $clearErrors = true)
    {
        if ($clearErrors) {
            $this->clearErrors();
        }
        $xsd = $path . '/native.xsd';
        $oldHandler = set_error_handler([$this, 'validationError']);
        $dom->schemaValidate($xsd);
        set_error_handler($oldHandler);
    }

    /**
     * Return true if the document had errors.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * Count the errors in validation.
     *
     * @return int
     */
    public function countErrors()
    {
        return count($this->errors);
    }

    /**
     * Get a list of the errors.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Clear out the errors and start fresh.
     */
    public function clearErrors()
    {
        $this->errors = array();
    }
}
