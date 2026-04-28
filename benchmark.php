<?php

// LIBRARY BENCHMARKING SCRIPT. How to use it? Run:
// $ php benchmark.php <Width> <Depth> <Repeat>
//
// It builds a dataset of W^D objects (Width to the power of Depth).
// Then, it serializes and deserializes the dataset R (Repeat) times.
//
// Finally, the script outputs the results and saves them to the benchmarks/ directory.

use JuraSciix\DataMapper\DataMapper;

require "vendor/autoload.php";

// Требуется побольше памяти
ini_set('memory_limit', '4G');

class Node {
    /**
     * @param Node[] $a
     */
    public function __construct(
        readonly array $a = [],
        readonly int   $q = 0
    ) {}
}

function buildDataset(int $width, int $depth): Node {
    $a = [];
    for ($i = 0; $width > $i && $depth > 0; $i++) {
        $a[] = buildDataset($depth - 1, $width);
    }
    return new Node($a, $width * $depth);
}

function serializeDataset(Node $dataset): array {
    $a = [];
    foreach ($dataset->a as $node) {
        $a[] = serializeDataset($node);
    }
    return ['a' => $a, 'q' => $dataset->q];
}

function compareDatasets(Node $lhs, Node $rhs): bool {
    // Мы сравниваем значения q всех узлов, так как экземпляры могут отличаться.
    if ($lhs->q !== $rhs->q || count($lhs->a) !== count($rhs->a)) {
        return false;
    }
    for ($i = 0; $i < count($lhs->a); $i++) {
        if (!compareDatasets($lhs->a[$i], $rhs->a[$i])) {
            return false;
        }
    }
    return true;
}

const MESSAGE_SHOW_DATE = false;

function message(string $message): void {
    $date = date('H:i:s');
    $millis = intval(hrtime()[1] / 1E6);
    if (MESSAGE_SHOW_DATE) {
        echo "[$date.$millis] ";
    }
    echo $message;
    echo PHP_EOL;
}

function measure(callable $callback): callable {
    $closure = $callback(...);
    return function () use ($closure) {
        $args = func_get_args();

        if (PHP_VERSION_ID >= 80200) {
            memory_reset_peak_usage();
        }
        $memStart = PHP_VERSION_ID >= 80200 ? memory_get_peak_usage() : memory_get_usage();
        $start = microtime(true);
        $result = call_user_func_array($closure, $args);
        $end = microtime(true);
        $memEnd = PHP_VERSION_ID >= 80200 ? memory_get_peak_usage() : memory_get_usage();

        return [$end - $start, max($memEnd - $memStart, 0), $result];
    };
}

function timeUnit(int|float $t): string {
    if ($t * 1E6 < 1) {
        // Меньше МИКРОСЕКУНДЫ
        return round($t * 1E9, 3) . " ns";
    }
    if ($t * 1000 < 1) {
        // Меньше 1 миллисекунды
        return round($t * 1E6, 3) . " hs";
    }
    if ($t < 1) {
        // Меньше секунды
        return round($t * 1000, 3) . " ms";
    }
    return round($t, 3) . " s";
}

function memoryUnit(int $memory): string {
    if ($memory >= 0x40000000) {
        return intdiv($memory, 0x40000000) . " GB";
    }
    if ($memory >= 0x100000) {
        return intdiv($memory, 0x100000) . " MB";
    }
    if ($memory >= 0x400) {
        return intdiv($memory, 0x400) . "KB";
    }
    return "$memory bytes";
}

function ensure(bool $condition): void {
    if (!$condition) {
        throw new AssertionError();
    }
}

function ratio(int|float $a, int|float $b, float $factor = 1.0, bool $asNumber = false): int|string {
    if ($a < $b) {
        $r = ratio($b, $a, $factor, true);
        return match ($r) {
            0 => "Erased",
            1 => "Same",
            default => "by 1/$r"
        };
    }
    $ratio = ($b === 0) ? 1 : intval($a / $b * $factor);
    return $asNumber ? $ratio : (($ratio === 1) ? "Same" : "by $ratio");
}

function runBenchmark(string $tag, int $width, int $depth, int $repeat, bool $debug = false): void {
    $mapper = new DataMapper();

    $debug && message("Build and manual serialize dataset");
    $dataset = buildDataset($width, $depth);
    $rawDataset = serializeDataset($dataset);
    $debug && message("Dataset built");

    $timerDatabase = [];

    $debug && message("Serializing");
    $m1 = measure(fn() => $mapper->serialize($dataset));
    for ($i = 1; $i <= $repeat; $i++) {
        [$dt, $dm, $serialized] = $m1();
        ensure($serialized === $rawDataset);
        unset($serialized);
        $timerDatabase['Serialize'][0][] = $dt;
        $timerDatabase['Serialize'][1][] = $dm;
        $debug && message("Round #$i done");
    }

    $debug && message("Deserializing");
    $m2 = measure(fn() => $mapper->deserialize($rawDataset, Node::class));
    for ($i = 1; $i <= $repeat; $i++) {
        [$dt, $dm, $deserialized] = $m2();
        ensure(compareDatasets($dataset, $deserialized));
        unset($deserialized);
        $timerDatabase['Deserialize'][0][] = $dt;
        $timerDatabase['Deserialize'][1][] = $dm;
        $debug && message("Round #$i done");
    }

    message("======================== $tag: width=$width, depth=$depth, repeat=$repeat ========================");

    $previous = null;
    if (file_exists("benchmarks/$tag.json")) {
        $previous = json_decode(file_get_contents("benchmarks/$tag.json"), true);
    }

    // Выводим таблицу
    printf("%-12s | %-24s | %-10s | %-24s | %-10s \n", "PROCEDURE", "TIME", "CHANGE_T", "MEMORY", "CHANGE_M");
    foreach ($timerDatabase as $name => [$measures, $memory]) {
        $minT = timeUnit(min($measures));
        $maxT = timeUnit(max($measures));
        $minM = memoryUnit(min($memory));
        $maxM = memoryUnit(max($memory));
        $changeT = "";
        $changeM = "";
        if (isset($previous)) {
            $factor = (1 + $width ** $depth) / (1 + $previous['options']['width'] ** $previous['options']['depth']);
            $changeT = ratio(min($measures), min($previous['database'][$name][0]), $factor);
            $changeM = ratio(min($memory), min($previous['database'][$name][1]), $factor);
        }
        printf("%-12s | %-24s | %-10s | %-24s | %-10s \n",
            $name, "$minT - $maxT", $changeT, "$minM - $maxM", $changeM);
    }

    @mkdir('benchmarks');
    file_put_contents("benchmarks/$tag.json", json_encode([
        'options' => ['width' => $width, 'depth' => $depth],
        'database' => $timerDatabase
    ]));
}

runBenchmark('D', 1, 10, 10);
runBenchmark('D', 1, 1000, 10);
runBenchmark('W', 10, 1, 10);
runBenchmark('W', 1000, 1, 10);
runBenchmark('MD', 2, 50, 10);
runBenchmark('MW', 50, 2, 10);