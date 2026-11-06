#!/bin/sh
# Respaldo diario: archivos del proyecto + volcado de la base. Se ejecuta dentro del contenedor "respaldos".
set -u

FECHA=$(TZ="America/Montevideo" date +%Y-%m-%d_%H-%M-%S)
DIR=/datos/script/respaldos
mkdir -p "$DIR/local" "$DIR/remoto"

# Los respaldos anteriores NO se incluyen en el nuevo (si no, cada copia contendría las anteriores y el tamaño se multiplica).
tar -czf "$DIR/local/respaldo-$FECHA.tar.gz" \
    --exclude='datos/script/respaldos' \
    --exclude='datos/.git' \
    -C / datos

# La contraseña llega por la variable MYSQL_PWD definida en docker-compose.yml.
mysqldump -h db -P 3306 -u root sigeru > "$DIR/local/respaldo-$FECHA.sql"

cp "$DIR/local/respaldo-$FECHA.tar.gz" "$DIR/remoto/"
cp "$DIR/local/respaldo-$FECHA.sql" "$DIR/remoto/"

rclone copy "$DIR/remoto/respaldo-$FECHA.tar.gz" "remoto:proyecto" --drive-shared-with-me
rclone copy "$DIR/remoto/respaldo-$FECHA.sql" "remoto:proyecto" --drive-shared-with-me

# Se conservan solo 7 días en disco.
find "$DIR/local" "$DIR/remoto" -name 'respaldo-*' -mtime +7 -delete
