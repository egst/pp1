<?php declare(strict_types = 1);

namespace Solution;

use \Closure;
use \Generator;

use \Solution\Util;

class Processor {

    /** @var (callable (string): string) $decorator */
    protected $decorator;
    /** @var (callable (string): bool) $filter */
    protected $filter;

    /**
     *  @param ?(callable (string): string) $decorator
     *  @param ?(callable (string): bool)   $filter
     */
    public function __construct (?callable $decorator = null, ?callable $filter = null) {
        $this->decorator = $decorator ?? fn (string $x): string => $x;
        $this->filter    = $filter    ?? fn (string $_): bool   => true;
    }

    /**
     *  @param iterable<string> $input
     *  @return array<string, int>
     */
    public function process (iterable $input): array {
        $result = [];
        foreach ($input as $line)
            self::updateResult($result, $this->processLine($line));
        return $result;
    }

    /**
     *  @param iterable<string> $input
     *  @return Generator<array<string, int>>
     */
    public function processStream (iterable $input): Generator {
        $result = [];
        foreach ($input as $line) {
            self::updateResult($result, $this->processLine($line));
            yield $result;
        }
    }

    protected function processLine (string $line): ?string {
        $transformed = ($this->decorator)($line);
        $selected    = ($this->filter)($transformed);
        return $selected ? $transformed : null;
    }

    /** @param array<string, int> $result */
    protected static function updateResult (array &$result, ?string $line): void {
        if ($line === null) return;
        $result[$line] = ($result[$line] ?? 0) + 1;
    }

    // The rest only provides a nicer interface...

    /**
     *  @param ((Closure (string): string) | array<Closure (string): string>) $decorator
     *  @param ((Closure (string): bool)   | array<Closure (string): bool>)   $filter
     *  Note that only closures are allowed here to avoid an ambiguity of arrays of strings.
     *  E.g. `['foo', 'bar']` could represent either `foo::bar(...)` or `[foo(...), bar(...)]`.
     *  To pass generic callables (strings and arrays), use the constructor.
     */
    public static function make (
        Closure | array | null $decorator = null,
        Closure | array | null $filter    = null
    ):  self {
        $decorator ??= [];
        $filter    ??= [];
        if (is_array($decorator)) $decorator = Util::seq(...$decorator);
        if (is_array($filter))    $filter    = Util::and(...$filter);
        return new self($decorator, $filter);
    }

    /** @param (callable (string): string) $decorator */
    public function addDecorator (callable $decorator): static {
        $this->decorator = Util::seq($this->decorator, $decorator);
        return $this;
    }

    /** @param (callable (string): bool) $filter */
    public function addFilter (callable $filter): static {
        $this->filter = Util::and($this->filter, $filter);
        return $this;
    }

    /** @param (callable (string): bool) $filter */
    public function addDisjunctiveFilter (callable $filter): static {
        $this->filter = Util::or($this->filter, $filter);
        return $this;
    }

}

