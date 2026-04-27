<?php

namespace Rr\Bundle\Workers\Jobs\Responce;

class JobResponse
{
    /**
     * @param string $id
     * @param mixed $result
     */
    public function __construct(
        protected readonly string $id,
        protected readonly mixed  $result,
    )
    {
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getResult(): mixed
    {
        return $this->result;
    }
}