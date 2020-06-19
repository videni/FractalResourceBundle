<?php

declare(strict_types=1);

namespace Videni\Bundle\FractalResourceBundle\Serializer;

use JMS\Serializer\Visitor\SerializationVisitorInterface;
use JMS\Serializer\XmlSerializationVisitor;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface as TranslatorContract;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\Handler\FormErrorHandler as JMSFormErrorHandler;

final class FormErrorHandler implements SubscribingHandlerInterface
{
    /**
     * @var TranslatorInterface|null
     */
    private $translator;

    /**
     * @var string
     */
    private $translationDomain;

    public function __construct(?object $translator = null, string $translationDomain = 'validators')
    {
        if (null!== $translator && (!$translator instanceof TranslatorInterface && !$translator instanceof TranslatorContract)) {
            throw new \InvalidArgumentException(sprintf(
                'The first argument passed to %s must be instance of %s or %s, %s given',
                self::class,
                TranslatorInterface::class,
                TranslatorContract::class
            ));
        }
        $this->translator = $translator;
        $this->translationDomain = $translationDomain;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods()
    {
        return JMSFormErrorHandler::getSubscribingMethods();
    }

    /**
     * @param array $type
     */
    public function serializeFormToXml(XmlSerializationVisitor $visitor, Form $form, array $type): \DOMElement
    {
        $formNode = $visitor->getDocument()->createElement('form');

        $formNode->setAttribute('name', $form->getName());

        $violations = [];
        $this->flatFormErrors($form, $violations);

        $formNode->appendChild($errorsNode = $visitor->getDocument()->createElement('errors'));
        foreach ($violations as $path => $error) {
            $errorNode = $visitor->getDocument()->createElement('entry');
            $errorNode->appendChild($visitor->getDocument()->createCDATASection($path));
            $errorNode->appendChild($this->serializeFormErrorToXml($visitor, $error, []));

            $errorsNode->appendChild($errorNode);
        }

        return $formNode;
    }

    /**
     * @param array $type
     */
    public function serializeFormToJson(SerializationVisitorInterface $visitor, Form $form, array $type): \ArrayObject
    {
        $violations = [];
        $this->flatFormErrors($form, $violations);

        return $this->convertFormToArray($violations);
    }

    /**
     * @param array $type
     */
    public function serializeFormErrorToXml(XmlSerializationVisitor $visitor, FormError $formError, array $type): \DOMCdataSection
    {
        return $visitor->getDocument()->createCDATASection($this->getErrorMessage($formError));
    }

    /**
     * @param array $type
     */
    public function serializeFormErrorToJson(SerializationVisitorInterface $visitor, FormError $formError, array $type): string
    {
        return $this->getErrorMessage($formError);
    }

    private function getErrorMessage(FormError $error): ?string
    {
        if (null === $this->translator) {
            return $error->getMessage();
        }

        if (null !== $error->getMessagePluralization()) {
            if ($this->translator instanceof TranslatorContract) {
                return $this->translator->trans($error->getMessageTemplate(), ['%count%' => $error->getMessagePluralization()] + $error->getMessageParameters(), $this->translationDomain);
            } else {
                return $this->translator->transChoice($error->getMessageTemplate(), $error->getMessagePluralization(), $error->getMessageParameters(), $this->translationDomain);
            }
        }

        return $this->translator->trans($error->getMessageTemplate(), $error->getMessageParameters(), $this->translationDomain);
    }

    private function convertFormToArray($errors)
    {
        $messages = new \ArrayObject();
        foreach ($errors as $path => $violations) {
            $messages[$path][] = array_map(function($violation){
                return [
                    'message' => $this->getErrorMessage($violation),
                ];
            }, $violations);
        }

        return $messages;
    }

    private function flatFormErrors(FormInterface $form, &$violations, $name = '', $previousPath = ''): void
    {
        $currentPath = $previousPath ?  \implode('.', [$previousPath, $name]): $name ;

        foreach ($form->getErrors() as $error) {
            $violations[$currentPath][] = $error;
        }

        foreach ($form->all() as $child) {
            if ($child instanceof FormInterface) {
                $this->flatFormErrors($child, $violations, $child->getName(), $currentPath);
            }
        }
    }
}
