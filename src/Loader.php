<?php
declare(strict_types=1);

namespace Moto\Autoload;

use Moto\Autoload\Fake\Foo_Bar\Baz\Dib;

/**
 * @phpstan-import-type namespace_prefix_string from Resolver
 *
 * @phpstan-import-type directory_prefix_string from Resolver
 */
class Loader
{
    /**
     * @param array<namespace_prefix_string, directory_prefix_string> $namespaceDirectory
     */
    public static function register(array $namespaceDirectory) : void
    {
        require_once __DIR__ . '/Resolver.php';
        $resolver = new Resolver();

        foreach ($namespaceDirectory as $namespace => $directory) {
            $resolver->add($namespace, $directory);
        }

        /** @var callable(string):void $callable */
        $callable = [new Loader($resolver), 'load'];
        spl_autoload_register($callable);
    }

    public function __construct(protected Resolver $resolver)
    {
    }

    public function load(string $fullyQualifiedName) : bool
    {
        $absoluteFilePath = $this->resolver->resolve($fullyQualifiedName);

        if (! $absoluteFilePath) {
            return false;
        }

        require_once $absoluteFilePath;
        return true;
    }
}
