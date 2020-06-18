<?php

declare(strict_types=1);

namespace FOS\Bundle\FractalResourceBundle\Serializer;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Serializer\Serializer;
use League\Fractal\Resource\ResourceInterface;

class FractalSerializerAdapter implements Serializer
{
    private $decreated;

    public function __construct(Serializer $decreated)
    {
        $this->decreated = $decreated;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($data, $format, Context $context)
    {
        $data  = $data instanceof ResourceInterface ? $data->toArray(): $data;
       
        return $this->decreated->serialize($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($data, $type, $format, Context $context)
    {
        $this->decreated->deserialize($data, $type, $format, $context);
    }
}
