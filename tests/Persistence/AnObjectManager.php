<?php

declare(strict_types=1);

namespace SharedBundle\Tests\Persistence;

use Shared\Domain\DomainMessage;
use SharedBundle\Persistence\Doctrine\AbstractObjectManager;
use SharedBundle\Persistence\Doctrine\Attribute\ObjectManager;

/**
 * @template-extends AbstractObjectManager<int, DomainMessage>
 */
#[ObjectManager(DomainMessage::class)]
final readonly class AnObjectManager extends AbstractObjectManager
{
}
