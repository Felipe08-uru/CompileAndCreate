Usuarios() {
	mysql -D sigeru -e "SELECT * FROM Usuario"
	date +"El %d de %B %Y a las %T se revisaron los usuarios de sigeru" >> log.txt
}
Camiones() {
	mysql -D sigeru -e "SELECT * FROM Camion"
	date +"El %d de %B %Y a las %T se revisaron los camiones de sigeru" >> log.txt

}
Contenedores() {
	mysql -D sigeru -e "SELECT * FROM Contenedor"
	date +"El %d de %B %Y a las %T se revisaron los contenedores de sigeru" >> log.txt

}
Centros() {
	mysql -D sigeru -e "SELECT * FROM Centro"
	date +"El %d de %B %Y a las %T se revisaron los centros de sigeru" >> log.txt

}
Herramientas() {
	mysql -D sigeru -e "SELECT * FROM Centro_Herramientas"
	date +"El %d de %B %Y a las %T se revisaron las herramientas de sigeru" >> log.txt

}
Incidencias() {
	mysql -D sigeru -e "SELECT * FROM Incidencia"
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

