<?php

namespace App\Services;

use App\Models\Tag;

interface ITagService
{
    public function getAllTags(): array;

    public function getTagById(int $id): Tag;

    public function createTag(Tag $tag): string;

    public function updateTag(Tag $tag): string;

    public function deleteTag(Tag $tag): string;
}