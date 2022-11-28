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
