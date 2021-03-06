<?php

$result = [];
$products = json_decode(file_get_contents($argv[1]), true);
$rule = json_decode(file_get_contents($argv[2]), true);

$index = create_index($products);

$findProducts = get_find_products($rule['findProducts']);
$findSearchResult = search($index, $findProducts);

$groups = create_groups($findSearchResult, $rule, $products);
foreach ($groups as $group) {
    $baseProductId = $group[0];

    $matchProducts = get_match_products($rule['matchProducts'], $products, $baseProductId);

    $matchSearchResult = search($index, $matchProducts);
    $matchSearchResultSymbols = [];

    foreach ($matchSearchResult as $entry) {
        $matchSearchResultSymbols[] = $products[$entry]['symbol'];
    }

    foreach ($group as $groupProductId) {
        $symbolKey = $products[$groupProductId]['symbol'];
        $result[$symbolKey] = $matchSearchResultSymbols;
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);

/**
 * @param array $products
 *
 * @return array
 */
function create_index(array $products): array
{
    $index = [];
    foreach ($products as $product) {
        foreach ($product['parameters'] as $name => $value) {
            $index[$name] ?? $index[$name] = ['all_ids' => []];
            $index[$name][$value] ?? $index[$name][$value] = [];

            $index[$name][$value][$product['id']] = $product['id'];
            $index[$name]['all_ids'][$product['id']] = $product['id'];
        }
    }
    return $index;
}

/**
 * @param array $criteria
 *
 * @return \Generator
 */
function get_find_products(array $criteria): \Generator
{
    foreach ($criteria as $criterion) {
        yield $criterion['parameter'] => $criterion['equals'];
    }
}

/**
 * @param array $findSearchResult
 * @param array $rule
 * @param array $products
 *
 * @return array
 */
function create_groups(array $findSearchResult, array $rule, array $products): array
{
    $groups = [];
    foreach ($findSearchResult as $productId) {
        $distinction = "";
        foreach ($rule['findProducts'] as $ruleSet) {
            if ($ruleSet['equals'] === 'any') {
                $distinction .= $ruleSet['parameter'] . $products[$productId]['parameters'][$ruleSet['parameter']];
            }
        }
        $groups[$distinction] ?? $groups[$distinction] = [];
        $groups[$distinction][] = $productId;
    }
    return $groups;
}

/**
 * @param array $criteria
 * @param array $products
 * @param string $productId
 *
 * @return \Generator
 */
function get_match_products(array $criteria, array $products, string $productId): \Generator
{
    foreach ($criteria as $criterion) {
        yield $criterion['parameter'] => $criterion['equals'] === 'this' ?
            $products[$productId]['parameters'][$criterion['parameter']] :
            $criterion['equals'];
    }
}

/**
 * @param array $index
 * @param \Generator $criteria
 *
 * @return array
 */
function search(array $index, \Generator $criteria): array
{
    $results = [];
    $emptyParameters = [[]];

    foreach ($criteria as $parameter => $value) {
        switch ($value) {
            case 'any':
                $results[] = $index[$parameter]['all_ids'];
                break;
            case 'is empty':
                $emptyParameters[] = $index[$parameter]['all_ids'];
                break;
            default:
                $results[] = $index[$parameter][$value];
        }
    }

    $searchResult = count($results) === 1 ?
        $results[0] :
        call_user_func_array('array_intersect_key', $results);

    if (count($emptyParameters) > 1) {
        $emptyParameters[0] = $searchResult;
        $searchResult = call_user_func_array('array_diff_key', $emptyParameters);
    }

    return $searchResult;
}
