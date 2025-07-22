# moto/autoload

This project defines a name-to-file resolution algorithm for autoloaders that allows [more-than-one class per file](#more-than-one-class-per-file).

[![PDS Skeleton](https://img.shields.io/badge/pds-skeleton-blue.svg?style=flat-square)](https://github.com/php-pds/skeleton)
[![PDS Composer Script Names](https://img.shields.io/badge/pds-composer--script--names-blue?style=flat-square)](https://github.com/php-pds/composer-script-names)

## Theory of Operation

### One Class Per File

Historically, PHP class-to-file naming conventions have supported only one class per file.

#### Horde/PEAR

In the pre-namespacing versions of PHP, the original [Horde/PEAR resolution logic](https://pear.php.net/manual/en/standards.naming.php) converted underscores to directory separators to generate a file path:

```
Foo_Bar_Baz_Dib     => /classes/Foo/Bar/Baz/Dib.php
Foo_Bar_Baz_Dib_Zim => /classes/Foo/Bar/Baz/Dib/Zim.php
```

#### PSR-0

[PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) modified this logic to recognized the formal namespaces introduced in PHP 5.3, so that ...

1. namespace separators were converted to directory separators, and
2. underscores only in the terminating class name portion were converted to directory separators:

```
Foo_Bar\Baz\Dib     => /classes/Foo_Bar/Baz/Dib.php
Foo_Bar\Baz\Dib_Zim => /classes/Foo_Bar/Baz/Dib/Zim.php
```

#### PSR-4

[PSR-4](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md) modified PSR-0 so that ...

1. underscores in the terminating class name portion would no longer be converted to directory separators, and
2. a namespace prefix could be mapped to a directory prefix:

```
/**
 * Foo_Bar\Baz\ maps to /classes/foo-bar/baz/src/
 */
Foo_Bar\Baz\Dib     => /classes/foo-bar/baz/src/Dib.php
Foo_Bar\Baz\Dib_Zim => /classes/foo-bar/baz/src/Dib_Zim.php
```

### More-Than-One Class Per File

The Moto name-to-file [resolution algorithm](#resolution-algorithm) modifies this even further, so that everything after an underscore in the terminating class name portion is ignored:

```
/**
 * Foo_Bar\Baz\ maps to /classes/foo-bar/baz/src/
 */
Foo_Bar\Baz\Dib     => /classes/foo-bar/baz/src/Dib.php
Foo_Bar\Baz\Dib_Zim => /classes/foo-bar/baz/src/Dib.php
Foo_Bar\Baz\Dib_Gir => /classes/foo-bar/baz/src/Dib.php
Foo_Bar\Baz\Dib_Irk => /classes/foo-bar/baz/src/Dib.php
```

This means the `Dib.php` file can contain any number of underscore-suffixed classes so long as they are prefixed with `Dib` ...

```php
// /classes/foo-bar/src/Baz/Dib.php
namespace Foo_Bar/Baz;

class Dib { }

class Dib_Zim { }

class Dib_Gir { }

class Dib_Irk { }
```

... and a Moto-compliant resolver will find the correct file for that class.

## Resolution Algorithm

Given ...

- a fully qualified `namespace-prefix` (including the rightmost namespace separator), and

- an absolute `directory-prefix` for the `namespace-prefix` (including the rightmost directory separator)

... the following algorithm resolves a `fully-qualified-name` to an `absolute-file-path` or to `null`:

1. If the `fully-qualified-name` does not begin with the `namespace-prefix`, the `fully-qualified-name` resolves to `null`.

2. Otherwise, set the `qualified-name` by capturing all characters after the `namespace-prefix`.

3. If the `qualified-name` contains a namespace separator ...

    A. Set the `partial-namespace` by capturing all characters up to and including the rightmost namespace separator, and set the `partial-name` by capturing all characters after the rightmost namespace separator.

    B. Otherwise, set the `partial-namespace` to an empty string, and set the `partial-name` to the `qualified-name`.

4. If the `partial-name` contains an underscore, remove the first underscore and all characters thereafter.

5. If the `partial-name` is empty, the `fully-qualified-name` resolves to `null`.

6. Otherwise, set the `partial-directory` by converting each namespace separator in the `partial-namespace` to a `DIRECTORY_SEPARATOR`.

7. Set the `absolute-file-path` by concatenating the `directory-prefix`, `partial-directory`, `partial-name`, and a `filename-suffix` (typically `.php`).

8. If the `absolute-file-path` exists, the `fully-qualified-name` resolves to the `absolute-file-path`; otherwise, it resolves to `null`.

Cf. the [reference implementation](./src/Resolver.php) `resolutionAlgotrithm()` method for an example.

## Implications

- Directory names with underscores WILL be resolvable.

- Base file names with underscores WILL NOT be resolvable.

- The `partial-name` itself does not have to exist at the `absolute-file-path`; that is, the file might contain only underscore-suffixed names:

    ```php
    // Foo_Bar\Baz => /vendor/foo-bar/baz/src/Dib.php
    namespace Foo_Bar\Baz;

    // no Dib {} class

    class Dib_Zim {}

    class Dib_Gir {}

    class Dib_Irk {}
    ```

- If function autoloading becomes supported by PHP, the Moto name-to-file resolution algorithm can support multiple functions per file:

    ```php
    // Foo_Bar\Baz => /vendor/foo-bar/baz/src/dib.php
    namespace Foo_Bar\Baz;

    function dib() {}

    function dib_zim() {}

    function dib_gir() {}

    function dib_irk() {}
    ```

* * *
