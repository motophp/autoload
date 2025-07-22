<?php
declare(strict_types=1);

namespace Moto\Autoload;

/**
 * @phpstan-type namespace_directory_array array<non-empty-string, non-empty-string>
 */
class Resolver
{
    /**
     * @var namespace_directory_array $namespaceDirectory
     */
    protected array $namespaceDirectory;

    /**
     * @param namespace_directory_array $namespaceDirectory
     */
    public function __construct(
        array $namespaceDirectory,
        protected string $filenameSuffix = '.php'
    ) {
        foreach ($namespaceDirectory as $namespace => $directory) {
            $namespace = rtrim($namespace, '\\') . '\\';
            $directory = DIRECTORY_SEPARATOR . ltrim($directory, DIRECTORY_SEPARATOR);
            $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $this->namespaceDirectory[$namespace] = $directory;
        }
    }

    public function resolve(string $fullyQualifiedName) : ?string
    {
        foreach ($this->namespaceDirectory as $namespace => $directory) {
            $absoluteFilePath = $this->resolutionAlgorithm(
                $namespace,
                $directory,
                $fullyQualifiedName
            );

            if ($absoluteFilePath) {
                return $absoluteFilePath;
            }
        }

        return null;
    }

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
