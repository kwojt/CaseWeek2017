<?php

function search($index, $products, $query) 
{
    $results = [];
    $isEmpties = [];

    foreach ($query as $parameter => $value) {
        switch ($value) {
            case 'any':
                $results[] = $index[$parameter]['all_ids'];
                break;
            case 'is empty':
                $isEmpties[$parameter] = $parameter;
                break;
            default:
                $results[] = $index[$parameter][$value];
        }
    }

    $finalResult = null;

    if (count($results) == 1) {
        $finalResult = $results[0];
    } else {
        $finalResult = call_user_func_array('array_intersect_key', $results);
    }
    
    $finalResult = array_flip($finalResult);

    if (count($isEmpties) > 0) {
        $finalResult = array_filter($finalResult, function ($entry) use ($products, $isEmpties) {
            $interseciton = array_intersect_key($products[$entry]['parameters'], $isEmpties);
            return count($interseciton) == 0;
        });
    }

    return $finalResult;
}

$jsonData = file_get_contents("products.json");
$products = json_decode($jsonData, true);
$allIds = array_keys($products);
$index = [];

foreach ($products as $product) {
    foreach ($product['parameters'] as $parameterName => $parameterValue) {
        if (!array_key_exists($parameterName, $index)) {
            $index[$parameterName] = ['all_ids' => []];
        }
        if (!array_key_exists($parameterValue, $index[$parameterName])) {
            $index[$parameterName][$parameterValue] = [];
        }
        $index[$parameterName][$parameterValue][$product['id']] = $product['id'];
        $index[$parameterName]['all_ids'][$product['id']] = $product['id'];
    }
}

$totalTime = microtime(true);
$rule = json_decode(file_get_contents($argv[1]), true);

$findProducts = [];

foreach ($rule['findProducts'] as $criterion) {
    $findProducts[$criterion['parameter']] = $criterion['equals'];
}

$result = search($index, $products, $findProducts);

$jsonFinalResult = [];

foreach ($result as $baseProductId) {
    $symbolKey = $products[$baseProductId]['symbol'];
    $jsonFinalResult[$symbolKey] = [];
    $time = microtime(true);
    $matchProducts = [];

    foreach ($rule['matchProducts'] as $criterion) {
        switch ($criterion['equals']) {
            case 'this':
                $matchProducts[$criterion['parameter']] = $products[$baseProductId]['parameters'][$criterion['parameter']];
                break;
            default:
                $matchProducts[$criterion['parameter']] = $criterion['equals'];
        }
    }

    $searchTime = microtime(true);
    $matchSearchResult = search($index, $products, $matchProducts);
    $searchTime = microtime(true) - $searchTime;
    $time = microtime(true) - $time;

    foreach ($matchSearchResult as $entry) {
        $jsonFinalResult[$symbolKey][] = $products[$entry]['symbol'];
    }
}

$totalTime = microtime(true) - $totalTime;

echo "TOTAL TIME: " . $totalTime . "\n";
echo "RESULT COUNT: " . count($result) . "\n";

file_put_contents('result.json', json_encode($jsonFinalResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
