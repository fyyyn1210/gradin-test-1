<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

class CourierCollection extends ResourceCollection
{
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                ...Arr::except($default['meta'], ['links', 'path']),
                'has_prev' => $paginated['current_page'] > 1,
                'has_next' => $paginated['current_page'] < $paginated['last_page'],
            ],
        ];
    }
}
