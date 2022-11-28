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
namespace Spatie\Async\Process;

use Spatie\Async\Task;
use Throwable;

class SynchronousProcess implements Runnable
{
    use ProcessCallbacks;
    protected $id;

    protected $task;

    protected $output;
    protected $errorOutput;
    protected $executionTime;

    public function __construct(callable $task, int $id)
    {
        $this->id = $id;
        $this->task = $task;
    }

    public static function create(callable $task, int $id): self
    {
        return new self($task, $id);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPid(): ?int
    {
        return $this->getId();
    }

    public function start()
    {
        $startTime = microtime(true);

        if ($this->task instanceof Task) {
            $this->task->configure();
        }

        try {
            $this->output = $this->task instanceof Task
                ? $this->task->run()
                : call_user_func($this->task);
        } catch (Throwable $throwable) {
            $this->errorOutput = $throwable;
        } finally {
            $this->executionTime = microtime(true) - $startTime;
        }
    }

    public function stop($timeout = 0): void
    {
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function getErrorOutput()
    {
        return $this->errorOutput;
    }

    public function getCurrentExecutionTime(): float
    {
        return $this->executionTime;
    }

    protected function resolveErrorOutput(): Throwable
    {
        return $this->getErrorOutput();
    }
}
