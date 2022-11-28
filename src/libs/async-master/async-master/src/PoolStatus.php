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
