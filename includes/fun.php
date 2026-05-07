<?php
// Shared utility functions used by registration, lookups, and listing flows.
require_once 'config.php';

// Look up a user record by email and return the matching database row.
function checkUserByEmail(mysqli $conn, string $email): ?array
{
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

// Register a new user and store the password as a secure hash in users.password_hash.
function RegisterUser(mysqli $conn, string $username, string $email, string $password, string $full_name, string $postal_code, string $address, ?float $lat, ?float $lng): bool
{
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, postal_code, address, latitude, longitude)
            VALUES (?,?,?,?,?,?,?,?)");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ssssssdd', $username, $email, $hashedPassword, $full_name, $postal_code, $address, $lat, $lng);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

