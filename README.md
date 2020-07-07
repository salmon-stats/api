# salmon-stats-api

## Overview
- **For front-end web app, please refer to [salmon-stats/web](https://github.com/salmon-stats/web).**
- This project provides API used by following apps:
  * [Salmonia (App Store)](https://apps.apple.com/app/salmonia/id1480684492) by [@tkgstrator](https://github.com/tkgstrator): iOS
  * [salmon-stats/app](https://github.com/salmon-stats/app): Android
  * [salmon-stats/web](https://github.com/salmon-stats/web): Web ([Link](https://salmon-stats.yuki.games))
  * [tkgstrator/Salmonia](https://github.com/tkgstrator/Salmonia): Linux/Mac/Windows

## Installation
```sh
git clone https://github.com/yukidaruma/salmon-stats
docker-compose build

cp example.env .env
# Update .env if necessary.
# You must provide Twitter API key in order to use login with Twitter feature.
vi .env

docker-compose exec app bash
composer install --no-dev # TODO: move `composer install` to Dockerfile
php artisan key:generate
php artisan migrate

# Fetch past Salmon Run schedules
php artisan salmon-stats:fetch-schedules

# Add **host** crontab
* * * * * php /{path_to_project}/artisan schedule:run >> /dev/null 2>&1
```

## Running tests
```sh
docker-compose exec app vendor/bin/phpunit
```

## Start
```sh
docker-compose up -d
```

## Third-party APIs
This app is using following third-party APIs.
* [Spla2 API](https://spla2.yuu26.com/) by [@m_on_yu](https://twitter.com/m_on_yu) for past Salmon Run schedules.
* [Stat.ink API](https://github.com/fetus-hina/stat.ink/tree/master/doc/api-2) by [@fetus_hina](https://twitter.com/fetus_hina) for weapon data.
