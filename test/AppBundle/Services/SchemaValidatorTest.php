<?php

namespace AppBundle\Services;

use AppBundle\Utility\AbstractTestCase;
use DOMDocument;

class SchemaValidatorTest extends AbstractTestCase {
	
	/**
	 * @var SchemaValidator
	 */
	protected $validator;
	
	public function setUp() {
		parent::setUp();
		$this->validator = $this->getContainer()->get('schemavalidator');
		$this->validator->clearErrors();
	}
	
	public function testInstance() {
		$this->assertInstanceOf('AppBundle\Services\SchemaValidator', $this->validator);
	}
	
	public function testValidate() {
		$dom = new DOMDocument();
		$dom->loadXML($this->getValidXml());
		$path = dirname(__FILE__, 3) . '/data';
		$this->validator->validate($dom, $path, true);
		$this->assertEquals(0, $this->validator->countErrors());
	}
	
	public function testValidateWithErrors() {
		$dom = new DOMDocument();
		$dom->loadXML($this->getinvalidXml());
        $path = dirname(__FILE__, 3) . '/data';
        $this->validator->validate($dom, $path, true);
		$this->assertEquals(1, $this->validator->countErrors());
	}
	
	private function getValidXml() {
		$str = <<<ENDSTR
<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation='testSchema.xsd'>
 <item>String 1</item>
 <item>String 2</item>
 <item>String 3</item>
</root>
ENDSTR;
		return $str;
	}

	private function getInvalidXml() {
		$str = <<<ENDSTR
<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation='testSchema.xsd'>
      <items>
         <item>String 1</item>
         <item>String 2</item>
         <item>String 3</item>
     </items>
</root>
ENDSTR;
		return $str;
	}
}