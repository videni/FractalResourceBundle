<?php

declare(strict_types=1);

namespace Videni\Bundle\FractalResourceBundle\Serializer;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Serializer\Serializer;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\Manager;

class FractalSerializerAdapter implements Serializer
{
    private $decreated;

    private $manager;

    public function __construct(Serializer $decreated, Manager $manager)
    {
        $this->decreated = $decreated;
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($data, $format, Context $context)
    {
        if ($data instanceof ResourceInterface) {
            $data = $this->manager->createData($data)->toArray();
        }
       
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
