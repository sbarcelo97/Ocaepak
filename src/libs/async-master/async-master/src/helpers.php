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
use Spatie\Async\Pool;
use Spatie\Async\Process\Runnable;
use Spatie\Async\Runtime\ParentRuntime;

if (!function_exists('async')) {
    /**
     * @param \Spatie\Async\Task|callable $task
     *
     * @return \Spatie\Async\Process\ParallelProcess
     */
    function async($task): Runnable
    {
        return ParentRuntime::createProcess($task);
    }
}

if (!function_exists('await')) {
    function await(Pool $pool): array
    {
        return $pool->wait();
    }
}
