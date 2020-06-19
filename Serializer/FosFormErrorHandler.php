<?php

namespace Videni\Bundle\FractalResourceBundle\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use JMS\Serializer\XmlSerializationVisitor;
use Symfony\Component\Form\Form;
use JMS\Serializer\YamlSerializationVisitor;

/**
 * A copy of FOS\RestBundle\Serializer\Normalizer\FormErrorHandler
 *
 * @internal since 2.8
 */
class FosFormErrorHandler implements SubscribingHandlerInterface
{
    private $formErrorHandler;

    public function __construct(FormErrorHandler $formErrorHandler)
    {
        $this->formErrorHandler = $formErrorHandler;
    }

    public static function getSubscribingMethods()
    {
        return FormErrorHandler::getSubscribingMethods();
    }

    public function serializeFormToXml(XmlSerializationVisitor $visitor, Form $form, array $type, Context $context = null)
    {
        if ($context) {
            if ($context->hasAttribute('status_code')) {
                $document = $visitor->getDocument(true);
                if (!$visitor->getCurrentNode()) {
                    $visitor->createRoot();
                }

                $codeNode = $document->createElement('code');
                $visitor->getCurrentNode()->appendChild($codeNode);
                $codeNode->appendChild($context->getNavigator()->accept($context->getAttribute('status_code'), null, $context));

                $messageNode = $document->createElement('message');
                $visitor->getCurrentNode()->appendChild($messageNode);
                $messageNode->appendChild($context->getNavigator()->accept('Validation Failed', null, $context));

                $errorsNode = $document->createElement('errors');
                $visitor->getCurrentNode()->appendChild($errorsNode);
                $visitor->setCurrentNode($errorsNode);

                $errorNodes = $this->formErrorHandler->serializeFormToXml($visitor, $form, $type);
                $errorsNode->appendChild($errorNodes);

                $visitor->revertCurrentNode();

                return $visitor->getCurrentNode();
            }
        }

        return $this->formErrorHandler->serializeFormToXml($visitor, $form, $type);
    }

    public function serializeFormToJson(JsonSerializationVisitor $visitor, Form $form, array $type, Context $context = null)
    {
        $isRoot = !interface_exists(SerializationVisitorInterface::class) && null === $visitor->getRoot();
        $result = $this->adaptFormArray($this->formErrorHandler->serializeFormToJson($visitor, $form, $type), $context);

        if ($isRoot) {
            $visitor->setRoot($result);
        }

        return $result;
    }

    public function serializeFormToYml(YamlSerializationVisitor $visitor, Form $form, array $type, Context $context = null)
    {
        $isRoot = null === $visitor->getRoot();
        $result = $this->adaptFormArray($this->formErrorHandler->serializeFormToYml($visitor, $form, $type), $context);

        if ($isRoot) {
            $visitor->setRoot($result);
        }

        return $result;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->formErrorHandler, $name], $arguments);
    }

    private function adaptFormArray(\ArrayObject $serializedForm, Context $context = null)
    {
        $statusCode = $this->getStatusCode($context);
        if (null !== $statusCode) {
            return [
                'code' => $statusCode,
                'message' => 'Validation Failed',
                'errors' => $serializedForm,
            ];
        }

        return $serializedForm;
    }

    private function getStatusCode(Context $context = null)
    {
        if (null === $context) {
            return;
        }

        if ($context->hasAttribute('status_code')) {
            return $context->getAttribute('status_code');
        }
    }
}
