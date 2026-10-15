# TimerSecondsBundle

A [Kimai](https://www.kimai.org) plugin that shows the running timer as `HH:MM:SS` (`00:05:23` instead of
`0:05`) and refreshes it every second: the stop button in the toolbar, the browser title and the rows of running
entries in the lists. Durations of saved entries keep Kimai's own format.

Kimai has no setting for this: the duration format is always `%h:%m` and the timer is refreshed every 10 seconds
(see [discussion #4972](https://github.com/kimai/kimai/discussions/4972)).

## Requirements

Kimai 2. Tested with 2.69.0; the frontend code it relies on has not changed since 2.0.0.

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

Mount `/opt/kimai/var/plugins` on a volume, otherwise the plugin is lost when the container is recreated.

To remove it, delete the `TimerSecondsBundle` directory and run `kimai:reload` again.

## How it works

Through `ThemeEvent::JAVASCRIPT` the plugin adds a small script to every page of a logged-in user. The script
replaces `formatDuration()` of Kimai's `date` JavaScript plugin, only for running entries (those that pass their
start date), and updates the elements with a `data-since` attribute every second. There is no server-side change
and nothing is stored.

It hooks into Kimai's frontend code, which is not a public API: a future Kimai version may break it. In that case
the timer falls back to Kimai's own format, or the browser console shows an error. Please open an issue.

## License

MIT
