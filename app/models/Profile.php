<?php

declare(strict_types=1);

final class Profile
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByUserId(int $userId): ?array
    {
        $statement = $this->db->prepare('
            SELECT
                u.id AS user_id,
                u.email,
                u.role,
                u.is_active,
                p.full_name,
                p.phone,
                p.city,
                p.region,
                p.country,
                p.bio,
                p.avatar_path,
                p.skills_summary,
                p.created_at,
                p.updated_at
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE u.id = :user_id
            LIMIT 1
        ');
        $statement->execute(['user_id' => $userId]);
        $profile = $statement->fetch();

        return $profile ?: null;
    }

    public function updateByUserId(int $userId, array $data): void
    {
        $statement = $this->db->prepare('
            INSERT INTO profiles (user_id, full_name, phone, city, region, country, bio, avatar_path, skills_summary, created_at, updated_at)
            VALUES (:user_id, :full_name, :phone, :city, :region, :country, :bio, :avatar_path, :skills_summary, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                full_name = VALUES(full_name),
                phone = VALUES(phone),
                city = VALUES(city),
                region = VALUES(region),
                country = VALUES(country),
                bio = VALUES(bio),
                avatar_path = VALUES(avatar_path),
                skills_summary = VALUES(skills_summary),
                updated_at = NOW()
        ');

        $statement->execute([
            'user_id' => $userId,
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'city' => $data['city'],
            'region' => $data['region'],
            'country' => $data['country'],
            'bio' => $data['bio'],
            'avatar_path' => $data['avatar_path'],
            'skills_summary' => $data['skills_summary'],
        ]);
    }

    public function findTaskerById(int $taskerId): ?array
    {
        $profile = $this->findByUserId($taskerId);

        if ($profile === null || (string) $profile['role'] !== 'tasker') {
            return null;
        }

        return $profile;
    }
}
