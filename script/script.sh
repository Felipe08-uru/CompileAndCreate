
op=1

while test $op -ne 0
do
echo "----------------------------------------------------------------------"
echo "Seleccione lo que quiera hacer"
echo "1.Gestion de Usuarios"
echo "2.Gestion de Grupos"
echo "3.Ver ip del servidor"
echo "4.Gestion de Firewall"
echo "5.Gestion de Base de Datos"
echo "6.Gestion de Logs"
echo "0.Salir"
read -p "Opcion:" op

case $op in
	1)sh Usuarios.sh;;
	2)sh Grupos.sh;;
	3)sh Redes.sh;;
	4)sh Firewall.sh;;
	5)sh BaseDatos.sh;;
	6)sh Log.sh;;
	0)exit;;
	*)echo "Pusiste cualquiera";;
esac
done
