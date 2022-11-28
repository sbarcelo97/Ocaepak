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

use ReflectionFunction;
use Throwable;

trait ProcessCallbacks
{
    protected $successCallbacks = [];
    protected $errorCallbacks = [];
    protected $timeoutCallbacks = [];

    public function then(callable $callback): self
    {
        $this->successCallbacks[] = $callback;

        return $this;
    }

    public function catch(callable $callback): self
    {
        $this->errorCallbacks[] = $callback;

        return $this;
    }

    public function timeout(callable $callback): self
    {
        $this->timeoutCallbacks[] = $callback;

        return $this;
    }

    public function triggerSuccess()
    {
        if ($this->getErrorOutput()) {
            $this->triggerError();

            return;
        }

        $output = $this->getOutput();

        foreach ($this->successCallbacks as $callback) {
            call_user_func_array($callback, [$output]);
        }

        return $output;
    }

    public function triggerError()
    {
        $exception = $this->resolveErrorOutput();

        if (!$this->errorCallbacks) {
            throw $exception;
        }

        foreach ($this->errorCallbacks as $callback) {
            if (!$this->isAllowedThrowableType($exception, $callback)) {
                continue;
            }

            call_user_func_array($callback, [$exception]);

            break;
        }
    }

    abstract protected function resolveErrorOutput(): Throwable;

    public function triggerTimeout()
    {
        foreach ($this->timeoutCallbacks as $callback) {
            call_user_func_array($callback, []);
        }
    }

    protected function isAllowedThrowableType(Throwable $throwable, callable $callable): bool
    {
        $reflection = new ReflectionFunction($callable);

        $parameters = $reflection->getParameters();

        if (!isset($parameters[0])) {
            return true;
        }

        $firstParameter = $parameters[0];

        if (!$firstParameter) {
            return true;
        }

        $type = $firstParameter->getType();

        if (!$type) {
            return true;
        }

        if (is_a($throwable, $type->getName())) {
            return true;
        }

        return false;
    }
}
