<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\Services\SchemaValidator;
use DOMDocument;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class SchemaValidatorTest extends ControllerBaseCase {
    /**
     * @var SchemaValidator
     */
    protected $validator;

    private function getValidXml() {
        return <<<'ENDSTR'
<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation='testSchema.xsd'>
 <item>String 1</item>
 <item>String 2</item>
 <item>String 3</item>
</root>
ENDSTR;
    }

    private function getInvalidXml() {
        return <<<'ENDSTR'
<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation='testSchema.xsd'>
      <items>
         <item>String 1</item>
         <item>String 2</item>
         <item>String 3</item>
     </items>
</root>
ENDSTR;
    }

    public function testInstance() : void {
        $this->assertInstanceOf(SchemaValidator::class, $this->validator);
    }

    public function testValidate() : void {
        $dom = new DOMDocument();
        $dom->loadXML($this->getValidXml());
        $path = dirname(__FILE__, 2) . '/data';
        $this->validator->validate($dom, $path, true);
        $this->assertSame(0, $this->validator->countErrors());
    }

    public function testValidateWithErrors() : void {
        $dom = new DOMDocument();
        $dom->loadXML($this->getinvalidXml());
        $path = dirname(__FILE__, 2) . '/data';
        $this->validator->validate($dom, $path, true);
        $this->assertSame(1, $this->validator->countErrors());
    }

    protected function setUp() : void {
        parent::setUp();
        $this->validator = self::$container->get(SchemaValidator::class);
        $this->validator->clearErrors();
    }
}
