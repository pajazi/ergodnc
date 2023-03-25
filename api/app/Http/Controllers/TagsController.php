<?php

namespace App\Http\Controllers;

use App\Http\Resources\TagResource;
use App\Models\Tags;

class TagsController extends Controller
{
    public function __invoke()
    {
        return TagResource::collection(
            Tags::all()
        );
    }
}
