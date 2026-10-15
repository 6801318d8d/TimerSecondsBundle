<?php

namespace KimaiPlugin\TimerSecondsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds the seconds to the time format of every locale ("HH:mm" becomes "HH:mm:ss", "h:mm a" becomes "h:mm:ss a").
 *
 * AppExtension builds the parameter "kimai.languages" from config/locales.php and LocaleService receives it as its
 * only argument; the parameter is resolved after this pass. The format is used only for display (twig filters
 * "time" and "date_time", the help page, the HTML and PDF exports): TimePickerType always types times as "HH:mm"
 * or "h:mm a", whatever the locale.
 */
final class TimeFormatPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('kimai.languages')) {
            return;
        }

        $languages = $container->getParameter('kimai.languages');
        foreach ($languages as $locale => $settings) {
            $time = $settings['time'] ?? null;
            if (!\is_string($time) || str_contains($time, 's') || !str_contains($time, 'mm')) {
                continue;
            }
            $languages[$locale]['time'] = preg_replace('/mm/', 'mm:ss', $time, 1);
        }

        $container->setParameter('kimai.languages', $languages);
    }
}
