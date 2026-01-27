<?php

namespace Rr\Bundle\Workers\Traits;

use Iterator;

trait GeneratorConsumes
{
    private function consumes(\Iterator $gen): void
    {
        foreach ($gen as $_) {}
    }
}