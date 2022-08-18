<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services\Processing;

use App\Entity\Deposit;
use App\Entity\Journal;
use App\Services\FilePaths;
use App\Services\Processing\PayloadValidator;
use Exception;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Description of PayloadValidatorTest.
 */
class PayloadValidatorTest extends ControllerBaseCase {
    /**
     * @var PayloadValidator
     */
    private $validator;

    /**
     * @var vfsStreamDirectory
     */
    private $root;

    public function testInstance() : void {
        $this->assertInstanceOf(PayloadValidator::class, $this->validator);
    }

    /**
     * @dataProvider hashFileData
     *
     * @param mixed $alg
     * @param mixed $name
     * @param mixed $data
     */
    public function testHashFile($alg, $name, $data) : void {
        $file = vfsStream::newFile('deposit.zip')->withContent($data)->at($this->root);
        $this->assertSame(strtoupper(hash($alg, $data)), $this->validator->hashFile($name, $file->url()));
    }

    public function hashFileData() {
        return [
            ['sha1', 'sha-1', 'some data.'],
            ['sha1', 'sha1', 'some data.'],
            ['sha1', 'SHA1', 'some data.'],
            ['md5', 'md5', 'some data.'],
            ['md5', 'MD5', 'some data.'],
        ];
    }

    public function testHashFileException() : void {
        $this->expectException(Exception::class);
        $file = vfsStream::newFile('deposit.zip')->withContent('some data.')->at($this->root);
        $this->validator->hashFile('cheese', $file->url());
    }

    public function testProcessDeposit() : void {
        $file = vfsStream::newFile('deposit.zip')->withContent('some data.')->at($this->root);

        $filePaths = $this->createMock(FilePaths::class);
        $filePaths->method('getHarvestFile')->willReturn($file->url());
        $this->validator->setFilePaths($filePaths);

        $journal = new Journal();
        $journal->setUuid('abc123');

        $deposit = new Deposit();
        $deposit->setJournal($journal);
        $deposit->setChecksumType('sha1');
        $deposit->setChecksumValue(hash('sha1', 'some data.'));

        $result = $this->validator->processDeposit($deposit);
        $this->assertTrue($result);
        $this->assertSame('', $deposit->getProcessingLog());
    }

    public function testProcessDepositChecksumMismatch() : void {
        $file = vfsStream::newFile('deposit.zip')->withContent('some data.')->at($this->root);

        $filePaths = $this->createMock(FilePaths::class);
        $filePaths->method('getHarvestFile')->willReturn($file->url());
        $this->validator->setFilePaths($filePaths);

        $journal = new Journal();
        $journal->setUuid('abc123');

        $deposit = new Deposit();
        $deposit->setJournal($journal);
        $deposit->setChecksumType('sha1');
        $deposit->setChecksumValue(hash('sha1', 'some other different data.'));

        $result = $this->validator->processDeposit($deposit);
        $this->assertFalse($result);
        $this->assertStringContainsStringIgnoringCase('Deposit checksum does not match', $deposit->getProcessingLog());
    }

    public function testProcessDepositChecksumUnknown() : void {
        $file = vfsStream::newFile('deposit.zip')->withContent('some data.')->at($this->root);

        $filePaths = $this->createMock(FilePaths::class);
        $filePaths->method('getHarvestFile')->willReturn($file->url());
        $this->validator->setFilePaths($filePaths);

        $journal = new Journal();
        $journal->setUuid('abc123');

        $deposit = new Deposit();
        $deposit->setJournal($journal);
        $deposit->setChecksumType('cheese');
        $deposit->setChecksumValue(hash('sha1', 'some other different data.'));

        $result = $this->validator->processDeposit($deposit);
        $this->assertFalse($result);
        $this->assertStringContainsStringIgnoringCase('Unknown hash algorithm cheese', $deposit->getProcessingLog());
    }

    protected function setup() : void {
        parent::setUp();
        $this->validator = self::$container->get(PayloadValidator::class);
        $this->root = vfsStream::setup();
    }
}
