Ver (){
	sudo ufw status
}

Habilitar (){
	read -p "ingrese puerto a habilitar:" puerto
	if sudo ufw allow $puerto >> /dev/null
	then
	echo "Se ha habilitado el puerto $puerto"
	date +"El %d de %B %Y a las %T se habilito el puerto $puerto" >> log.txt
	fi
}

Deshabilitar (){
	read -p "Ingrese puerto a deshabilitar:" puerto
	if sudo ufw deny $puerto >> /dev/null
	then
	echo "Se ha deshabilitado el puerto $puerto"
	date +"El %d de %B %Y a las %T se deshabilito el puerto $puerto" >> log.txt
	fi
}

op=1

while test $op -ne 0
do
echo "----------------------------------------------------------------------"
echo "Seleccione lo que quiera hacer"
echo "1.Ver puertos"
echo "2.Habilitar un puerto"
echo "3.Deshabilitar un puerto"
echo "0.Salir"
read -p "Opcion:" op

case $op in
	1)Ver;;
	2)Habilitar;;
	3)Deshabilitar;;
	0)exit;;
	*)echo "Pusiste cualquiera";;
esac
done
