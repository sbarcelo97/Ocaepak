<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
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
