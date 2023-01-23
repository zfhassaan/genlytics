<?php

namespace zfhassaan\genlytics\overrides;

use Google\ApiCore\ValidationException;

/**
 * @internal
 */
trait ValidationTrait
{
    /**
     * @param array $arr Associative array
     * @param array $requiredKeys List of keys to check for in $arr
     * @return array Returns $arr for fluent use
     */
    public static function validate(array $arr, array $requiredKeys)
    {
        return self::validateImpl($arr, $requiredKeys, true);
    }

    /**
     * @param array $arr Associative array
     * @param array $requiredKeys List of keys to check for in $arr
     * @return array Returns $arr for fluent use
     */
    public static function validateNotNull(array $arr, array $requiredKeys)
    {
        return self::validateImpl($arr, $requiredKeys, false);
    }

    private static function validateImpl($arr, $requiredKeys, $allowNull)
    {
        foreach ($requiredKeys as $requiredKey) {
            $valid = array_key_exists($requiredKey, $arr)
                && ($allowNull || !is_null($arr[$requiredKey]));
            if (!$valid) {
                throw new ValidationException("Missing required argument $requiredKey");
            }
        }
        return $arr;
    }

    /**
     * @param string $filePath
     * @throws ValidationException
     */
    private static function validateFileExists(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new ValidationException("Could not find specified file: $filePath");
        }
    }
}
