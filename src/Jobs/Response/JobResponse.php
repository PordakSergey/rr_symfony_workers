<?php

namespace Rr\Bundle\Workers\Jobs\Response;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
readonly class JobResponse
{
    /**
     * @param string $id
     * @param mixed $result
     */
    public function __construct(
        protected string $id,
        protected mixed  $result,
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