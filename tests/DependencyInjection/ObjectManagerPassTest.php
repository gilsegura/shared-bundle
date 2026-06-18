<?php

declare(strict_types=1);

namespace SharedBundle\Tests\DependencyInjection;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Shared\Domain\DomainMessage;
use SharedBundle\DependencyInjection\ObjectManagerPass;
use SharedBundle\SharedBundle;
use SharedBundle\Tests\Persistence\AnObjectManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class ObjectManagerPassTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
    }

    private function compile(): void
    {
        $this->container->addCompilerPass(new ObjectManagerPass());
        $this->container->compile();
    }

    public function test_must_throw_when_service_does_not_extend_abstract_object_manager(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $definition = new Definition(\stdClass::class);
        $definition->setPublic(true);
        $definition->addTag(SharedBundle::OBJECT_MANAGER_TAG);

        $this->container->setDefinition('object_manager', $definition);

        $this->compile();
    }

    public function test_must_inject_entity_manager_and_entity_from_the_attribute(): void
    {
        // The pass references the entity manager by id; register a stub so the
        // container can resolve it when it compiles (this is a unit test on the
        // pass, not the full Doctrine integration).
        $this->container->setDefinition(
            EntityManagerInterface::class,
            new Definition(EntityManagerInterface::class)->setSynthetic(true),
        );

        $definition = new Definition(AnObjectManager::class);
        $definition->setPublic(true);
        $definition->setAutowired(true);
        $definition->addTag(SharedBundle::OBJECT_MANAGER_TAG);

        $this->container->setDefinition(AnObjectManager::class, $definition);

        $this->compile();

        $wired = $this->container->getDefinition(AnObjectManager::class);

        $arguments = $wired->getArguments();

        // The attribute drives the entity argument and the entity manager is
        // referenced, while autowiring is disabled so neither is re-resolved.
        self::assertFalse($wired->isAutowired());
        self::assertEquals(new Reference(EntityManagerInterface::class), $arguments[0]);
        self::assertSame(DomainMessage::class, $arguments[1]);
    }
}
