<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use DOMDocument;

/**
 * Simple wrapper around around DOMDocument->validate().
 */
class SchemaValidator extends AbstractValidator {
    /**
     * Validate a DOM document.
     *
     * @param bool $clearErrors
     * @param mixed $path
     */
    public function validate(DOMDocument $dom, $path, $clearErrors = true) : void {
        if ($clearErrors) {
            $this->clearErrors();
        }
        $xsd = $path . '/native.xsd';
        $oldHandler = set_error_handler([$this, 'validationError']);
        $dom->schemaValidate($xsd);
        set_error_handler($oldHandler);
    }
}
