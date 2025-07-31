<?php
declare(strict_types=1);

namespace Moto\Autoload;

use RuntimeException;
use Throwable;

/**
 * @phpstan-type namespace_prefix_string string
 * @phpstan-type directory_prefix_string string
 */
class Loader
{
    static protected Loader $singleton;

    /**
     * @param array<namespace_prefix_string,directory_prefix_string> $namespaceDirectory
     */
    public static function register(array $namespaceDirectory) : void
    {
        if (! isset(self::$singleton)) {
            self::$singleton = new self();
            spl_autoload_register(self::$singleton);
        }

        foreach ($namespaceDirectory as $namespace => $directory) {
            self::$singleton->add($namespace, $directory);
        }
    }

    /**
     * @var array<namespace_prefix_string,directory_prefix_string[]>
     */
    protected array $namespaceDirectories = [];

    protected string $filenameSuffix = '.php';

    public function __invoke(string $fullyQualifiedName) : void
    {
        $absoluteFilePath = $this->resolve($fullyQualifiedName);

        if ($absoluteFilePath) {
            require_once $absoluteFilePath;
        }
    }

    /**
     * @param namespace_prefix_string $namespace
     * @param directory_prefix_string $directory
     */
    public function add(string $namespace, string $directory) : void
    {
        $namespace = trim($namespace);

        if ($namespace !== '' && ! str_ends_with($namespace, '\\')) {
            throw new Loader_Exception(
                <<<MESSAGE
                Expected a namespace prefix ending with a namespace
                separator, or an empty string; got '$namespace' instead.
                MESSAGE,
            );
        }

        $osSpecificDirectory = str_replace('/', DIRECTORY_SEPARATOR, $directory);

        if (! str_ends_with($osSpecificDirectory, DIRECTORY_SEPARATOR)) {
            throw new Loader_Exception(
                <<<MESSAGE
                Expected a directory prefix ending with with a directory
                separator; got '$directory' instead.
                MESSAGE,
            );
        }

        $this->namespaceDirectories[$namespace][] = $osSpecificDirectory;
    }

    public function resolve(string $fullyQualifiedName) : ?string
    {
        foreach ($this->namespaceDirectories as $namespace => $directories) {
            foreach ($directories as $directory) {
                $absoluteFilePath = $this->resolutionAlgorithm(
                    $namespace,
                    $directory,
                    $fullyQualifiedName
                );

                if ($absoluteFilePath) {
                    return $absoluteFilePath;
                }
            }
        }

        return null;
    }

    /**
     * @param namespace_prefix_string $namespacePrefix
     * @param directory_prefix_string $directoryPrefix
     */
    protected function resolutionAlgorithm(
        string $namespacePrefix,
        string $directoryPrefix,
        string $fullyQualifiedName
    ) : ?string
    {
        // 1. If the `fully-qualified-name` does not begin with the
        // `namespace-prefix`, the `fully-qualified-name` resolves to `null`.
        if (! str_starts_with($fullyQualifiedName, $namespacePrefix)) {
            return null;
        }

        // 2. Otherwise, set the `qualified-name` by capturing all characters
        // after the `namespace-prefix`.
        $qualifiedName = substr($fullyQualifiedName, strlen($namespacePrefix));

        // 3. If the `qualified-name` contains a namespace separator ...
        $namespaceSeparator = strrpos($qualifiedName, '\\');

        if ($namespaceSeparator !== false) {
            // 3A. Set the `partial-namespace` by capturing all characters up to
            // and including the rightmost namespace separator, and set the
            // `partial-name` by capturing all characters after the rightmost
            // namespace separator.
            $partialNamespace = substr($qualifiedName, 0, $namespaceSeparator + 1);
            $partialName = substr($qualifiedName, $namespaceSeparator + 1);
        } else {
            // 3B. Otherwise, set the `partial-namespace` to an empty string,
            // and set the `partial-name` to the `qualified-name`.
            $partialNamespace = '';
            $partialName = $qualifiedName;
        }

        // 4. If the `partial-name` contains an underscore, remove the first
        // underscore and all characters thereafter.
        $underscore = strpos($partialName, '_');

        if ($underscore !== false) {
            $partialName = substr($partialName, 0, $underscore);
        }

        // 5. If the `partial-name` is empty, the `fully-qualified-name`
        // resolves to `null`.
        if (! $partialName) {
            return null;
        }

        // 6. Otherwise, set the `partial-directory` by converting each
        // namespace separator in the `partial-namespace` to a
        // `DIRECTORY_SEPARATOR`.
        $partialDirectory = str_replace('\\', DIRECTORY_SEPARATOR, $partialNamespace);

        // 7. Set the `absolute-file-path` by concatenating the
        // `directory-prefix`, `partial-directory`, `partial-name`, and a
        // `filename-suffix` (typically `.php`).
        $absoluteFilePath = $directoryPrefix
            . $partialDirectory
            . $partialName
            . $this->filenameSuffix;

        // 8. If the `absolute-file-path` exists, the `fully-qualified-name`
        // resolves to the `absolute-file-path`; otherwise, it resolves to
        // `null`.
        return file_exists($absoluteFilePath) ? $absoluteFilePath : null;
    }
}

class Loader_Exception extends RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $message = str_replace(["\r\n", "\r", "\n"], " ", $message);
        parent::__construct($message, $code, $previous);
    }
}
