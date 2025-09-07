#!/bin/bash

# Настройки
PROJECT_DIR="/var/www/lastframe"
REPO_URL="https://github.com/wollkey/lastframe.git"
PORT=8080

echo "🚀 Начинаем деплой..."

# Переходим в директорию проекта или клонируем репозиторий
if [ -d "$PROJECT_DIR" ]; then
    echo "📁 Переходим в существующую директорию проекта"
    cd $PROJECT_DIR
    
    echo "🔄 Останавливаем текущий сервер (если запущен)"
    pkill -f "php -S.*:$PORT" || true
    
    echo "📥 Обновляем код из репозитория"
    git pull origin main
else
    echo "📥 Клонируем репозиторий"
    git clone $REPO_URL $PROJECT_DIR
    cd $PROJECT_DIR
fi

# Устанавливаем зависимости, если есть composer.json
if [ -f "composer.json" ]; then
    echo "📦 Устанавливаем PHP зависимости"
    composer install --no-dev --optimize-autoloader
fi

# Проверяем права доступа
echo "🔐 Настраиваем права доступа"
chmod -R 755 public/
chmod +x deploy.sh

# Запускаем сервер в фоне
echo "🌐 Запускаем PHP сервер на порту $PORT"
nohup php -S 0.0.0.0:$PORT -t public > server.log 2>&1 &

echo "✅ Деплой завершен!"
echo "🌍 Сайт доступен по адресу: http://77.73.71.219:$PORT"
echo "📋 Логи сервера: $PROJECT_DIR/server.log"

# Показываем статус
sleep 2
if pgrep -f "php -S.*:$PORT" > /dev/null; then
    echo "✅ Сервер успешно запущен"
else
    echo "❌ Ошибка запуска сервера. Проверьте логи:"
    tail -10 server.log
fi
