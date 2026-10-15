<?php

namespace KimaiPlugin\TimerSecondsBundle;

use App\Plugin\PluginInterface;
use KimaiPlugin\TimerSecondsBundle\DependencyInjection\Compiler\TimeFormatPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Shows the running timer as HH:MM:SS, refreshed every second, times as HH:mm:ss and durations as HH:MM:SS,
 * and keeps the seconds of new entries.
 */
class TimerSecondsBundle extends Bundle implements PluginInterface
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new TimeFormatPass());
    }
}
