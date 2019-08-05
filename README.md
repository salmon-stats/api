# salmon-stats-api

## Overview
- **For front-end web app, please refer to [salmon-stats-app](https://github.com/yukidaruma/salmon-stats-app).**
- This project provides API used by [salmon-stats-app](https://github.com/yukidaruma/salmon-stats-app).
- The app is available online at [http://salmon-stats.yuki.games](http://salmon-stats.yuki.games).

## Installation
```sh
git clone https://github.com/yukidaruma/salmon-stats
docker-compose build

cp example.env .env
# Update .env if necessary.
# You must provide Twitter API key in order to use login with Twitter feature.
vi .env

docker-compose exec app bash
php artisan key:generate
php artisan migrate
```

## Start
```sh
docker-compose up -d
```

## Third-party APIs
This app is using following third-party APIs.
* [Spla2 API](https://spla2.yuu26.com/) by [@m_on_yu](https://twitter.com/m_on_yu) for past Salmon Run schedules.
* [Stat.ink API](https://github.com/fetus-hina/stat.ink/tree/master/doc/api-2) by [@fetus_hina](https://twitter.com/fetus_hina) for weapon data.
