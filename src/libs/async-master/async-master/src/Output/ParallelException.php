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
namespace Spatie\Async\Output;

class ParallelException extends \Exception
{
    /** @var string */
    protected $originalClass;

    /** @var string */
    protected $originalTrace;

    public function __construct(string $message, string $originalClass, string $originalTrace)
    {
        parent::__construct($message);
        $this->originalClass = $originalClass;
        $this->originalTrace = $originalTrace;
    }

    /** @return string */
    public function getOriginalClass(): string
    {
        return $this->originalClass;
    }

    /** @return string */
    public function getOriginalTrace(): string
    {
        return $this->originalTrace;
    }
}
