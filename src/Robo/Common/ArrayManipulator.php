<?php

namespace DigitalPolygon\Polymer\Robo\Common;

use Dflydev\DotAccessData\Data;

/**
 * Utility class for manipulating arrays.
 */
class ArrayManipulator
{
    /**
     * Merges arrays recursively while preserving.
     *
     * @param array $array1
     *   The first array.
     * @param array $array2
     *   The second array.
     *
     * @return array
     *   The merged array.
     *
     * @see http://php.net/manual/en/function.array-merge-recursive.php#92195
     */
    public static function arrayMergeRecursiveDistinct(
        array &$array1,
        array &$array2,
    ) {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursiveDistinct(
                    $merged[$key],
                    $value
                );
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    /**
     * Converts dot-notated keys to proper associative nested keys.
     *
     * E.g., [drush.alias => 'self'] would be expanded to
     * ['drush' => ['alias' => 'self']]
     *
     * @param array $array
     *   The array containing unexpanded dot-notated keys.
     *
     * @return array
     *   The expanded array.
     */
    public static function expandFromDotNotatedKeys(array $array)
    {
        $data = new Data();

        // @todo Make this work at all levels of array.
        foreach ($array as $key => $value) {
            $data->set($key, $value);
        }

        return $data->export();
    }

    /**
     * Flattens a multidimensional array to a flat array with dot-notated keys.
     *
     * This is the inverse of expandFromDotNotatedKeys(), e.g.,
     * ['drush' => ['alias' => 'self']] would be flattened to
     * [drush.alias => 'self'].
     *
     * @param array<string, array> $array
     *   The multidimensional array.
     *
     * @return array<string, mixed>
     *   The flattened array.
     */
    public static function flattenToDotNotatedKeys(array $array)
    {
        return self::flattenMultidimensionalArray($array, '.');
    }

    /**
     * Flattens a multidimensional array to a flat array, using custom glue.
     *
     * This is the inverse of expandFromDotNotatedKeys(), e.g.,
     * ['drush' => ['alias' => 'self']] would be flattened to
     * [drush.alias => 'self'].
     *
     * @param array<string, array<string,string>|bool> $array
     *   The multidimensional array.
     * @param string $glue
     *   The character(s) to use for imploding keys.
     *
     * @return array<string, mixed>
     *   The flattened array.
     */
    public static function flattenMultidimensionalArray(array $array, $glue): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array));
        $result = [];
        foreach ($iterator as $leafValue) {
            $keys = [];
            foreach (range(0, $iterator->getDepth()) as $depth) {
                $keys[] = $iterator->getSubIterator($depth)->key();
            }
            $result[implode($glue, $keys)] = $leafValue;
        }

        return $result;
    }

    /**
     * Converts a multi-dimensional array to a human-readable flat array.
     *
     * Used primarily for rendering tables via Symfony Console commands.
     *
     * @param array<mixed, mixed> $array
     *   The multi-dimensional array.
     *
     * @return array<int<0, max>, array<int, int|string>>
     *   The human-readble, flat array.
     */
    public static function convertArrayToFlatTextArray(array $array)
    {
        $rows = [];
        $max_line_length = 60;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $flattened_array = self::flattenToDotNotatedKeys($value);
                foreach ($flattened_array as $sub_key => $sub_value) {
                    if ($sub_value === true) {
                        $sub_value = 'true';
                    } elseif ($sub_value === false) {
                        $sub_value = 'false';
                    } elseif (!is_string($sub_value) || $sub_value === null) {
                        $sub_value = '';
                    }
                    $rows[] = [
                        "$key.$sub_key",
                        wordwrap($sub_value, $max_line_length, "\n", true),
                    ];
                }
            } else {
                if ($value === true) {
                    $contents = 'true';
                } elseif ($value === false) {
                    $contents = 'false';
                } elseif ($value === null || !is_string($value)) {
                    $contents = '';
                } else {
                    $contents = wordwrap($value, $max_line_length, "\n", true);
                }
                $rows[] = [$key, $contents];
            }
        }

        return $rows;
    }
}
