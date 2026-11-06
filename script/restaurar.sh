opc="--config ../rclone/rclone.conf --drive-shared-with-me"

Hacer() {
	docker exec respaldos sh /datos/script/respaldar.sh
	date +"El %d de %B %Y a las %T se hizo un respaldo" >> log.txt
}

Listar() {
	echo "Respaldos locales:"
	ls -l respaldos/local
	echo "Respaldos en Drive:"
	rclone lsf remoto:proyecto $opc | grep '^respaldo-' | sort
}

Restaurar() {
	echo "------------------------------------------------------------"
	tar=$(rclone lsf remoto:proyecto $opc | grep '^respaldo-.*\.tar\.gz$' | sort | tail -n 1)
	if [ -z "$tar" ]
	then
		echo "No hay respaldos en Drive"
		read pausa
		return
	fi
	sql=$(echo $tar | sed 's/\.tar\.gz$/.sql/')
	if ! rclone lsf remoto:proyecto $opc | grep -q "^$sql$"
	then
		echo "El respaldo mas reciente no tiene su archivo sql"
		read pausa
		return
	fi
	echo "El respaldo mas reciente es $tar"
	read -p "Seguro que quiere restaurarlo? (y/n):" resp
	if [ "$resp" = "y" ]
	then
		mkdir -p ../restauracion
		rclone copyto remoto:proyecto/$tar ../restauracion/$tar $opc
		rclone copyto remoto:proyecto/$sql ../restauracion/$sql $opc
		ls -l ../restauracion
		tar -xzf ../restauracion/$tar -C ../restauracion --strip-components=1
		(cd .. && docker compose down)
		(cd ../restauracion && docker compose up -d --build web db)
		echo "Esperando a la base de datos"
		until (cd ../restauracion && docker compose exec -T db sh -c 'mysqladmin ping -h 127.0.0.1 -uroot -p"$MYSQL_ROOT_PASSWORD"' > /dev/null 2>&1)
		do
			sleep 2
		done
		(echo "CREATE DATABASE IF NOT EXISTS sigeru; USE sigeru;"; cat ../restauracion/$sql) | (cd ../restauracion && docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"')
		(cd ../restauracion && docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -D sigeru -e "SELECT COUNT(*) FROM usuario"')
		date +"El %d de %B %Y a las %T se restauro el respaldo $tar" >> log.txt
		echo "Restaurado, la web esta en el puerto 8083"
		echo "Para volver: docker compose down en restauracion y docker compose up -d en la carpeta principal"
		read pausa
	else
		echo "No se restauro nada"
	fi
}

op=1

while test $op -ne 0
do
echo "-----------------------------------------------------------------"
echo "1-Hacer respaldo"
echo "2-Listar respaldos"
echo "3-Restaurar el respaldo mas reciente"
echo "0-Salir"
read -p "Opcion:" op

case $op in
	1)Hacer;;
	2)Listar;;
	3)Restaurar;;
	0)exit;;
	*)echo "Seleccionaste mal";;
esac
done
