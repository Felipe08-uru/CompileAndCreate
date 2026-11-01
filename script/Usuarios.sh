Crear() {
	echo "------------------------------------------------------------"
	read -p "Ingrese nombre de Usuario:" nomUsu
	if grep -q $nomUsu usuarios.txt;
	then
		echo "Ese nombre ya existe, pruebe otro"
		read pausa
	else
	read -p "Ingrese en que grupo meterlo:" nomGru
	fi
	if grep -q $nomGru grupos.txt;
	then
	echo "felipeadrianjuan" | sudo -S useradd -g $nomGru -m -s /bin/bash  $nomUsu
	date +"El %d de %B %Y a las %T se creo el usuario $nomUsu en el grupo $nomGru" >> log.txt
	echo $nomUsu >> usuarios.txt
	else
		echo "Ese grupo no existe, creelo o intente con otro"
		read pausa
	fi
}

Borrar() {
	echo "--------------------------------------------------------"
	read -p "Ingrese nombre de Usuario:" nomUsu
	if grep -q $nomUsu usuarios.txt;
	then
		echo "felipeadrianjuan" | sudo -S userdel -r $nomUsu 2> /dev/null
		date +"El %d de %B %Y a las %T se borro el usuario $nomUsu" >> log.txt
		sed -i "/$nomUsu/d" usuarios.txt
	else
		echo "Ese usuario no existe"
	fi
}

Bloquear() {
	echo "-------------------------------------------------------"
	read -p "Ingrese nombre del usuario:" nomUsu
	if grep -q $nomUsu usuarios.txt;
	then
		echo "felipeadrianjuan" | sudo -S passwd -l $nomUsu
		date +"El %d de %B %Y a las %T se bloqueo el usuario $nomUsu" >> log.txt
	else
		echo "Ese usuario no existe"
	fi
}

Desbloquear() {
	echo "----------------------------------------------------------"
	read -p "Ingrese nombre del usuario:" nomUsu
	if grep -q $nomUsu usuarios.txt;
	then
		echo "felipeadrianjuan" | sudo -S passwd -u $nomUsu
		date +"El %d de %B %Y a las %T se desbloqueo el usuario $nomUsu" >> log.txt
	else	
		echo "Ese usuario no existe"
	fi
}

Fijar() {
	echo "------------------------------------------------------------"
	read -p "Ingrese el nombre del usuario:" nomUsu
	if grep -q $nomUsu usuarios.txt;
	then
		read -p "Ingrese su nueva contraseña:" cont
		echo $cont  | sudo passwd -s $nomUsu
		date +"El %d de %B %Y a las %T se fijo la contraseña para el usuario $nomUsu" >> log.txt
	else
		echo "El usuario no existe"
	fi
}
Asignar() {
	echo "-------------------------------------------------------------"
	read -p "Ingrese nombre del usuario:" nomUsu
	if grep -q $nomUsu usuarios.txt;
	then
		read -p "Ingrese nombre del grupo a asignarle:" nomGru
		if grep -q $nomGru grupos.txt;
		then
			if getent group $nomGru | grep $nomUsu;
			then
				echo "El usuario ya esta en ese grupo"
			else
				echo "felipeadrianjuan" | sudo -S usermod -a -G $nomGru $nomUsu
				date +"El %d de %B %Y a las %T se le asigno al usuario $nomUsu el grupo $nomGru" >> log.txt
			fi
		else
			echo "El grupo no existe"
		fi
	else 
		echo "El usuario no existe"
	fi
}
Desasignar() {
	echo "--------------------------------------------------------------"
	read -p "Ingrese nombre del usuario:" nomUsu
	if grep -q $nomUsu usuarios.txt;
	then
		read -p "Ingrese nombre del grupo a borrarle:" nomGru
		if grep -q $nomGru grupos.txt;
		then
			if getent group $nomGru | grep $nomUsu;
			then
				echo "felipeadrianjuan" | sudo -S gpasswd -d $nomUsu $nomGru
				date +"El %d de %B %Y a las %T se le desasigno el grupo $nomGru al usuario $nomUsu" >> log.txt
			else
				echo "El usuario no esta en ese grupo"
			fi
		else
			echo "Ese grupo no existe"
		fi
	else
		echo "El usuario no existe"
	fi
}
Listar() {
	echo "Presione q para salir"
	less usuarios.txt
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
echo "8-Listar usuarios"
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
	8)Listar;;
	0)exit;;
	*)echo "Seleccionaste mal";;
esac
done
