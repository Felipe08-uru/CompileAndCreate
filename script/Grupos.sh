Crear() {
	echo "------------------------------------------------------------"
	read -p "Ingrese nombre del grupo" nomGru
	echo "felipeadrianjuan" | sudo -S groupadd $nomGru
	date +"El %d de %B %Y a las %T se creo el grupo $nomGru" >> log.txt 
}

Borrar(){
	echo "--------------------------------------------------------"
	read -p "Ingrese nombre de Grupo" nomGru
	echo "felipeadrianjuan" | sudo -S  groupdel $nomGru
	date +"El %d de %B %Y a las %T se borro el grupo $nomGru" >> log.txt

}

op=1

while test $op -ne 0
do
echo "-----------------------------------------------------------------"
echo "1-Crear Grupo"
echo "2-Eliminar Grupo"
echo "0-salir"
read -p "Opcion:" op

case $op in
	1)Crear;;
	2)Borrar;;
	0)exit;;
	*)echo "Seleccionaste mal";;
esac
done
