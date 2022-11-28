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

interface Runnable
{
    public function getId(): int;

    public function getPid(): ?int;

    public function start();

    /**
     * @return static
     */
    public function then(callable $callback);

    /**
     * @return static
     */
    public function catch(callable $callback);

    /**
     * @return static
     */
    public function timeout(callable $callback);

    /**
     * @param int|float $timeout The timeout in seconds
     *
     * @return mixed
     */
    public function stop($timeout = 0);

    public function getOutput();

    public function getErrorOutput();

    public function triggerSuccess();

    public function triggerError();

    public function triggerTimeout();

    public function getCurrentExecutionTime(): float;
}
