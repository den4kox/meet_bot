#/bin/bash
docker stop $(docker ps -a -q)
sudo rm -rf ./meetbot
git clone https://github.com/den4kox/meet_bot.git ./meetbot

cp env ./meetbot/.env

cd ./meetbot
git submodule add https://github.com/Laradock/laradock.git

cd ./laradock
docker-compose up -d nginx mysql

docker-compose exec workspace bash
composer install
php artisan key:generate
php artisan migrate
exit

cd ..
sudo chmod -R 777 storage bootstrap/cache

cp ../meetbot.conf ./laradock/nginx/sites/meetbot.conf





