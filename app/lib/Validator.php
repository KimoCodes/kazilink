<?php

declare(strict_types=1);

final class Validator
{
    public static function trim(array $input): array
    {
        $trimmed = [];

        foreach ($input as $key => $value) {
            $trimmed[$key] = is_string($value) ? trim($value) : $value;
        }

        return $trimmed;
    }

    public static function flattenFieldErrors(array $fieldErrors): array
    {
        $errors = [];

        foreach ($fieldErrors as $messages) {
            foreach ((array) $messages as $message) {
                $errors[] = (string) $message;
            }
        }

        return array_values(array_unique($errors));
    }

    public static function registration(array $input): array
    {
        return self::flattenFieldErrors(self::registrationFields($input));
    }

    public static function registrationFields(array $input): array
    {
        $errors = [];

        $fullName = normalize_whitespace((string) ($input['full_name'] ?? ''));
        $email = (string) ($input['email'] ?? '');
        $password = (string) ($input['password'] ?? '');
        $role = (string) ($input['role'] ?? '');

        if ($fullName === '' || mb_strlen($fullName) < 2) {
            $errors['full_name'][] = 'Full name must be at least 2 characters.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Please enter a valid email address.';
        }

        if (mb_strlen($password) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters.';
        }

        if (!in_array($role, ['client', 'tasker'], true)) {
            $errors['role'][] = 'Please select a valid account type.';
        }

        return $errors;
    }

    public static function login(array $input): array
    {
        return self::flattenFieldErrors(self::loginFields($input));
    }

    public static function loginFields(array $input): array
    {
        $errors = [];
        $email = (string) ($input['email'] ?? '');
        $password = (string) ($input['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Please enter a valid email address.';
        }

        if ($password === '') {
            $errors['password'][] = 'Password is required.';
        }

        return $errors;
    }

    public static function task(array $input, array $categoryIds): array
    {
        return self::flattenFieldErrors(self::taskFields($input, $categoryIds));
    }

    public static function taskFields(array $input, array $categoryIds): array
    {
        $errors = [];
        $title = normalize_whitespace((string) ($input['title'] ?? ''));
        $description = normalize_whitespace((string) ($input['description'] ?? ''));
        $city = normalize_whitespace((string) ($input['city'] ?? ''));
        $country = normalize_whitespace((string) ($input['country'] ?? ''));
        $budget = (string) ($input['budget'] ?? '');
        $categoryId = (int) ($input['category_id'] ?? 0);
        $scheduledFor = (string) ($input['scheduled_for'] ?? '');

        if ($title === '' || mb_strlen($title) < 5 || mb_strlen($title) > 180) {
            $errors['title'][] = 'Title must be between 5 and 180 characters.';
        }

        if ($description === '' || mb_strlen($description) < 20) {
            $errors['description'][] = 'Description must be at least 20 characters.';
        }

        if ($city === '' || mb_strlen($city) > 100) {
            $errors['city'][] = 'City is required and must be 100 characters or fewer.';
        }

        if ($country === '' || mb_strlen($country) > 100) {
            $errors['country'][] = 'Country is required and must be 100 characters or fewer.';
        }

        if (!in_array($categoryId, $categoryIds, true)) {
            $errors['category_id'][] = 'Please choose a valid category.';
        }

        if (!is_numeric($budget) || (float) $budget <= 0) {
            $errors['budget'][] = 'Budget must be a positive amount.';
        }

        if ($scheduledFor !== '') {
            $timestamp = strtotime($scheduledFor);

            if ($timestamp === false) {
                $errors['scheduled_for'][] = 'Scheduled date must be a valid date and time.';
            }
        }

        return $errors;
    }

    public static function bid(array $input): array
    {
        return self::flattenFieldErrors(self::bidFields($input));
    }

    public static function bidFields(array $input): array
    {
        $errors = [];
        $amount = (string) ($input['amount'] ?? '');
        $message = (string) ($input['message'] ?? '');

        if (!is_numeric($amount) || (float) $amount <= 0) {
            $errors['amount'][] = 'Bid amount must be a positive amount.';
        }

        if ($message !== '' && mb_strlen($message) > 2000) {
            $errors['message'][] = 'Bid message must be 2000 characters or fewer.';
        }

        return $errors;
    }

    public static function message(array $input): array
    {
        return self::flattenFieldErrors(self::messageFields($input));
    }

    public static function messageFields(array $input): array
    {
        $errors = [];
        $body = trim((string) ($input['body'] ?? ''));

        if ($body === '') {
            $errors['body'][] = 'Message body is required.';
        }

        if (mb_strlen($body) > 4000) {
            $errors['body'][] = 'Message body must be 4000 characters or fewer.';
        }

        return $errors;
    }

    public static function review(array $input): array
    {
        return self::flattenFieldErrors(self::reviewFields($input));
    }

    public static function reviewFields(array $input): array
    {
        $errors = [];
        $rating = (int) ($input['rating'] ?? 0);
        $comment = trim((string) ($input['comment'] ?? ''));

        if ($rating < 1 || $rating > 5) {
            $errors['rating'][] = 'Rating must be between 1 and 5.';
        }

        if ($comment !== '' && mb_strlen($comment) > 2000) {
            $errors['comment'][] = 'Review comment must be 2000 characters or fewer.';
        }

        return $errors;
    }

    public static function profile(array $input): array
    {
        return self::flattenFieldErrors(self::profileFields($input));
    }

    public static function profileFields(array $input): array
    {
        $errors = [];
        $fullName = normalize_whitespace((string) ($input['full_name'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $city = trim((string) ($input['city'] ?? ''));
        $region = trim((string) ($input['region'] ?? ''));
        $country = trim((string) ($input['country'] ?? ''));
        $bio = trim((string) ($input['bio'] ?? ''));
        $skillsSummary = trim((string) ($input['skills_summary'] ?? ''));

        if ($fullName === '' || mb_strlen($fullName) < 2 || mb_strlen($fullName) > 150) {
            $errors['full_name'][] = 'Full name must be between 2 and 150 characters.';
        }

        if ($phone !== '' && !preg_match('/^[\d +\-().]{6,30}$/', $phone)) {
            $errors['phone'][] = 'Please enter a valid phone number.';
        }

        if ($city !== '' && mb_strlen($city) > 100) {
            $errors['city'][] = 'City must be 100 characters or fewer.';
        }

        if ($region !== '' && mb_strlen($region) > 100) {
            $errors['region'][] = 'Region must be 100 characters or fewer.';
        }

        if ($country !== '' && mb_strlen($country) > 100) {
            $errors['country'][] = 'Country must be 100 characters or fewer.';
        }

        if ($bio !== '' && mb_strlen($bio) > 1000) {
            $errors['bio'][] = 'Bio must be 1000 characters or fewer.';
        }

        if ($skillsSummary !== '' && mb_strlen($skillsSummary) > 280) {
            $errors['skills_summary'][] = 'Skills summary must be 280 characters or fewer.';
        }

        return $errors;
    }

    public static function newsletter(array $input): array
    {
        return self::flattenFieldErrors(self::newsletterFields($input));
    }

    public static function newsletterFields(array $input): array
    {
        $errors = [];
        $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
        $audience = trim((string) ($input['audience'] ?? ''));
        $allowedAudiences = ['client', 'tasker', 'partner'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Please enter a valid email address.';
        }

        if (!in_array($audience, $allowedAudiences, true)) {
            $errors['audience'][] = 'Please choose the kind of updates you want.';
        }

        return $errors;
    }

    public static function contact(array $input): array
    {
        return self::flattenFieldErrors(self::contactFields($input));
    }

    public static function contactFields(array $input): array
    {
        $errors = [];
        $name = normalize_whitespace((string) ($input['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
        $topic = normalize_whitespace((string) ($input['topic'] ?? ''));
        $message = trim((string) ($input['message'] ?? ''));

        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            $errors['name'][] = 'Please enter your name.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Please enter a valid email address.';
        }

        if ($topic === '' || mb_strlen($topic) > 120) {
            $errors['topic'][] = 'Please add a short topic.';
        }

        if ($message === '' || mb_strlen($message) < 20 || mb_strlen($message) > 3000) {
            $errors['message'][] = 'Message must be between 20 and 3000 characters.';
        }

        return $errors;
    }

    public static function marketplaceListingFields(array $input): array
    {
        $errors = [];
        $title = normalize_whitespace((string) ($input['title'] ?? ''));
        $description = normalize_whitespace((string) ($input['description'] ?? ''));
        $city = normalize_whitespace((string) ($input['city'] ?? ''));
        $country = normalize_whitespace((string) ($input['country'] ?? ''));
        $startingPrice = (string) ($input['starting_price'] ?? '');

        if ($title === '' || mb_strlen($title) < 5 || mb_strlen($title) > 180) {
            $errors['title'][] = 'Title must be between 5 and 180 characters.';
        }

        if ($description === '' || mb_strlen($description) < 20) {
            $errors['description'][] = 'Description must be at least 20 characters.';
        }

        if ($city === '' || mb_strlen($city) > 100) {
            $errors['city'][] = 'City is required and must be 100 characters or fewer.';
        }

        if ($country === '' || mb_strlen($country) > 100) {
            $errors['country'][] = 'Country is required and must be 100 characters or fewer.';
        }

        if (!is_numeric($startingPrice) || (float) $startingPrice <= 0) {
            $errors['starting_price'][] = 'Starting price must be a positive amount.';
        }

        return $errors;
    }

    public static function marketplaceBidFields(array $input, float $minimumAmount): array
    {
        $errors = [];
        $amount = (string) ($input['amount'] ?? '');
        $message = (string) ($input['message'] ?? '');

        if (!is_numeric($amount) || (float) $amount <= 0) {
            $errors['amount'][] = 'Bid amount must be a positive amount.';
        } elseif ((float) $amount < $minimumAmount) {
            $errors['amount'][] = 'Bid amount must be at least the listing price.';
        }

        if ($message !== '' && mb_strlen($message) > 2000) {
            $errors['message'][] = 'Bid message must be 2000 characters or fewer.';
        }

        return $errors;
    }

    public static function adFields(array $input): array
    {
        $errors = [];
        $title = normalize_whitespace((string) ($input['title'] ?? ''));
        $body = trim((string) ($input['body'] ?? ''));
        $ctaLabel = normalize_whitespace((string) ($input['cta_label'] ?? ''));
        $ctaUrl = trim((string) ($input['cta_url'] ?? ''));
        $placement = trim((string) ($input['placement'] ?? ''));
        $sortOrder = (string) ($input['sort_order'] ?? '0');

        if ($title === '' || mb_strlen($title) < 3 || mb_strlen($title) > 180) {
            $errors['title'][] = 'Ad title must be between 3 and 180 characters.';
        }

        if ($body === '' || mb_strlen($body) < 10 || mb_strlen($body) > 1000) {
            $errors['body'][] = 'Ad body must be between 10 and 1000 characters.';
        }

        if ($ctaLabel !== '' && mb_strlen($ctaLabel) > 80) {
            $errors['cta_label'][] = 'CTA label must be 80 characters or fewer.';
        }

        if ($ctaUrl !== '' && !filter_var($ctaUrl, FILTER_VALIDATE_URL) && !str_starts_with($ctaUrl, '/')) {
            $errors['cta_url'][] = 'CTA URL must be a valid absolute URL or a local path starting with /.';
        }

        if (!in_array($placement, ['home', 'marketplace'], true)) {
            $errors['placement'][] = 'Please choose a valid ad placement.';
        }

        if (!is_numeric($sortOrder)) {
            $errors['sort_order'][] = 'Sort order must be a number.';
        }

        return $errors;
    }

    public static function agreementAcceptanceFields(array $input): array
    {
        $errors = [];

        if ((string) ($input['confirm_offline_payment'] ?? '') !== '1') {
            $errors['confirm_offline_payment'][] = 'Please confirm that payment happens offline and not on the platform.';
        }

        if ((string) ($input['confirm_scope'] ?? '') !== '1') {
            $errors['confirm_scope'][] = 'Please confirm that the job scope and dispute terms are understood.';
        }

        return $errors;
    }

    public static function disputeFields(array $input): array
    {
        $errors = [];
        $type = trim((string) ($input['type'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));

        if (!in_array($type, ['non_payment', 'client_unavailable', 'tasker_no_show', 'scope_change', 'unsafe', 'other'], true)) {
            $errors['type'][] = 'Please choose a valid issue type.';
        }

        if ($description === '' || mb_strlen($description) < 20 || mb_strlen($description) > 3000) {
            $errors['description'][] = 'Issue details must be between 20 and 3000 characters.';
        }

        return $errors;
    }

    public static function adminDisputeUpdateFields(array $input): array
    {
        $errors = [];
        $status = trim((string) ($input['status'] ?? ''));
        $adminNotes = trim((string) ($input['admin_notes'] ?? ''));

        if (!in_array($status, ['open', 'under_review', 'resolved', 'rejected'], true)) {
            $errors['status'][] = 'Please choose a valid dispute status.';
        }

        if ($adminNotes !== '' && mb_strlen($adminNotes) > 4000) {
            $errors['admin_notes'][] = 'Admin notes must be 4000 characters or fewer.';
        }

        if (in_array($status, ['resolved', 'rejected'], true) && $adminNotes === '') {
            $errors['admin_notes'][] = 'Please add a short resolution note before closing a dispute.';
        }

        return $errors;
    }
}
