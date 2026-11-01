Crear() {
	echo "------------------------------------------------------------"
	read -p "Ingrese nombre del grupo": nomGru
	if grep -q $nomGru grupos.txt;
	then
		echo "El grupo ya existe"
		read pausa
	else
		echo "felipeadrianjuan" | sudo -S groupadd $nomGru
		date +"El %d de %B %Y a las %T se creo el grupo $nomGru" >> log.txt
		echo $nomGru >> grupos.txt
	fi
}

Borrar(){
	echo "--------------------------------------------------------"
	read -p "Ingrese nombre de Grup:o" nomGru
	if grep -q $nomGru grupos.txt;
	then
			echo "felipeadrianjuan" | sudo -S  groupdel $nomGru
			date +"El %d de %B %Y a las %T se borro el grupo $nomGru" >> log.txt
			sed -i "/$nomGru/d" grupos.txt
	else
		echo "Ese grupo no existe"
		read pausa
	fi
}
Listar(){
	echo "Presione q para salir"
	less grupos.txt
}
op=1

while test $op -ne 0
do
echo "-----------------------------------------------------------------"
echo "1-Crear Grupo"
echo "2-Eliminar Grupo"
echo "3-Listar Grupos"
echo "0-salir"
read -p "Opcion:" op

case $op in
	1)Crear;;
	2)Borrar;;
	3)Listar;;
	0)exit;;
	*)echo "Seleccionaste mal";;
esac
done
