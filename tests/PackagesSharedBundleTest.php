<?php

declare(strict_types=1);

namespace SharedBundle\Tests;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

final class PackagesSharedBundleTest extends WebTestCase
{
    #[DoesNotPerformAssertions]
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
