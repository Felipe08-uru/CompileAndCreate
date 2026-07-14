nombre=""
edad=1
posicion=""

Crear (){
	read -p "Ingrese nombre del jugador" nombre
	read -p "Ingrese edad del jugador" edad
	read -p "Ingrese posicion del jugador" posicion
	echo "Se ha creador el jugador $nombre de $edad años que juega de $posicion"
}

Modificar (){
	echo "1.Nombre"
	echo "2.Edad"
	echo "3.Posicion"
	read -p "Ingrese que modificar" op
	case $op in
		1) read -p "Ingrese nuevo nombre" nombre;;
		2) read -p "Ingrese nueva edad" edad;;
		3) read -p "Ingrese nueva posicion" posicion;;
		*) echo "Has ingresado un numero no valido";;
	esac
}

Eliminar (){
	nombre=""
	edad=0
	posicion=""
}

Listar (){
	echo $nombre
	echo $edad
	echo $posicion
}




op=1

while test $op -ne 0
do
echo "----------------------------------------------------------------------"
echo "Seleccione lo que quiera hacer"
echo "1.Crear Jugador"
echo "2.Modificar Jugador"
echo "3.Eliminar Jugador"
echo "4.Listar"
echo "0.Salir"
read -p "Opcion:" op


case $op in
	1)Crear;;
	2)Modificar;;
	3)Eliminar;;
	4)Listar;;
	0)exit;;
	*)echo "Pusiste cualquiera";;
esac
done
