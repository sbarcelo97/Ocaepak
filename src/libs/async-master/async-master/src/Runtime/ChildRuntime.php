<?php
/*   Copyright 2022 Region Global

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/
use Spatie\Async\Runtime\ParentRuntime;

try {
    $autoloader = $argv[1] ?? null;
    $serializedClosure = $argv[2] ?? null;
    $outputLength = $argv[3] ? intval($argv[3]) : (1024 * 10);

    if (!$autoloader) {
        throw new InvalidArgumentException('No autoloader provided in child process.');
    }

    if (!file_exists($autoloader)) {
        throw new InvalidArgumentException("Could not find autoloader in child process: {$autoloader}");
    }

    if (!$serializedClosure) {
        throw new InvalidArgumentException('No valid closure was passed to the child process.');
    }

    require_once $autoloader;

    $task = ParentRuntime::decodeTask($serializedClosure);

    $output = call_user_func($task);

    $serializedOutput = base64_encode(serialize($output));

    if (strlen($serializedOutput) > $outputLength) {
        throw \Spatie\Async\Output\ParallelError::outputTooLarge($outputLength);
    }

    fwrite(STDOUT, $serializedOutput);

    exit(0);
} catch (Throwable $exception) {
    require_once __DIR__ . '/../Output/SerializableException.php';

    $output = new \Spatie\Async\Output\SerializableException($exception);

    fwrite(STDERR, base64_encode(serialize($output)));

    exit(1);
}
