<?php

header("Content-Type: text/plain; charset=UTF-8");

$rows = file('config.txt', FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);
try {
    var_dump(parse($rows));
} catch (ErrorException $e) {
    print "Error: {$e->getMessage()}\n";
}
unset($rows);


/** Main function to process the file line by line
 * @param array $rows
 * @throws ErrorException
 * @return array
 */
function parse(array $rows): array
{
    $config = [];

    foreach ($rows as $row) {
        // Comments starts with #, inline comments with the config string afterwards starts with #####
        if ((strpos($row, '#') !== 0 || strpos($row, '#####') === 0) && trim($row)) {
            [$attributes, $value] = preg_split('/\s*=\s*/', $row, 2);
            if ($attributes && $value) {
                // This handles rows that start with an inlined comment
                $attributes = explode(' ', $attributes);
                $attributes = array_pop($attributes);
                // Now we can parse the attributes
                $attributes = explode('.', trim($attributes));
                $config = array_merge_recursive($config, convert($attributes, $value));
            } else {
                // Either attribute or value is missing
                throw new ErrorException("Incorrect file formatting");
            }
        }
    }

    return $config;
}

/** Walks through the given array and creates multidimensional array
 * @param array $attributes
 * @param string $value
 * @return array
 */
function convert(array $attributes, string $value): array
{
    $array = [];
    $key = array_shift($attributes);
    if (!isset($attributes[0])) {
        $array[$key] = cast($value);
    } else {
        $array[$key] = convert($attributes, $value);
    }
    return $array;
}

/** Casts a proper type
 * @param string $value
 * @return array|bool|int|string|string[]
 */
function cast(string $value)
{
    if (strtolower($value) == 'false') {
        return false;
    } elseif (strtolower($value) == 'true') {
        return true;
    } elseif (is_numeric($value)) {
        return (int)$value;
    } else {
        // Simply remove unnecessary double-quotes and ignore if a string is inconsistently quoted
        return str_replace('"', '', $value);
    }
}
