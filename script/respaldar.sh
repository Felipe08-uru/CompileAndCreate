FECHA=$(TZ="America/Montevideo" date +%Y-%m-%d_%H-%M-%S)

tar -czvf "/datos/script/respaldos/local/respaldo-$FECHA.tar.gz" \
    --exclude='./script/respaldos' \
    -C /datos .

mysqldump -h db -P 3306 -u root sigeru > "/datos/script/respaldos/local/respaldo-$FECHA.sql"

cp "/datos/script/respaldos/local/respaldo-$FECHA.tar.gz" "/datos/script/respaldos/remoto/respaldo-$FECHA.tar.gz" && cp "/datos/script/respaldos/local/respaldo-$FECHA.sql" "/datos/script/respaldos/remoto/respaldo-$FECHA.sql"

rclone copy "/datos/script/respaldos/remoto/respaldo-$FECHA.tar.gz" "remoto:proyecto" --drive-shared-with-me
rclone copy "/datos/script/respaldos/remoto/respaldo-$FECHA.sql" "remoto:proyecto" --drive-shared-with-me

find datos/script/respaldos/local datos/script/respaldos/remoto -name 'respaldo-*' -mtime +7 -delete
rclone delete remoto:proyecto --include "*delete*" --drive-shared-with-me
