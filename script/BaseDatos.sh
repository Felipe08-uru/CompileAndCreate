cont=felipeadrianjuan
user=root

Usuarios() {
	mysql -u $user -p $cont -D sigeru -e "SELECT * FROM usuario"
	date +"El %d de %B %Y a las %T se revisaron los usuarios de sigeru" >> log.txt
}
Camiones() {
	mysql -u $user -p $cont -D sigeru -e "SELECT * FROM camion"
	date +"El %d de %B %Y a las %T se revisaron los camiones de sigeru" >> log.txt

}
Contenedores() {
	mysql -u $user -p $cont -D sigeru -e "SELECT * FROM contenedor"
	date +"El %d de %B %Y a las %T se revisaron los contenedores de sigeru" >> log.txt

}
Centros() {
	mysql -u $user -p $cont -D sigeru -e "SELECT * FROM centro"
	date +"El %d de %B %Y a las %T se revisaron los centros de sigeru" >> log.txt

}
Herramientas() {
	mysql -u $user -p $cont -D sigeru -e "SELECT * FROM herramienta"
	date +"El %d de %B %Y a las %T se revisaron las herramientas de sigeru" >> log.txt

}
Incidencias() {
	mysql -u $user -p $cont -D sigeru -e "SELECT * FROM incidencia"
	date +"El %d de %B %Y a las %T se revisaron las incidencias de sigeru" >> log.txt
}
Respaldo() {
	mysqldump sigeru > respaldo.sql
	date +"El %d de %B %Y a las %T se respaldo sigeru" >> log.txt

}

op=1

while test $op -ne 0
do
echo "1-Ver Usuarios"
echo "2-Ver Camiones"
echo "3-Ver Contenedores"
echo "4-Ver Centros"
echo "5-Ver Herramientas"
echo "6-Ver Incidencias"
echo "7-Respaldar base"
echo "0-Salir"
read -p "Ingrese opcion:" op
case $op in
	1)Usuarios;;
	2)Camiones;;
	3)Contenedores;;
	4)Centros;;
	5)Herramientas;;
	6)Incidencias;;
	7)Respaldo;;
	0)exit;;
	*)echo "Ingresaste cualquiera"
esac
done

