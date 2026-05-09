<?php

namespace Modules\Tournaments\DrawEngine\Contracts;

use Illuminate\Support\Collection;
use Modules\Tournaments\Models\TournamentCategory;

interface DrawGeneratorInterface
{
    public function generate(TournamentCategory $event, Collection $participants, array $settings = []): array;
}
