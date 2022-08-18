<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\Services\DtdValidator;
use DOMDocument;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class DtdValidatorTest extends ControllerBaseCase {
    /**
     * @var DtdValidator
     */
    protected $validator;

    private function getValidXml() {
        return <<<'ENDSTR'
<?xml version="1.0" standalone="yes"?>
<!DOCTYPE root [
<!ELEMENT root (item)+ >
<!ELEMENT item EMPTY >
<!ATTLIST item type CDATA #REQUIRED>
]>
<root>
	<item type="foo"/>
	<item type="bar"/>
</root>
ENDSTR;
    }

    private function getInvalidXml() {
        return <<<'ENDSTR'
<?xml version="1.0" standalone="yes"?>
<!DOCTYPE root [
<!ELEMENT root (item)+ >
<!ELEMENT item EMPTY >
<!ATTLIST item type CDATA #REQUIRED>
]>
<root>
	<item/>
	<item type="bar"/>
</root>
ENDSTR;
    }

    public function testInstance() : void {
        $this->assertInstanceOf(DtdValidator::class, $this->validator);
    }

    public function testValidateNoDtd() : void {
        $dom = new DOMDocument();
        $dom->loadXML('<root />');
        $this->validator->validate($dom);
        $this->assertSame(0, $this->validator->countErrors());
    }

    public function testValidate() : void {
        $dom = new DOMDocument();
        $dom->loadXML($this->getValidXml());
        $this->validator->validate($dom, true);
        $this->assertFalse($this->validator->hasErrors());
        $this->assertSame(0, $this->validator->countErrors());
    }

    public function testValidateWithErrors() : void {
        $dom = new DOMDocument();
        $dom->loadXML($this->getinvalidXml());
        $this->validator->validate($dom, true);
        $this->assertTrue($this->validator->hasErrors());
        $this->assertSame(1, $this->validator->countErrors());
    }

    protected function setup() : void {
        parent::setUp();
        $this->validator = self::$container->get(DtdValidator::class);
        $this->validator->clearErrors();
    }
}
