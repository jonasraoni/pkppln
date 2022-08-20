<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\TestCase;

use Exception;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Nines\UtilBundle\TestCase\ControllerTestCase;
use SplFileInfo;

/**
 * Description of AbstractProcessingCmdTest.
 *
 * @author michael
 */
class BaseControllerTestCase extends ControllerTestCase {
    use FixturesTrait;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ReferenceRepository
     */
    protected $references;

    /**
     * @var array|SplFileInfo[]|string[]
     */
    protected $cleanup = [];

    /**
     * Get a list of fixture classes to load.
     */
    protected function fixtures() : array {
        return [];
    }

    /**
     * Get one data fixture. If $reload is true, the fixture will
     * be fetched from the database.
     *
     * @param bool $reload
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     *
     * @return null|object
     */
    protected function getReference(string $id, $reload = false) {
        if ( ! $this->references->hasReference($id)) {
            return;
        }
        $object = $this->references->getReference($id);
        if ( ! $reload) {
            return $object;
        }

        return $this->entityManager->find($object::class, $object->getId());
    }

    protected function cleanup($files) : void {
        if ( ! is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo) {
                $this->cleanup[] = $file->getRealPath();
            } else {
                if (is_string($file)) {
                    $this->cleanup[] = $file;
                } else {
                    throw new Exception('Cannot clean up ' . $file::class);
                }
            }
        }
    }

    /**
     * Set up the container and fixtures.
     */
    protected function setUp() : void {
        parent::setUp();
        self::bootKernel();
        $this->references = $this->loadFixtures($this->fixtures())->getReferenceRepository();
        $this->cleanup = [];
    }

    /**
     * Clear out the memory for the next test run.
     *
     * @throws MappingException
     */
    protected function tearDown() : void {
        parent::tearDown();

        foreach ($this->cleanup as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
