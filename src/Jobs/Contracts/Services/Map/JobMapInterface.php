<?php

namespace Rr\Bundle\Workers\Jobs\Contracts\Services\Map;

interface JobMapInterface
{
    /**
     * @return array
     */
    public function getAll() : array;

    /**
     * @param string $name
     * @return object
     */
    public function getByName(string $name) : string;
}