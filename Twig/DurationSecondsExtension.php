<?php

namespace KimaiPlugin\TimerSecondsBundle\Twig;

use App\Entity\Timesheet;
use App\Twig\LocaleFormatExtensions;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Replaces Kimai's twig filter "duration" ("0:59", format fixed to "%h:%m" in LocaleService::getDurationFormat())
 * with "00:59:00". Twig keeps the last filter registered with a name, see services.yaml for the order.
 * Decimal durations ("duration(true)") still come from Kimai.
 */
final class DurationSecondsExtension extends AbstractExtension
{
    public function __construct(private readonly LocaleFormatExtensions $localeFormat)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('duration', [$this, 'duration']),
        ];
    }

    public function duration(Timesheet|int|string|null $duration, bool $decimal = false): string
    {
        if ($decimal) {
            return $this->localeFormat->duration($duration, true);
        }

        // the same seconds as LocaleFormatter::getSecondsForDuration()
        if ($duration instanceof Timesheet) {
            if (null === $duration->getEnd()) {
                $duration = time() - $duration->getBegin()->getTimestamp();
            } else {
                $duration = $duration->getDuration() ?? 0;
            }
        }
        $seconds = (int) $duration;
        $sign = $seconds < 0 ? '-' : '';
        $seconds = abs($seconds);

        return \sprintf('%s%02d:%02d:%02d', $sign, intdiv($seconds, 3600), intdiv($seconds, 60) % 60, $seconds % 60);
    }
}
