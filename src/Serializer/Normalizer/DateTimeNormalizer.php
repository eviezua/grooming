<?php

namespace App\Serializer\Normalizer;

use DateTimeInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DateTimeNormalizer implements NormalizerInterface
{
    public function normalize($object, ?string $format = null, array $context = []): ?array
    {
        if (!$object instanceof DateTimeInterface) {
            return null;
        }

        return [
            'date' => $object->format('Y-m-d'),
            'time' => $object->format('H:i:s')
        ];
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof DateTimeInterface;
    }

    public function getSupportedTypes(?string $format = null): array
    {
        return [
            DateTimeInterface::class => true,
        ];
    }
}
