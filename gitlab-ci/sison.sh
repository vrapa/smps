#!/bin/bash

if [ ! -e "sis_on.txt" ]
then
  echo 'CHYBA: Neexistuje soubor "sis_on.txt". Patrne jste jeho smazani omylem commitl(a).'
  echo 'Je treba vytvorit soubor sis_on.txt v koreni sis, aby jeho obsahem byl nasledujici text:'
  echo '"//přejmenováním tohoto souboru zrušíte namapování sis 4.1 v apache" bez uvozovek + konec radku'
  exit 1
fi