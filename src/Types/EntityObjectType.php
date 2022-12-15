<?php

declare(strict_types=1);

namespace Apollo\Federation\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Utils\Utils;

use function is_callable;

/**
 * An entity is a type that can be referenced by another service. Entities create
 * connection points between services and form the basic building blocks of a federated
 * graph. Entities have a primary key whose value uniquely identifies a specific instance
 * of the type, similar to the function of a primary key in a SQL table
 * (see [related docs](https://www.apollographql.com/docs/apollo-server/federation/core-concepts/#entities-and-keys)).
 *
 * The `keyFields` property is required in the configuration, indicating the fields that
 * serve as the unique keys or identifiers of the entity.
 *
 * Sample usage:
 *
 *     $userType = new Apollo\Federation\Types\EntityObjectType([
 *       'name' => 'User',
 *       'keyFields' => ['id', 'email'],
 *       'fields' => [...]
 *     ]);
 *
 * Entity types can also set attributes to its fields to hint the gateway on how to resolve them.
 *
 *     $userType = new Apollo\Federation\Types\EntityObjectType([
 *       'name' => 'User',
 *       'keyFields' => ['id', 'email'],
 *       'fields' => [
 *         'id' => [
 *           'type' => Types::int(),
 *           'isExternal' => true,
 *         ]
 *       ]
 *     ]);
 */
class EntityObjectType extends ObjectType
{
    /** @var string[] */
    private array $keyFields;

    /** @var callable | null */
    public $referenceResolver = null;

    /**
     * @param array{keyFields?: string[], __resolveReference?: callable} $config
     */
    public function __construct(array $config)
    {
        $this->keyFields = $config['keyFields'] ?? [];

        if (isset($config['__resolveReference'])) {
            self::validateResolveReference($config);
            $this->referenceResolver = $config['__resolveReference'] ?? null;
        }

        parent::__construct($config);
    }

    /**
     * Gets the fields that serve as the unique key or identifier of the entity.
     *
     * @return string[]
     */
    public function getKeyFields(): array
    {
        return $this->keyFields;
    }

    /**
     * Gets whether this entity has a resolver set
     */
    public function hasReferenceResolver(): bool
    {
        return $this->referenceResolver !== null;
    }

    /**
     * Resolves an entity from a reference
     *
     * @param array{__typename?: mixed} | null $ref
     * @param mixed $context
     * @param mixed $info
     *
     * @return mixed
     */
    public function resolveReference(?array $ref = null, $context = null, $info = null)
    {
        $this->validateReferenceResolver();
        $this->validateReferenceKeys($ref);

        /** @var callable $resolver */
        $resolver = $this->referenceResolver;

        return ($resolver)($ref, $context, $info);
    }

    private function validateReferenceResolver(): void
    {
        Utils::invariant($this->hasReferenceResolver(), 'No reference resolver was set in the configuration.');
    }

    /**
     * @param array{__typename?: mixed} | null $ref
     */
    private function validateReferenceKeys(?array $ref): void
    {
        Utils::invariant(isset($ref['__typename']), 'Type name must be provided in the reference.');
    }

    /**
     * @param array{keyFields?: string[], __resolveReference?: callable} $config
     */
    public static function validateResolveReference(array $config): void
    {
        Utils::invariant(is_callable($config['__resolveReference'] ?? ''), 'Reference resolver has to be callable.');
    }
}
