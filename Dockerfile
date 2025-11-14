# --- Étape 1 : image PHP avec Apache ---
FROM php:8.2-apache

# --- Étape 2 : activer PDO pour MySQL ---
RUN docker-php-ext-install pdo pdo_mysql

# --- Étape 3 : copier tout le code dans le conteneur ---
COPY . /var/www/html/

# --- Étape 4 : exposer le port pour Render ---
EXPOSE 10000
