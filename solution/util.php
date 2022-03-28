<?php declare(strict_types = 1);

namespace Solution;

use \Generator;
use \InvalidArgumentException;
use \RuntimeException;

class Util {

    /**
     *  Function composition.
     *  Statically restricted to input and output of the same type.
     *  @template T
     *  @param (callable (T): T) ...$fs
     *  @return (callable (T): T)
     */
    public static function seq (callable ...$fs): callable {
        return function ($x) use ($fs) {
            foreach ($fs as $f)
                $x = $f($x);
            return $x;
        };
    }

    /**
     *  A conjunction of predicates.
     *  @template T
     *  @param (callable (T): bool) ...$fs
     *  @return (callable (T): bool)
     */
    public static function and (callable ...$fs): callable {
        return function ($x) use ($fs) {
            foreach ($fs as $f) if (!$f($x))
                return false;
            return true;
        };
    }

    /**
     *  A disjunction of predicates.
     *  @template T
     *  @param (callable (T): bool) ...$fs
     *  @return (callable (T): bool)
     */
    public static function or (callable ...$fs): callable {
        return function ($x) use ($fs) {
            foreach ($fs as $f) if ($f($x))
                return true;
            return false;
        };
    }

    /**
     *  Reads a potentially infinite stream from a file.
     *  To only read a finite file, set `$wait` to false.
     *  @param positive-int | false $wait
     *      If a non-false value given, the function will check for new lines
     *      at the end of the given file indefinitely in the given millisecond value intervals.
     *  @return Generator<string>
     *  @throws InvalidArgumentException
     *  @throws RuntimeException
     */
    public static function read (string $filename, int | false $wait = 500): Generator {
        $file = self::open($filename);
        try {
            while (true) {
                $line = fgets($file);
                if ($line) {
                    yield $line;
                } else {
                    if ($wait === false)
                        break;
                    self::release($file, $filename, $wait);
                }
            }
        } finally {
            fclose($file);
        }
    }

    /**
     *  A helper for the `Util::read` method.
     *  @return resource
     *  @throws InvalidArgumentException
     */
    private static function open (string $filename) {
        $file = fopen($filename, 'r');
        if (!$file)
            throw new InvalidArgumentException("Unable to read the `$filename` file.");
        return $file;
    }

    /**
     *  A helper for the `Util::read` method.
     *  Temporarily closes the given resource for the given ammount of time
     *  before reopening it at the same position.
     *  @param resource $handle
     *  @param positive-int $delay Delay in milliseconds.
     *  @throws RuntimeException
     */
    private static function release (&$handle, string $filename, int $delay): void {
        $offset = ftell($handle);
        if (!$offset)
            throw new RuntimeException("An internal error occurred while releasing a resource.");
        fclose($handle);
        usleep($delay * 1000);
        $handle = fopen($filename, 'r');
        if (!$handle)
            throw new RuntimeException("An internal error occurred while releasing a resource.");
        fseek($handle, $offset);
    }

}
