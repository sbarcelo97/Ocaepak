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


use Spatie\Async\Runtime\ParentRuntime;

try {
    $autoloader = $argv[1] ?? null;
    $serializedClosure = $argv[2] ?? null;
    $outputLength = $argv[3] ? intval($argv[3]) : (1024 * 10);

    if (!$autoloader) {
        throw new InvalidArgumentException('No autoloader provided in child process.');
    }

    if (!file_exists($autoloader)) {
        throw new InvalidArgumentException("Could not find autoloader in child process: {$autoloader}");
    }

    if (!$serializedClosure) {
        throw new InvalidArgumentException('No valid closure was passed to the child process.');
    }

    require_once $autoloader;

    $task = ParentRuntime::decodeTask($serializedClosure);

    $output = call_user_func($task);

    $serializedOutput = base64_encode(serialize($output));

    if (strlen($serializedOutput) > $outputLength) {
        throw \Spatie\Async\Output\ParallelError::outputTooLarge($outputLength);
    }

    fwrite(STDOUT, $serializedOutput);

    exit(0);
} catch (Throwable $exception) {
    require_once __DIR__ . '/../Output/SerializableException.php';

    $output = new \Spatie\Async\Output\SerializableException($exception);

    fwrite(STDERR, base64_encode(serialize($output)));

    exit(1);
}
