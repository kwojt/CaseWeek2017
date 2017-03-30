<?php
// Główna paczka
$time = microtime(true);

// TODO Umieścić wszystko w wektorze i wywalać z bazy, żeby nie przeszukiwać
$rule = json_decode( file_get_contents( $argv[2]), true);
$base = json_decode( file_get_contents( $argv[1]), true);
      
$result = [];
$found = 0;

// Przeszukuje całą bazę
foreach ($base as $productFind) {

    $wanted = true;

    foreach ($rule["findProducts"] as $arg) {
        
        if ($arg["equals"] == "any" && isset($productFind["parameters"][$arg["parameter"]])) {
            continue;
        }

        if ($arg["equals"] == "is empty" && !isset($productFind["parameters"][$arg["parameter"]])) {
            continue;
        }
        
        if (isset($productFind["parameters"][$arg["parameter"]]) && $productFind["parameters"][$arg["parameter"]] == $arg["equals"]) {
            continue;
        }

        $wanted = false;
        break;
    }

    // Jeśli produkt nie jest poszukiwany to go olewam
    if(!$wanted) continue;
    else $found++;
    // W innym przypadku...
    // Tworzę tablicę dla dopasowań
    $result[$productFind["symbol"]] = [];
    // W podobny sposób przeszukuje całą bazę -_-

    foreach ($base as $productMatch) {

        if ($productFind["id"] == $productMatch["id"]) continue;

        $wanted2 = true;
        foreach ($rule["matchProducts"] as $arg2) {
          
            if ($arg2["equals"] == "any" && isset($productMatch["parameters"][$arg2["parameter"]])) {
                continue;
            }
        
            if ($arg2["equals"] == "is empty" && !isset($productMatch["parameters"][$arg2["parameter"]])) {
                continue;
            }

            if (!isset($productMatch["parameters"][$arg2["parameter"]])) {
                $wanted2 = "false";
                break;
            }

            if ($arg2["equals"] == "this" && $productFind["parameters"][$arg2["parameter"]] == $productMatch["parameters"][$arg2["parameter"]]) {
                continue;
            }
        
            if ($productMatch["parameters"][$arg2["parameter"]] == $arg2["equals"]) {
                continue;
            }

            $wanted2 = false;
            break;
        }

        // Jeśli produkt nie pasuje
        if (!$wanted2) continue;
        // W innym przypadku...
        // Dodaje ID produktu, który pasuje, do tabelki
        array_push($result[$productFind["symbol"]], $productMatch["symbol"]);
    }
}

echo json_encode($result);

$time = microtime(true) - $time;