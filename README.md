# TimerSecondsBundle

A [Kimai](https://www.kimai.org) plugin that shows times and durations with seconds:

- the running timer as `HH:MM:SS` (`00:05:23` instead of `0:05`), refreshed every second: the stop button in the
  toolbar, the browser title and the rows of running entries in the lists;
- the begin and end of entries as `17:13:27` instead of `17:13` (`5:13:27 PM` in 12-hour locales), wherever Kimai
  shows a time: lists, reports, HTML and PDF exports;
- durations as `00:59:00` instead of `0:59` wherever Kimai renders them on the server: lists, totals, reports,
  dashboard widgets, HTML and PDF exports. Decimal durations are unchanged;
- the seconds of a new entry that begins in the current minute, e.g. a timer started from the "Start" form, which
  Kimai sets to `:00`.

Kimai has no setting for any of this: the duration format is always `%h:%m` and the timer is refreshed every 10
seconds (see [discussion #4972](https://github.com/kimai/kimai/discussions/4972)).

Times are still typed as `HH:mm` (or `h:mm a`) in the forms. Editing an entry without changing its times keeps
its seconds (Kimai's own behaviour).

## Store the seconds: turn off rounding

By default Kimai rounds the begin and the end of every entry to 1 minute, so all entries are stored at `:00` and
the plugin can only add `:00`. To store the seconds, set the rounding of begin and end to `0` in
*System → Settings → Rounding* (configuration keys `timesheet.rounding.default.begin` and
`timesheet.rounding.default.end`). The plugin does not change this setting. Entries saved before keep their `:00`.

## Requirements

Kimai 2. Tested with 2.69.0.

## Installation

The plugin directory must be called `TimerSecondsBundle`:

```sh
cd /path/to/kimai/var/plugins
git clone https://github.com/6801318d8d/TimerSecondsBundle.git
cd /path/to/kimai
bin/console kimai:reload --env=prod
```

With the official Docker image (replace `kimai` with the name of your container):

```sh
git clone https://github.com/6801318d8d/TimerSecondsBundle.git
docker cp TimerSecondsBundle kimai:/opt/kimai/var/plugins/
docker exec kimai chown -R www-data:www-data /opt/kimai/var/plugins/TimerSecondsBundle
docker exec -u www-data kimai /opt/kimai/bin/console kimai:reload --env=prod
```

Run `kimai:reload` as `www-data`: run as root, it leaves cache files that the web server cannot write, and the API
answers with errors.

Mount `/opt/kimai/var/plugins` on a volume, otherwise the plugin is lost when the container is recreated.

To update it, run `git pull` in the plugin directory (or copy the new version over it) and run `kimai:reload`
again. To remove it, delete the `TimerSecondsBundle` directory and run `kimai:reload` again.

## How it works

- **Running timer**: through `ThemeEvent::JAVASCRIPT` the plugin adds a small script to every page of a logged-in
  user. The script replaces `formatDuration()` of Kimai's `date` JavaScript plugin, only for running entries (those
  that pass their start date), and updates the elements with a `data-since` attribute every second. Durations that
  Kimai formats in JavaScript from seconds keep Kimai's format.
- **Times**: a compiler pass adds `:ss` to the time format of every locale in the container parameter
  `kimai.languages` (built from `config/locales.php` and read by `LocaleService`). The forms are not affected:
  `TimePickerType` always types times as `HH:mm` or `h:mm a`.
- **Durations**: a Twig extension, registered after Kimai's `LocaleFormatExtensions`, replaces the `duration`
  filter.
- **Seconds of new entries**: a form type extension of `TimesheetEditForm`, `TimesheetAdminEditForm` and
  `TimesheetMultiUserEditForm` restores the prefilled seconds when the submitted begin is still in the prefilled
  minute. Begins typed by hand keep `:00`.

Nothing is stored by the plugin.

It hooks into Kimai's internals (frontend code, a container parameter, a Twig filter, form events), which are not
a public API: a future Kimai version may break it. In that case the timer falls back to Kimai's own format, the
browser console shows an error, or Kimai shows an error page; remove the plugin and please open an issue.

## Changes

- 2.0.0: times as `HH:mm:ss` and durations as `HH:MM:SS` everywhere, seconds of new entries kept.
- 1.0.0: running timer as `HH:MM:SS`, refreshed every second.

## License

MIT
