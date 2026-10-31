<?php

namespace KimaiPlugin\TimerSecondsBundle\EventSubscriber;

use App\Event\ThemeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Shows the running timer as HH:MM:SS ("00:05:23" instead of "0:05") and refreshes it every second.
 *
 * Kimai has no setting for it: LocaleService::getDurationFormat() always returns "%h:%m", KimaiDateUtils drops the
 * seconds and KimaiActiveRecords refreshes the timer every 10 seconds. The script replaces formatDuration() of the
 * "date" plugin only when it gets a start date (running entries: toolbar timer, browser title, running rows of the
 * lists); durations given in seconds keep Kimai's JavaScript format. Server-rendered durations (saved entries,
 * totals) come from the twig filter in Twig/DurationSecondsExtension.php.
 *
 * The script also marks the forms saved with the begin Kimai prefilled, so that a timer started from the "Start"
 * form begins at the save (see Form/StartSecondsExtension.php).
 */
final class TimerSecondsSubscriber implements EventSubscriberInterface
{
    private const SCRIPT = <<<'HTML'
        <script>
        document.addEventListener('kimai.initialized', function (event) {
            let dates, records;
            try {
                dates = event.detail.kimai.getPlugin('date');
                records = event.detail.kimai.getPlugin('active-records');
            } catch (error) {
                // a Kimai version without these plugins: leave Kimai's own timer alone
                console.warn('TimerSecondsBundle disabled:', error);
                return;
            }
            const formatDuration = dates.formatDuration.bind(dates);
            const updateTitle = !!records.getConfiguration('updateBrowserTitle');
            const pad = (value) => String(value).padStart(2, '0');

            // running entries pass their start (PHP DATE_ISO8601, "2026-10-15T10:00:00+0200"), the rest seconds
            dates.formatDuration = function (since) {
                if (typeof since !== 'string') {
                    return formatDuration(since);
                }
                const begin = Date.parse(since.replace(/([+-]\d{2})(\d{2})$/, '$1:$2'));
                if (isNaN(begin)) {
                    return formatDuration(since);
                }
                let seconds = Math.floor((Date.now() - begin) / 1000);
                const sign = seconds < 0 ? '-' : '';
                seconds = Math.abs(seconds);

                return sign + pad(Math.floor(seconds / 3600)) + ':' + pad(Math.floor(seconds / 60) % 60) + ':' + pad(seconds % 60);
            };

            // like KimaiActiveRecords._updateDuration(), without redrawing the favicon every second
            const tick = function () {
                let title = null;
                for (const record of document.querySelectorAll('[data-since]:not([data-since=""])')) {
                    const duration = dates.formatDuration(record.dataset.since);
                    record.textContent = duration;
                    if (title === null && record.dataset.replacer !== undefined) {
                        title = duration;
                    }
                }
                if (title !== null && updateTitle) {
                    document.title = title;
                }
            };
            tick();
            setInterval(tick, 1000);
        });

        // a form saved with the begin Kimai prefilled (date and time as rendered): tell the server, which then starts
        // the timer at the save (Form/StartSecondsExtension::UNTOUCHED); capture phase, before Kimai's modal reads it
        window.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            const date = form.querySelector('input[name$="[begin_date]"]');
            const time = form.querySelector('input[name$="[begin_time]"]');
            let marker = form.querySelector('input[name="timer_seconds_begin_untouched"]');
            const untouched = date !== null && time !== null && date.value === date.defaultValue && time.value === time.defaultValue;
            if (untouched && marker === null) {
                marker = document.createElement('input');
                marker.type = 'hidden';
                marker.name = 'timer_seconds_begin_untouched';
                marker.value = '1';
                form.appendChild(marker);
            } else if (!untouched && marker !== null) {
                marker.remove();
            }
        }, true);
        </script>
        HTML;

    public static function getSubscribedEvents(): array
    {
        return [ThemeEvent::JAVASCRIPT => ['onJavascript', 100]];
    }

    public function onJavascript(ThemeEvent $event): void
    {
        // the login page triggers the event too, without user and without timer
        if ($event->getUser() === null) {
            return;
        }

        $event->addContent(self::SCRIPT);
    }
}
