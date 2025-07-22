<?php
declare(strict_types=1);

namespace Moto\Autoload;

class Loader
{
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
