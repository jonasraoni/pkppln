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
class DtdValidator extends AbstractValidator {
    /**
     * Validate a DOM document.
     *
     * @param null $path
     * @param bool $clearErrors
     */
    public function validate(DOMDocument $dom, $path = null, $clearErrors = true) : void {
        if ($clearErrors) {
            $this->clearErrors();
        }
        if (null === $dom->doctype) {
            return;
        }
        $oldHandler = set_error_handler([$this, 'validationError']);
        $dom->validate();
        set_error_handler($oldHandler);
    }
}
