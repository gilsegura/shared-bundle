<?php

declare(strict_types=1);

namespace SharedBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

final class PackagesSharedBundleTest extends WebTestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function test_must_boot_kernel(): void
    {
        self::bootKernel();
    }

    #[\Override]
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('test', true);
    }
}
