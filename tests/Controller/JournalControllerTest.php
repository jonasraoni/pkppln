<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller;

use App\DataFixtures\JournalFixtures;
use Nines\UserBundle\DataFixtures\UserFixtures;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class JournalControllerTest extends ControllerBaseCase {
    protected function fixtures() : array {
        return [
            UserFixtures::class,
            JournalFixtures::class,
        ];
    }

    public function testAnonIndex() : void {

        $crawler = $this->client->request('GET', '/journal/');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserIndex() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/journal/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminIndex() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/journal/');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAnonShow() : void {

        $crawler = $this->client->request('GET', '/journal/1');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
    }

    public function testUserShow() : void {
        $this->login('user.user');
        $crawler = $this->client->request('GET', '/journal/1');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminShow() : void {
        $this->login('user.admin');
        $crawler = $this->client->request('GET', '/journal/1');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAnonSearch() : void {

        $formCrawler = $this->client->request('GET', '/journal/search');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    public function testUserSearch() : void {
        $this->login('user.user');
        $formCrawler = $this->client->request('GET', '/journal/search');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $form = $formCrawler->selectButton('Search')->form([
            'q' => '5D69',
        ]);
        $this->client->submit($form);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame(1, $this->client->getCrawler()->filter('td:contains("CBF45637-5D69-44C3-AEC0-A906CBC3E27B")')->count());
    }

    public function testAdminSearch() : void {
        $this->login('user.admin');
        $formCrawler = $this->client->request('GET', '/journal/search');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $form = $formCrawler->selectButton('Search')->form([
            'q' => '5D69',
        ]);
        $this->client->submit($form);
        $this->assertSame(200, $this->client->getresponse()->getStatusCode());
        $this->assertSame(1, $this->client->getCrawler()->filter('td:contains("CBF45637-5D69-44C3-AEC0-A906CBC3E27B")')->count());
    }

    public function testAnonPing() : void {

        $this->client->request('GET', '/journal/1/ping');
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    public function testUserPing() : void {
        $this->login('user.user');
        $this->client->request('GET', '/journal/1/ping');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminPing() : void {
        $this->login('user.admin');
        $this->client->request('GET', '/journal/1/ping');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }
}
