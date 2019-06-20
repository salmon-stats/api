<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title')</title>
    </head>
    <body>
        @yield('content')

        <footer class="footer">
            <p>
                Source code is available on <a href="https://github.com/yukidaruma/salmon-stats">GitHub</a>.
                Made with ❄️ by <a href="https://twitter.com">@Yukinkling</a>
            </p>
        </footer>
    </body>
</html>
