Ver() {
	less log.txt
}
Borrar() {
	echo "Presione q para salir" > log.txt
}

op=1

while test $op -ne 0
do
echo "-------------------------------------------------------------------------------------------------"
echo "1-Ver Log"
echo "2-Borrar Log"
echo "0-Salir"
read -p "Ingrese opcion:" op
case $op in
	1)Ver;;
	2)Borrar;;
	0)exit;;
	*)echo "Ingresaste cualquiera"
esac
done


