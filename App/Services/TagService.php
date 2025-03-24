<?php

namespace App\Services;

use App\Models\Tag;
use App\Services\CustomDb;
use PDO;

class TagService implements ITagService
{
    private $db;

    public function __construct(CustomDb $db)
    {
        $this->db = $db;
    }

    public function getAllTags(): array
    {
        $query = "SELECT * FROM Tags";
        $stmt = $this->db->executeSelectQuery($query);
        $tagsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($tagData) {
            return new Tag($tagData['id'], $tagData['name']);
        }, $tagsData);
    }

    public function getTagById(int $id): Tag
    {
        $query = "SELECT * FROM Tags WHERE id = :id";
        $stmt = $this->db->executeSelectQuery($query, [':id' => $id]);
        $tagData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tagData) {
            throw new \Exception("Tag with ID $id does not exist.");
        }

        return new Tag($tagData['id'], $tagData['name']);
    }

    public function createTag(Tag $tag): string
    {
        $query = "INSERT INTO Tags (name) VALUES (:name)";
        $bind = [':name' => $tag->name];
        $result = $this->db->executeQuery($query, $bind);

        if ($result) {
            return "Tag '{$tag->name}' successfully created.";
        }

        throw new \Exception("Failed to create the tag.");
    }

    public function updateTag(Tag $tag): string
    {
        $query = "UPDATE Tags SET name = :name WHERE id = :id";
        $bind = [
            ':name' => $tag->name,
            ':id' => $tag->id
        ];
        $result = $this->db->executeQuery($query, $bind);

        if ($result) {
            return "Tag with ID {$tag->id} successfully updated.";
        }

        throw new \Exception("Failed to update the tag with ID {$tag->id}.");
    }

    public function deleteTag(Tag $tag): string
    {
        $query = "DELETE FROM Tags WHERE id = :id";
        $bind = [':id' => $tag->id];
        $result = $this->db->executeQuery($query, $bind);

        if ($result) {
            return "Tag with ID {$tag->id} successfully deleted.";
        }

        throw new \Exception("Failed to delete the tag with ID {$tag->id}.");
    }
}