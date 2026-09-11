#!/bin/bash

# Skript pro kontrolu syntaxe PHP souborů

# Definujte adresáře, které chcete zkontrolovat
DIRECTORIES="app"

# Inicializace proměnné pro detekci chyby
ERROR_FOUND=0

# Projde všechny PHP soubory v daných adresářích a zkontroluje jejich syntaxi
for DIR in $DIRECTORIES; do
    if [ -d "$DIR" ]; then
        find "$DIR" -name "*.php" -print0 | xargs -0 -n1 -P8 php -l || ERROR_FOUND=1
    else
        echo "Directory $DIR does not exist."
    fi
done

# Pokud byla nalezena chyba, skript vrátí kód 1 (pipeline selže)
if [ $ERROR_FOUND -ne 0 ]; then
    echo "Syntax errors detected."
    exit 1
else
    echo "No syntax errors detected."
fi