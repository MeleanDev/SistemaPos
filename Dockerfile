# --- ETAPA 1: Compilar Frontend (Node / Vite) ---
    FROM node:22-alpine AS frontend-builder
    WORKDIR /app

    # Aprovechar caché para dependencias de Node
    COPY package.json package-lock.json* ./
    RUN npm ci

    # Copiar el código fuente y compilar los assets
    COPY . .
    RUN npm run build


    # --- ETAPA 2: Imagen Final con FrankenPHP ---
    FROM dunglas/frankenphp:latest-bookworm

    # Copiar Composer oficial (más rápido que instalarlo vía script)
    COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

    # Utilidades mínimas del sistema (incluye mariadb-client para mysqldump)
    RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        mariadb-client \
        && rm -rf /var/lib/apt/lists/*

    # Extensiones de PHP requeridas
    RUN install-php-extensions \
        pdo_mysql \
        bcmath \
        zip \
        gd \
        redis

    # Configuración de PHP optimizada para reportes grandes y subida de archivos
    RUN echo "memory_limit = -1" > /usr/local/etc/php/conf.d/custom.ini \
        && echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/custom.ini \
        && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/custom.ini \
        && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/custom.ini

    WORKDIR /app

    # 1. Copiar primero solo los archivos de Composer para cachear la capa
    COPY composer.json composer.lock ./
    RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs --no-scripts

    # 2. Copiar todo el código de la aplicación
    COPY . /app

    # 3. Traer los assets ya compilados de la Etapa 1
    COPY --from=frontend-builder /app/public/build /app/public/build

    # 4. Finalizar optimizaciones de Laravel
    RUN composer dump-autoload --optimize --classmap-authoritative \
        && php artisan storage:link || true

    # Permisos
    RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
        && chmod -R 775 /app/storage /app/bootstrap/cache

    ENV SERVER_NAME=":8080"
    EXPOSE 8080