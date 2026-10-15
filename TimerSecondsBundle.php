<?php

namespace KimaiPlugin\TimerSecondsBundle;

use App\Plugin\PluginInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Shows the running timer as HH:MM:SS, refreshed every second.
 */
class TimerSecondsBundle extends Bundle implements PluginInterface
{
}
