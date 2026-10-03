<?php

declare(strict_types=1);

namespace SymconECharts;

use InvalidArgumentException;
use JsonException;
use UnexpectedValueException;

/**
 * Defines the versioned application protocol transported through Symcon data flow.
 */
final class EChartsDataProtocol
{
    public const VERSION = 1;
    public const OPERATION_CURRENT_READ = 'current.read';

    private const JSON_FLAGS = JSON_THROW_ON_ERROR
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_PRESERVE_ZERO_FRACTION;

    /**
     * Creates a request payload for the Symcon data-flow envelope.
     *
     * @param array<string, mixed> $payload
     *
     * @return array{ProtocolVersion: int, Operation: string, Payload: array<string, mixed>}
     */
    public static function CreateRequest(string $operation, array $payload): array
    {
        self::AssertOperation($operation);

        return [
            'ProtocolVersion' => self::VERSION,
            'Operation'       => $operation,
            'Payload'         => $payload
        ];
    }

    /**
     * Validates and normalizes a decoded request including an optional DataID.
     *
     * @param array<string, mixed> $message
     *
     * @return array{ProtocolVersion: int, Operation: string, Payload: array<string, mixed>}
     */
    public static function DecodeRequest(array $message): array
    {
        $version = $message['ProtocolVersion'] ?? null;
        if ($version !== self::VERSION) {
            throw new UnexpectedValueException('Unsupported ECharts data protocol version.');
        }

        $operation = $message['Operation'] ?? null;
        if (!is_string($operation)) {
            throw new InvalidArgumentException('The protocol operation must be a string.');
        }
        self::AssertOperation($operation);

        $payload = $message['Payload'] ?? null;
        if (!is_array($payload)) {
            throw new InvalidArgumentException('The protocol payload must be an object.');
        }

        return [
            'ProtocolVersion' => self::VERSION,
            'Operation'       => $operation,
            'Payload'         => $payload
        ];
    }

    /**
     * Encodes a successful synchronous gateway response.
     *
     * @param array<string, mixed> $payload
     *
     * @throws JsonException
     */
    public static function EncodeSuccessResponse(string $operation, array $payload): string
    {
        self::AssertOperation($operation);

        return json_encode([
            'ProtocolVersion' => self::VERSION,
            'Operation'       => $operation,
            'Success'         => true,
            'Payload'         => $payload
        ], self::JSON_FLAGS);
    }

    /**
     * Encodes a failed synchronous gateway response without exposing internal details.
     *
     * @throws JsonException
     */
    public static function EncodeErrorResponse(
        string $operation,
        string $code,
        string $message
    ): string {
        self::AssertOperation($operation);
        if (trim($code) === '' || trim($message) === '') {
            throw new InvalidArgumentException('Protocol errors require a code and message.');
        }

        return json_encode([
            'ProtocolVersion' => self::VERSION,
            'Operation'       => $operation,
            'Success'         => false,
            'Error'           => [
                'Code'    => $code,
                'Message' => $message
            ]
        ], self::JSON_FLAGS);
    }

    /**
     * Decodes and validates a synchronous gateway response.
     *
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public static function DecodeResponse(string $json, string $expectedOperation): array
    {
        self::AssertOperation($expectedOperation);

        $root = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        if (!$root instanceof \stdClass) {
            throw new InvalidArgumentException('The protocol response must be a JSON object.');
        }

        $response = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($response)) {
            throw new InvalidArgumentException('The protocol response could not be decoded.');
        }

        if (($response['ProtocolVersion'] ?? null) !== self::VERSION) {
            throw new UnexpectedValueException('Unsupported ECharts data protocol response version.');
        }
        if (($response['Operation'] ?? null) !== $expectedOperation) {
            throw new UnexpectedValueException('The gateway response uses an unexpected operation.');
        }
        if (!is_bool($response['Success'] ?? null)) {
            throw new InvalidArgumentException('The gateway response must contain a boolean Success field.');
        }

        if ($response['Success']) {
            if (!is_array($response['Payload'] ?? null)) {
                throw new InvalidArgumentException('A successful gateway response requires an object payload.');
            }
        } elseif (!is_array($response['Error'] ?? null)
            || !is_string($response['Error']['Code'] ?? null)
            || !is_string($response['Error']['Message'] ?? null)
        ) {
            throw new InvalidArgumentException('A failed gateway response requires a structured error.');
        }

        return $response;
    }

    private static function AssertOperation(string $operation): void
    {
        if (trim($operation) === '') {
            throw new InvalidArgumentException('The protocol operation must not be empty.');
        }
    }
}
