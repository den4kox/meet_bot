#/bin/bash
docker stop $(docker ps -a -q)
sudo rm -rf ./meetbot
git clone https://github.com/den4kox/meet_bot.git ./meetbot

cd ./meetbot
cp ../backend_env ./.env
sudo chmod -R 777 storage bootstrap/cache

git clone https://github.com/Laradock/laradock.git

cp ../laradock_env ./laradock/.env
cd ./laradock
cp ../../Caddyfile ./caddy/Caddyfile
docker-compose up -d nginx mysql phpmyadmin redis workspace 

docker-compose exec workspace composer install
docker-compose exec workspace php artisan key:generate
docker-compose exec workspace php artisan migrate


