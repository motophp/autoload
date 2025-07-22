<?php
declare(strict_types=1);

namespace Moto\Autoload;

use PHPUnit\Framework\TestCase;

class ResolverTest extends TestCase
{
    protected string $namespace = 'Foo\\Bar';

    protected string $directory;

    protected Resolver $resolver;

    protected function setUp() : void
    {
        $this->resolver = new Resolver([
            'Moto\\Autoload\\' => __DIR__ . DIRECTORY_SEPARATOR,
        ]);
    }

    public function testResolve_toString() : void
    {
        $expect = __DIR__ . DIRECTORY_SEPARATOR
            . 'Fake' . DIRECTORY_SEPARATOR
            . 'Foo_Bar' . DIRECTORY_SEPARATOR
            . 'Baz' . DIRECTORY_SEPARATOR
            . 'Dib.php';

        $actual = $this->resolver->resolve('Moto\\Autoload\\Fake\\Foo_Bar\\Baz\\Dib');
        $this->assertEquals($expect, $actual);

        $actual = $this->resolver->resolve('Moto\\Autoload\\Fake\\Foo_Bar\\Baz\Dib_Zim');
        $this->assertEquals($expect, $actual);

        $actual = $this->resolver->resolve('Moto\\Autoload\\Fake\\Foo_Bar\\Baz\\Dib_Gir');
        $this->assertEquals($expect, $actual);

        $actual = $this->resolver->resolve('Moto\\Autoload\\Fake\\Foo_Bar\\Baz\\Dib_Irk');
        $this->assertEquals($expect, $actual);
    }

    public function testResolve_toNull() : void
    {
        // incorrect namespace prefix
        $actual = $this->resolver->resolve('Moto\\');
        $this->assertNull($actual);

        // correct namespace prefix, file does not exist
        $actual = $this->resolver->resolve('Moto\\Autoload\\Fake\\NoSuchClass');
        $this->assertNull($actual);

        // correct namespace prefix, file exists, but has an underscore in the middle
        $actual = $this->resolver->resolve('Moto\\Autoload\\Fake\\Foo_Bar\Baz\\Gaz_Doom');
        $this->assertNull($actual);

        // correct namespace prefix, file exists, but has an underscore at the beginning
        $actual = $this->resolver->resolve('Moto\\Autoload\\Fake\\Foo_Bar\Baz\\_Gir');
        $this->assertNull($actual);
    }
}
