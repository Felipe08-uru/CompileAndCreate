Crear() {
	echo "------------------------------------------------------------"
	read -p "Ingrese nombre de Usuario" nomUsu
	read -p "Ingrese en que grupo meterlo" nomGru
	echo "felipeadrianjuan" | sudo -S useradd -g $nomGru -m -s /bin/bash  $nomUsu
	date +"El %d de %B %Y a las %T se creo el usuario $nomUsu en el grupo $nomGru" >> log.txt
}

Borrar() {
	echo "--------------------------------------------------------"
	read -p "Ingrese nombre de Usuario" nomUsu
	echo "felipeadrianjuan" | sudo -S userdel -r $nomUsu 2> /dev/null
	date +"El %d de %B %Y a las %T se borro el usuario $nomUsu" >> log.txt

}

Bloquear() {
	echo "-------------------------------------------------------"
	read -p "Ingrese nombre del usuario" nomUsu
	echo "felipeadrianjuan" | sudo -S passwd -l $nomUsu
	date +"El %d de %B %Y a las %T se bloqueo el usuario $nomUsu" >> log.txt

}

Desbloquear() {
	echo "----------------------------------------------------------"
	read -p "Ingrese nombre del usuario" nomUsu
	echo "felipeadrianjuan" | sudo -S passwd -u $nomUsu
	date +"El %d de %B %Y a las %T se desbloqueo el usuario $nomUsu" >> log.txt

}

Fijar() {
	echo "------------------------------------------------------------"
	read -p "Ingrese el nombre del usuario" nomUsu
	read -p "Ingrese su nueva contraseña:" cont
	echo $cont  | sudo passwd -s $nomUsu
	date +"El %d de %B %Y a las %T se fijo la contraseña para el usuario $nomUsu" >> log.txt

}
Asignar() {
	echo "-------------------------------------------------------------"
	read -p "Ingrese nombre del usuario" nomUsu
	read -p "Ingrese nombre del grupo a asignarle" nomGru
	echo "felipeadrianjuan" | sudo -S usermod -a -G $nomGru $nomUsu
	date +"El %d de %B %Y a las %T se le asigno al usuario $nomUsu el grupo $nomGru" >> log.txt

}
Desasignar() {
	echo "--------------------------------------------------------------"
	read -p "Ingrese nombre del usuario" nomUsu
	read -p "Ingrese nombre del grupo a borrarle" nomGru
	echo "felipeadrianjuan" | sudo -S gpasswd -d $nomUsu $nomGru
	date +"El %d de %B %Y a las %T se le desasigno el grupo $nomGru al usuario $nomUsu" >> log.txt

}


op=1

while test $op -ne 0
do
echo "-----------------------------------------------------------------"
echo "1-Crear Usuario"
echo "2-Eliminar Usuario"
echo "3-Bloquear Usuario"
echo "4-Debloquear Usuario"
echo "5-Fijarle contraseña a un Usuario"
echo "6-Asignarle grupo secundario a Usuario"
echo "7-Desasignarle grupo a Usuario"
echo "0-salir"
read -p "Opcion:" op

case $op in
	1)Crear;;
	2)Borrar;;
	3)Bloquear;;
	4)Desbloquear;;
	5)Fijar;;
	6)Asignar;;
	7)Desasignar;;
	0)exit;;
	*)echo "Seleccionaste mal";;
esac
done
