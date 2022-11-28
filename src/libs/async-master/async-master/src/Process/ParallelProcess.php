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

use Spatie\Async\Output\ParallelError;
use Spatie\Async\Output\SerializableException;
use Symfony\Component\Process\Process;
use Throwable;

class ParallelProcess implements Runnable
{
    use ProcessCallbacks;
    protected $process;
    protected $id;
    protected $pid;

    protected $output;
    protected $errorOutput;

    protected $startTime;

    public function __construct(Process $process, int $id)
    {
        $this->process = $process;
        $this->id = $id;
    }

    public static function create(Process $process, int $id): self
    {
        return new self($process, $id);
    }

    public function start(): self
    {
        $this->startTime = microtime(true);

        $this->process->start();

        $this->pid = $this->process->getPid();

        return $this;
    }

    public function stop($timeout = 0): self
    {
        $this->process->stop($timeout, SIGKILL);

        return $this;
    }

    public function isRunning(): bool
    {
        return $this->process->isRunning();
    }

    public function isSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    public function isTerminated(): bool
    {
        return $this->process->isTerminated();
    }

    public function getOutput()
    {
        if (!$this->output) {
            $processOutput = $this->process->getOutput();

            $this->output = @unserialize(base64_decode($processOutput));

            if (!$this->output) {
                $this->errorOutput = $processOutput;
            }
        }

        return $this->output;
    }

    public function getErrorOutput()
    {
        if (!$this->errorOutput) {
            $processOutput = $this->process->getErrorOutput();

            $this->errorOutput = @unserialize(base64_decode($processOutput));

            if (!$this->errorOutput) {
                $this->errorOutput = $processOutput;
            }
        }

        return $this->errorOutput;
    }

    public function getProcess(): Process
    {
        return $this->process;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function getCurrentExecutionTime(): float
    {
        return microtime(true) - $this->startTime;
    }

    protected function resolveErrorOutput(): Throwable
    {
        $exception = $this->getErrorOutput();

        if ($exception instanceof SerializableException) {
            $exception = $exception->asThrowable();
        }

        if (!$exception instanceof Throwable) {
            $exception = ParallelError::fromException($exception);
        }

        return $exception;
    }
}
