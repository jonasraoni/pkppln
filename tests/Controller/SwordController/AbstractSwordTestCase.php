<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller\SwordController;

use App\DataFixtures\BlacklistFixtures;
use App\DataFixtures\DepositFixtures;
use App\DataFixtures\JournalFixtures;
use App\DataFixtures\TermOfUseFixtures;
use App\DataFixtures\WhitelistFixtures;
use App\Utilities\Namespaces;
use Exception;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use SimpleXMLElement;
use Symfony\Component\BrowserKit\Client;

abstract class AbstractSwordTestCase extends ControllerBaseCase {

    protected function getXml() {
        $xml = new SimpleXMLElement($this->client->getResponse()->getContent());
        Namespaces::registerNamespaces($xml);

        return $xml;
    }

    public function fixtures() : array {
        return [
            JournalFixtures::class,
            DepositFixtures::class,
            TermOfUseFixtures::class,
            WhitelistFixtures::class,
            BlacklistFixtures::class,
        ];
    }

    /**
     * Get a single XML value as a string.
     *
     * @param type $xpath
     *
     * @throws Exception
     *
     * @return string
     */
    public function getXmlValue(SimpleXMLElement $xml, $xpath) {
        $data = $xml->xpath($xpath);
        if (1 === count($data)) {
            return trim((string) $data[0]);
        }
        if (0 === count($data)) {
            return;
        }

        throw new Exception("Too many elements for '{$xpath}'");
    }

    protected function setUp() : void {
        parent::setUp();
    }
}
