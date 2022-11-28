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
namespace Spatie\Async\Runtime;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;
use Spatie\Async\Pool;
use Spatie\Async\Process\ParallelProcess;
use Spatie\Async\Process\Runnable;
use Spatie\Async\Process\SynchronousProcess;
use Symfony\Component\Process\Process;

class ParentRuntime
{
    /** @var bool */
    protected static $isInitialised = false;

    /** @var string */
    protected static $autoloader;

    /** @var string */
    protected static $childProcessScript;

    protected static $currentId = 0;

    protected static $myPid = null;

    public static function init(string $autoloader = null)
    {
        if (!$autoloader) {
            $existingAutoloaderFiles = array_filter([
                __DIR__ . '/../../../../autoload.php',
                __DIR__ . '/../../../autoload.php',
                __DIR__ . '/../../vendor/autoload.php',
                __DIR__ . '/../../../vendor/autoload.php',
            ], function (string $path) {
                return file_exists($path);
            });

            $autoloader = reset($existingAutoloaderFiles);
        }

        self::$autoloader = $autoloader;
        self::$childProcessScript = __DIR__ . '/ChildRuntime.php';

        self::$isInitialised = true;
    }

    /**
     * @param \Spatie\Async\Task|callable $task
     */
    public static function createProcess($task, ?int $outputLength = null, ?string $binary = 'php'): Runnable
    {
        if (!self::$isInitialised) {
            self::init();
        }

        if (!Pool::isSupported()) {
            return SynchronousProcess::create($task, self::getId());
        }

        $process = new Process([
            $binary,
            self::$childProcessScript,
            self::$autoloader,
            self::encodeTask($task),
            $outputLength,
        ]);

        return ParallelProcess::create($process, self::getId());
    }

    /**
     * @param \Spatie\Async\Task|callable $task
     */
    public static function encodeTask($task): string
    {
        if ($task instanceof Closure) {
            $task = new SerializableClosure($task);
        }

        return base64_encode(serialize($task));
    }

    public static function decodeTask(string $task)
    {
        return unserialize(base64_decode($task));
    }

    protected static function getId(): string
    {
        if (self::$myPid === null) {
            self::$myPid = getmypid();
        }

        ++self::$currentId;

        return (string) self::$currentId . (string) self::$myPid;
    }
}
