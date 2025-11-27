<?php
declare(strict_types=1);

namespace Crustum\Notification\Trait;

use ReflectionClass;

/**
 * Serializes Notification Trait
 *
 * Provides automatic serialization for notification instances.
 *
 * Usage:
 * ```
 * class MyNotification extends Notification
 * {
 *     use SerializesNotification;
 * }
 * ```
 */
trait SerializesNotificationTrait
{
    /**
     * Prepare the instance for serialization
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        $values = [];
        $reflectionClass = new ReflectionClass($this);
        $class = static::class;
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if (!$property->isInitialized($this)) {
                continue;
            }

            $value = $property->getValue($this);

            if ($property->hasDefaultValue() && $value === $property->getDefaultValue()) {
                continue;
            }

            $name = $property->getName();

            if ($property->isPrivate()) {
                $name = "\0{$class}\0{$name}";
            } elseif ($property->isProtected()) {
                $name = "\0*\0{$name}";
            }

            $values[$name] = $this->serializeValue($value);
        }

        $values['__class__'] = $class;

        return $values;
    }

    /**
     * Restore the instance after unserialization
     *
     * @param array<string, mixed> $values Serialized values
     * @return void
     */
    public function __unserialize(array $values): void
    {
        $reflectionClass = new ReflectionClass($this);
        $class = static::class;
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();

            if ($property->isPrivate()) {
                $name = "\0{$class}\0{$name}";
            } elseif ($property->isProtected()) {
                $name = "\0*\0{$name}";
            }

            if (!array_key_exists($name, $values)) {
                continue;
            }

            $property->setValue($this, $this->unserializeValue($values[$name]));
        }
    }

    /**
     * Serialize a property value
     *
     * @param mixed $value The value to serialize
     * @return mixed Serialized value
     */
    protected function serializeValue(mixed $value): mixed
    {
        if (is_object($value) && method_exists($value, 'toArray')) {
            return [
                '__entity__' => true,
                'class' => get_class($value),
                'data' => $value->toArray(),
            ];
        }

        return $value;
    }

    /**
     * Unserialize a property value
     *
     * @param mixed $value The value to unserialize
     * @return mixed Unserialized value
     */
    protected function unserializeValue(mixed $value): mixed
    {
        if (is_array($value) && isset($value['__entity__']) && $value['__entity__'] === true) {
            return $value['data'];
        }

        return $value;
    }
}
