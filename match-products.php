<?php
// Główna paczka
$time = microtime(true);

// TODO Umieścić wszystko w wektorze i wywalać z bazy, żeby nie przeszukiwać
$rule = json_decode( file_get_contents( $argv[1]), true);
$base = json_decode( file_get_contents( $argv[2]), true);

// Przeszukuje całą bazę
foreach ($base as $product) {

    // Zakładam, że dany produkt jest tym, którego szukamy
    $wanted = true;

    // Sprawdzam, czy produkt na prawdę jest poszukiwany
    foreach ($rule["findProducts"] as $arg) {

        // Jeśli nie ma takiego parametru, a miał być
        if ($arg["equals"] == "any" && !isset($product["parameters"][$arg["parameter"]])) {
            $wanted = false;
            break;
        }

        // Jeśli jest taki parametr, a miało go nie być
        if ($arg["equals"] == "is empty" && isset($product["parameters"][$arg["parameter"]])) {
            $wanted = false;
            break;
        }
        
        // Inne wymagania, jeśli nie będzie choć jednego to nie chcemy tego produktu
        if ($product["parameters"][$arg["parameter"]] != $arg["equeals"]) {
            $wanted = false;
            break;
        }

        // Jeśli spełni wszystkie wymagania to spoko, nic się nie dzieje
    }

    // Jeśli produkt nie jest poszukiwany to go olewam
    if(!$wanted) continue;
}

$time = microtime(true) - $time;
echo "\nTime used: " . $time . "\n";
echo "\n";
