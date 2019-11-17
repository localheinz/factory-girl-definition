<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/localheinz/factory-girl-definition
 */

namespace Localheinz\FactoryGirl\Definition\Test\Unit;

use FactoryGirl\Provider\Doctrine\FixtureFactory;
use Faker\Generator;
use Localheinz\FactoryGirl\Definition\Definition;
use Localheinz\FactoryGirl\Definition\Definitions;
use Localheinz\FactoryGirl\Definition\Exception;
use Localheinz\FactoryGirl\Definition\FakerAwareDefinition;
use Localheinz\FactoryGirl\Definition\Test\Fixture;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 *
 * @covers \Localheinz\FactoryGirl\Definition\Definitions
 *
 * @uses \Localheinz\FactoryGirl\Definition\Exception\InvalidDefinition
 * @uses \Localheinz\FactoryGirl\Definition\Exception\InvalidDirectory
 */
final class DefinitionsTest extends Framework\TestCase
{
    use Helper;

    public function testInRejectsNonExistentDirectory(): void
    {
        $this->expectException(Exception\InvalidDirectory::class);

        Definitions::in(__DIR__ . '/../Fixture/Definition/NonExistentDirectory');
    }

    public function testInIgnoresClassesWhichDoNotImplementProviderInterface(): void
    {
        $fixtureFactory = $this->prophesize(FixtureFactory::class);

        $fixtureFactory
            ->defineEntity()
            ->shouldNotBeCalled();

        Definitions::in(__DIR__ . '/../Fixture/Definition/DoesNotImplementInterface')->registerWith($fixtureFactory->reveal());
    }

    public function testInIgnoresClassesWhichAreAbstract(): void
    {
        $fixtureFactory = $this->prophesize(FixtureFactory::class);

        $fixtureFactory
            ->defineEntity()
            ->shouldNotBeCalled();

        Definitions::in(__DIR__ . '/../Fixture/Definition/IsAbstract')->registerWith($fixtureFactory->reveal());
    }

    public function testInIgnoresClassesWhichHavePrivateConstructors(): void
    {
        $fixtureFactory = $this->prophesize(FixtureFactory::class);

        $fixtureFactory
            ->defineEntity()
            ->shouldNotBeCalled();

        Definitions::in(__DIR__ . '/../Fixture/Definition/PrivateConstructor')->registerWith($fixtureFactory->reveal());
    }

    public function testInAcceptsClassesWhichAreAcceptable(): void
    {
        $fixtureFactory = $this->prophesize(FixtureFactory::class);

        $fixtureFactory
            ->defineEntity(Fixture\Entity\User::class)
            ->shouldBeCalled();

        Definitions::in(__DIR__ . '/../Fixture/Definition/Acceptable')->registerWith($fixtureFactory->reveal());
    }

    public function testFluentInterface(): void
    {
        $definitions = Definitions::in(__DIR__ . '/../Fixture/Definition/Acceptable');

        self::assertInstanceOf(Definitions::class, $definitions);

        self::assertSame($definitions, $definitions->registerWith($this->prophesize(FixtureFactory::class)->reveal()));
        self::assertSame($definitions, $definitions->provideWith($this->prophesize(Generator::class)->reveal()));
    }

    public function testInAcceptsClassesWhichAreAcceptableAndFakerAwareAndProvidesThemWithFaker(): void
    {
        $faker = $this->prophesize(Generator::class);

        $definitions = Definitions::in(__DIR__ . '/../Fixture/Definition/FakerAware')->provideWith($faker->reveal());

        $reflection = new \ReflectionClass(Definitions::class);

        $property = $reflection->getProperty('definitions');

        $property->setAccessible(true);

        $definitions = $property->getValue($definitions);

        self::assertIsArray($definitions);

        $fakerAwareDefinitions = \array_filter($definitions, static function (Definition $definition) {
            return $definition instanceof FakerAwareDefinition;
        });

        self::assertCount(1, $fakerAwareDefinitions);
        self::assertContainsOnlyInstancesOf(FakerAwareDefinition::class, $fakerAwareDefinitions);

        /** @var Fixture\Definition\FakerAware\GroupDefinition $fakerAwareDefinition */
        $fakerAwareDefinition = \array_shift($fakerAwareDefinitions);

        self::assertInstanceOf(Fixture\Definition\FakerAware\GroupDefinition::class, $fakerAwareDefinition);
        self::assertSame($faker->reveal(), $fakerAwareDefinition->faker());
    }

    public function testThrowsInvalidDefinitionExceptionIfInstantiatingDefinitionsThrowsException(): void
    {
        $this->expectException(Exception\InvalidDefinition::class);

        Definitions::in(__DIR__ . '/../Fixture/Definition/ThrowsExceptionDuringConstruction');
    }
}
