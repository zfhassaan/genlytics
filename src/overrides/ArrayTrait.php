<?php

namespace zfhassaan\genlytics\overrides;

/**
 * Provides basic array helper methods.
 *
 * @internal
 */
trait ArrayTrait
{
    /**
     * Pluck a value out of an array.
     *
     * @param string $key
     * @param array $arr
     * @param bool $isRequired
     * @return mixed|null
     * @throws \InvalidArgumentException
     */
    private function pluck(string $key, array &$arr, bool $isRequired = true)
    {
        if (!array_key_exists($key, $arr)) {
            if ($isRequired) {
                throw new \InvalidArgumentException(
                    "Key $key does not exist in the provided array."
                );
            }

            return null;
        }

        $value = $arr[$key];
        unset($arr[$key]);
        return $value;
    }

    /**
     * Pluck a subset of an array.
     *
     * @param array $keys
     * @param array $arr
     * @return array
     */
    private function pluckArray(array $keys, array &$arr)
    {
        $values = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $arr)) {
                $values[$key] = $this->pluck($key, $arr, false);
            }
        }

        return $values;
    }

    /**
     * Determine whether given array is associative.
     *
     * @param array $arr
     * @return bool
     */
    private function isAssoc(array $arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Just like array_filter(), but preserves falsey values except null.
     *
     * @param array $arr
     * @return array
     */
    private function arrayFilterRemoveNull(array $arr)
    {
        return array_filter($arr, function ($element) {
            if (!is_null($element)) {
                return true;
            }

            return false;
        });
    }

    /**
     * Return a subset of an array, like pluckArray, without modifying the original array.
     *
     * @param array $keys
     * @param array $arr
     * @return array
     */
    private function subsetArray(array $keys, array $arr)
    {
        return array_intersect_key(
            $arr,
            array_flip($keys)
        );
    }
}
