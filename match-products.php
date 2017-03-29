<?php
// Główna paczka
$time = microtime(true);

// TODO Umieścić wszystko w wektorze i wywalać z bazy, żeby nie przeszukiwać
$rule = json_decode( file_get_contents( $argv[1]), true);
$base = json_decode( file_get_contents( $argv[2]), true);
$result = [];

// Przeszukuje całą bazę
foreach ($base as $productFind) {

    // Zakładam, że dany produkt jest tym, którego szukamy
    $wanted = true;

    // Sprawdzam, czy produkt na prawdę jest poszukiwany
    foreach ($rule["findProducts"] as $arg) {
        
        if ($arg["equals"] == "any" && isset($productFind["parameters"][$arg["parameter"]])) {
            continue;
        }

        if ($arg["equals"] == "is empty" && !isset($productFind["parameters"][$arg["parameter"]])) {
            continue;
        }
        
        // Inne wymagania, jeśli nie będzie choć jednego to nie chcemy tego produktu
        if (isset($productFind["parameters"][$arg["parameter"]]) && $productFind["parameters"][$arg["parameter"]] == $arg["equals"]) {
            continue;
        }

        $wanted = false;
    }

    // Jeśli produkt nie jest poszukiwany to go olewam
    if($wanted)
    echo "Pierwsze wanted: " . ($wanted ? "true" : "false");
    if(!$wanted) continue;
    // W innym przypadku...
    // Tworzę tablicę dla dopasowań
    $result[$productFind["symbol"]] = [];
    // W podobny sposób przeszukuje całą bazę -_-

    foreach ($base as $productMatch) {

        // TODO Można to zrobić dopiero na końcu usuwając sam wynik
        // Ale chyba tutaj będzie szybciej
        if ($productFind["id"] == $productMatch["id"]) continue;

        $wanted = true;
        foreach ($rule["findProducts"] as $arg) {

            // Jeśli nie ma takiego parametru, a miał być
            if ($arg["equals"] == "any" && !isset($productMatch["parameters"][$arg["parameter"]])) {
                $wanted = false;
                break;
            }
        
            // Jeśli jest taki parametr, a miało go nie być
            if ($arg["equals"] == "is empty" && isset($productMatch["parameters"][$arg["parameter"]])) {
                $wanted = false;
                break;
            }

            // Jeśli parametry miały być identyczne, a nie są
            if ($arg["equals"] == "this" && $productFind["parameters"][$arg["parameter"]] != $productMatch["parameters"][$arg["parameters"]]) {
                $wanted = false;
                break;
            }
        
            // Inne wymagania, jeśli nie będzie choć jednego to nie chcemy tego produktu
            if ($productMatch["parameters"][$arg["parameter"]] != $arg["equals"]) {
                $wanted = false;
                break;
            }

            // Jeśli spełni wszystkie wymagania to spoko, nic się nie dzieje
            // Oczywiście w tej chwili
        }

        // Jeśli produkt nie pasuje
        if (!$wanted) continue;
        // W innym przypadku...
        // Dodaje ID produktu, który pasuje, do tabelki
        array_push($result[$productFind["symbol"]], $productMatch["symbol"]);
    }
}

$time = microtime(true) - $time;
echo "\nTime used: " . $time . "\n";
echo "\n";
