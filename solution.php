<?php declare(strict_types = 1);

require __DIR__ . '/vendor/autoload.php';

use \Solution\Processor;
use \Solution\Util;

// A file name must be provided.
// No standard input reading option is implemented.
$filename = $argv[1] ?? throw new InvalidArgumentException('No input file name provided.');

// 'stream' as the second argument requires processing a possibly infinite stream.
// Any other value is ignored.
$mode = $argv[2] ?? null;

// Examples of decorators:

function foo (string $s): string {
    // Note: It's not specified explicitly in the assginment,
    // but the code example given skips lines that don't match the following regex test.
    // Here such lines are transformed into empty strings which are later filtered out.
    $matches = [];
    if (!preg_match('/test\.(\w+)/', $s, $matches))
        return '';
    return strtolower($matches[1] ?? '');
}

function bar (string $s): string {
    return trim($s);
}

// Examples of filters:

function boo (string $s): bool {
    return $s != 'debug';
}

function baz (string $s): bool {
    return $s != '';
}

// Processor configuration examples:
// (They're all semantically equivalent.)

#$processor = new Processor(
#    decorator: fn (string $s) => bar(foo($s)),
#    filter:    fn (string $s) => boo($s) && baz($s)
#);

#$processor = new Processor(
#    decorator: Util::seq(foo(...), bar(...)),
#    filter:    Util::and(boo(...), baz(...))
#);

$processor = Processor::make(
    decorator: [foo(...), bar(...)],
    filter:    [boo(...), baz(...)]
);

#$processor = new Processor(
#    decorator: foo(...),
#    filter:    boo(...),
#);
#$processor
#    ->addDecorator(bar(...))
#    ->addFilter(baz(...));

#$processor = new Processor()
#    ->addDecorator(foo(...))
#    ->addFilter(boo(...))
#    ->addDecorator(bar(...))
#    ->addFilter(baz(...));


if ($mode == 'stream') {
    $input   = Util::read($filename);
    $outputs = $processor->processStream($input);

    echo 'Acumulative count:' . PHP_EOL;
    foreach ($outputs as $output) {
        echo '----' . PHP_EOL;
        foreach ($output as $line => $count)
            echo "$line: $count" . PHP_EOL;
    }

} else {
    $input  = Util::read($filename, wait: false);
    $output = $processor->process($input);

    echo 'Total count:' . PHP_EOL;
    foreach ($output as $line => $count)
        echo "$line: $count" . PHP_EOL;
}
