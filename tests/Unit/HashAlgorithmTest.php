<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Idempotency\HashAlgorithm;

covers(HashAlgorithm::class);

/*
|--------------------------------------------------------------------------
| Happy Path Tests - fromString() Method
|--------------------------------------------------------------------------
*/

test('creates MD5 from string', function (): void {
    // Act
    $algorithm = HashAlgorithm::fromString('md5');

    // Assert
    expect($algorithm)->toBe(HashAlgorithm::MD5);
})->group('happy-path', 'from-string');

test('creates SHA1 from string', function (): void {
    // Act
    $algorithm = HashAlgorithm::fromString('sha1');

    // Assert
    expect($algorithm)->toBe(HashAlgorithm::SHA1);
})->group('happy-path', 'from-string');

test('creates SHA1 from hyphenated string', function (): void {
    // Act
    $algorithm = HashAlgorithm::fromString('sha-1');

    // Assert
    expect($algorithm)->toBe(HashAlgorithm::SHA1);
})->group('happy-path', 'from-string');

test('creates SHA256 from string', function (): void {
    // Act
    $algorithm = HashAlgorithm::fromString('sha256');

    // Assert
    expect($algorithm)->toBe(HashAlgorithm::SHA256);
})->group('happy-path', 'from-string');

test('creates SHA256 from hyphenated string', function (): void {
    // Act
    $algorithm = HashAlgorithm::fromString('sha-256');

    // Assert
    expect($algorithm)->toBe(HashAlgorithm::SHA256);
})->group('happy-path', 'from-string');

test('creates SHA512 from string', function (): void {
    // Act
    $algorithm = HashAlgorithm::fromString('sha512');

    // Assert
    expect($algorithm)->toBe(HashAlgorithm::SHA512);
})->group('happy-path', 'from-string');

test('creates SHA512 from hyphenated string', function (): void {
    // Act
    $algorithm = HashAlgorithm::fromString('sha-512');

    // Assert
    expect($algorithm)->toBe(HashAlgorithm::SHA512);
})->group('happy-path', 'from-string');

test('handles case-insensitive algorithm names', function (): void {
    // Act & Assert
    expect(HashAlgorithm::fromString('MD5'))->toBe(HashAlgorithm::MD5);
    expect(HashAlgorithm::fromString('SHA256'))->toBe(HashAlgorithm::SHA256);
    expect(HashAlgorithm::fromString('SHA-512'))->toBe(HashAlgorithm::SHA512);
})->group('happy-path', 'from-string');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - length() Method
|--------------------------------------------------------------------------
*/

test('returns correct length for MD5', function (): void {
    // Arrange
    $algorithm = HashAlgorithm::MD5;

    // Act
    $length = $algorithm->length();

    // Assert
    expect($length)->toBe(32);
})->group('happy-path', 'length');

test('returns correct length for SHA1', function (): void {
    // Arrange
    $algorithm = HashAlgorithm::SHA1;

    // Act
    $length = $algorithm->length();

    // Assert
    expect($length)->toBe(40);
})->group('happy-path', 'length');

test('returns correct length for SHA256', function (): void {
    // Arrange
    $algorithm = HashAlgorithm::SHA256;

    // Act
    $length = $algorithm->length();

    // Assert
    expect($length)->toBe(64);
})->group('happy-path', 'length');

test('returns correct length for SHA512', function (): void {
    // Arrange
    $algorithm = HashAlgorithm::SHA512;

    // Act
    $length = $algorithm->length();

    // Assert
    expect($length)->toBe(128);
})->group('happy-path', 'length');

/*
|--------------------------------------------------------------------------
| Sad Path Tests - fromString() Method
|--------------------------------------------------------------------------
*/

test('throws exception for unsupported algorithm', function (): void {
    // Act & Assert
    HashAlgorithm::fromString('unsupported');
})->throws(InvalidArgumentException::class, 'Unsupported hash algorithm: unsupported')->group('sad-path', 'from-string');

test('throws exception for empty string', function (): void {
    // Act & Assert
    HashAlgorithm::fromString('');
})->throws(InvalidArgumentException::class)->group('sad-path', 'from-string');

test('throws exception for invalid algorithm name', function (): void {
    // Act & Assert
    HashAlgorithm::fromString('sha999');
})->throws(InvalidArgumentException::class, 'Unsupported hash algorithm: sha999')->group('sad-path', 'from-string');

/*
|--------------------------------------------------------------------------
| Edge Case Tests
|--------------------------------------------------------------------------
*/

test('handles algorithm with extra whitespace', function (): void {
    // Note: fromString does not trim, so this should fail
    HashAlgorithm::fromString(' sha256 ');
})->throws(InvalidArgumentException::class)->group('edge-case');
