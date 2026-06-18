<?php

namespace Rr\Bundle\Workers\Jobs\Services\Mapper;

use Rr\Bundle\Workers\Jobs\Contracts\Services\Map\JobMapInterface;

class JobsMapper implements JobMapInterface
{
    /**
     * @return array
     */
    public function getAll(): array
    {
        return [];
    }

    /**
     * @param string $name
     * @return string
     */
    public function getByName(string $name): string
    {
        return '';
    }
}