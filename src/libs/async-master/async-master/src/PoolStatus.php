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
namespace Spatie\Async;

use Spatie\Async\Output\SerializableException;
use Spatie\Async\Process\ParallelProcess;

class PoolStatus
{
    protected $pool;

    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    public function __toString(): string
    {
        return $this->lines(
            $this->summaryToString(),
            $this->failedToString()
        );
    }

    protected function lines(string ...$lines): string
    {
        return implode(PHP_EOL, $lines);
    }

    protected function summaryToString(): string
    {
        $queue = $this->pool->getQueue();
        $finished = $this->pool->getFinished();
        $failed = $this->pool->getFailed();
        $timeouts = $this->pool->getTimeouts();

        return
            'queue: ' . count($queue)
            . ' - finished: ' . count($finished)
            . ' - failed: ' . count($failed)
            . ' - timeout: ' . count($timeouts);
    }

    protected function failedToString(): string
    {
        return (string) array_reduce($this->pool->getFailed(), function ($currentStatus, ParallelProcess $process) {
            $output = $process->getErrorOutput();

            if ($output instanceof SerializableException) {
                $output = get_class($output->asThrowable()) . ': ' . $output->asThrowable()->getMessage();
            }

            return $this->lines((string) $currentStatus, "{$process->getPid()} failed with {$output}");
        });
    }
}
