<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace EventEngineTest\CodeGenerator\Cody\Metadata;

use EventEngine\CodeGenerator\Cody\Metadata\MetadataFactory;
use EventEngine\InspectioGraph\Metadata\Metadata;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

final class MetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_is_a_callable(): void
    {
        $metadata = $this->prophesize(Metadata::class);

        $cut = new MetadataFactory(fn () => $metadata->reveal());
        $this->assertIsCallable($cut);
    }
}
