<?php

/*
 * This file is part of the Limenius\Liform package.
 *
 * (c) Limenius <https://github.com/Limenius/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Limenius\Liform\Serializer\Normalizer;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface as TranslatorContract;

/**
 * Normalizes invalid Form instances.
 *
 * @author Ener-Getick <egetick@gmail.com>
 */
class FormErrorNormalizer implements NormalizerInterface
{
    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        return [
            'code' => $context['status_code'] ?? null,
            'message' => 'Validation Failed',
            'errors' => $this->convertFormToArray($object),
        ];
    }

    /**
     * {@inheritdoc}
     * @param mixed $data
     * @param null $format
     * @param array $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof FormInterface && $data->isSubmitted() && !$data->isValid();
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes(?string $format): array
    {
        return [Form::class => true];
    }

    /**
     * This code has been taken from JMSSerializer.
     *
     * @param FormInterface $data
     *
     * @return array
     */
    private function convertFormToArray(FormInterface $data): array
    {
        $form = $errors = [];
        foreach ($data->getErrors() as $error) {
            $errors[] = $this->getErrorMessage($error);
        }

        if ($errors) {
            $form['errors'] = $errors;
        }

        $children = [];
        foreach ($data->all() as $child) {
            if ($child instanceof FormInterface) {
                $children[$child->getName()] = $this->convertFormToArray($child);
            }
        }

        if ($children) {
            $form['children'] = $children;
        }

        return $form;
    }

    /**
     * @param FormError $error
     *
     * @return string
     */
    private function getErrorMessage(FormError $error)
    {
        if (null !== $error->getMessagePluralization()) {
            // old way
//            if ($this->translator instanceof TranslatorContract) {
//                return $this->translator->trans($error->getMessageTemplate(), ['%count%' => $error->getMessagePluralization()] + $error->getMessageParameters(), 'validators');
//            } else {
//                return $this->translator->transChoice($error->getMessageTemplate(), $error->getMessagePluralization(), $error->getMessageParameters(), 'validators');
//            }

            return $this->translator->trans(
                $error->getMessageTemplate(),
                array_merge(
                    $error->getMessageParameters(),
                    ['%count%' => $error->getMessagePluralization()]
                ),
                'validators'
            );
        }

        return $this->translator->trans($error->getMessageTemplate(), $error->getMessageParameters(), 'validators');
    }
}
