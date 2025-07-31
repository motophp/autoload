<?php
declare(strict_types=1);

namespace Moto\Autoload;

use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    protected string $namespace = 'Foo\\Bar';

    protected string $directory;

    protected Loader $loader;

    protected function setUp() : void
    {
        $this->loader = new Loader();

        $this->loader->add(
            'Moto\\Autoload\\',
            __DIR__ . DIRECTORY_SEPARATOR,
        );
    }

    public function testResolve_toString() : void
    {
        $expect = __DIR__ . DIRECTORY_SEPARATOR
            . 'Fake' . DIRECTORY_SEPARATOR
            . 'Foo_Bar' . DIRECTORY_SEPARATOR
            . 'Baz' . DIRECTORY_SEPARATOR
            . 'Dib.php';

        $actual = $this->loader->resolve('Moto\\Autoload\\Fake\\Foo_Bar\\Baz\\Dib');
        $this->assertSame($expect, $actual);

        $actual = $this->loader->resolve('Moto\\Autoload\\Fake\\Foo_Bar\\Baz\Dib_Zim');
        $this->assertSame($expect, $actual);

        $actual = $this->loader->resolve('Moto\\Autoload\\Fake\\Foo_Bar\\Baz\\Dib_Gir');
        $this->assertSame($expect, $actual);

        $actual = $this->loader->resolve('Moto\\Autoload\\Fake\\Foo_Bar\\Baz\\Dib_Irk');
        $this->assertSame($expect, $actual);
    }

    public function testResolve_toNull() : void
    {
        // incorrect namespace prefix
        $actual = $this->loader->resolve('Moto\\');
        $this->assertNull($actual);

        // correct namespace prefix, file does not exist
        $actual = $this->loader->resolve('Moto\\Autoload\\Fake\\NoSuchClass');
        $this->assertNull($actual);

        // correct namespace prefix, file exists, but has an underscore in the middle
        $actual = $this->loader->resolve('Moto\\Autoload\\Fake\\Foo_Bar\Baz\\Gaz_Doom');
        $this->assertNull($actual);

        // correct namespace prefix, file exists, but has an underscore at the beginning
        $actual = $this->loader->resolve('Moto\\Autoload\\Fake\\Foo_Bar\Baz\\_Gir');
        $this->assertNull($actual);
    }

    public function testAdd_NammespaceDoesNotEndWithSeparator() : void
    {
        $this->expectException(Loader_Exception::class);

        $this->expectExceptionMessage(
            'Expected a namespace prefix ending with a namespace separator, or an empty string; got \'Foo\\Bar\' instead.'
        );

        $this->loader->add('Foo\\Bar', '/path/to/dir/');
    }

    public function testAdd_DirectoryDoesNotEndWithSeparator() : void
    {
        $this->expectException(Loader_Exception::class);

        $this->expectExceptionMessage(
            'Expected a directory prefix ending with with a directory separator; got \'/path/to/dir\' instead.'
        );

        $this->loader->add('Foo\\Bar\\', '/path/to/dir');
    }
}
